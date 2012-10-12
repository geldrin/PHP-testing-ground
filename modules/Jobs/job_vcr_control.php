<?php
// VCR control job 2012/08/17

define('BASE_PATH',	realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

@include("SOAP/Client.php");

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');

// Utils
include_once('job_utils_base.php');
include_once('job_utils_log.php');
include_once('job_utils_status.php');

set_time_limit(0);

// Init
$app = new Springboard\Application\Cli(BASE_PATH, PRODUCTION);

// Load jobs configuration file
$app->loadConfig('modules/Jobs/config_jobs.php');
$jconf = $app->config['config_jobs'];
$myjobid = $jconf['jobid_vcr_control'];

// Log related init
$debug = Springboard\Debug::getInstance();
$debug->log($jconf['log_dir'], $myjobid . ".log", "VCR control job started", $sendmail = false);

// Check operating system - exit if Windows
if ( iswindows() ) {
	echo "ERROR: Non-Windows process started on Windows platform\n";
	exit;
}

// Start an infinite loop - exit if any STOP file appears
while( !is_file( $app->config['datapath'] . 'jobs/job_vcr_control.stop' ) and !is_file( $app->config['datapath'] . 'jobs/all.stop' ) ) {

	clearstatcache();

    while ( 1 ) {

		$app->watchdog();
	
		$db_close = FALSE;
		$sleep_length = $jconf['sleep_vcr'];

		// Establish database connection
		try {
			$db = $app->bootstrap->getAdoDB();
		} catch (exception $err) {
			// Send mail alert, sleep for 15 minutes
			$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] No connection to DB (getAdoDB() failed). Error message:\n" . $err, $sendmail = true);
			// Sleep 15 mins then resume
			$sleep_length = 15 * 60;
			break;
		}

// !!! set start values from DB
// START
//update_db_stream_status(5, $jconf['dbstatus_vcr_start']);
//update_db_vcr_reclink_status(1, $jconf['dbstatus_vcr_ready']);
// STOP
//update_db_stream_status(5, $jconf['dbstatus_vcr_disc']);
// !!!

		// Initialize log for closing message and total duration timer
		$global_log = "";
		$start_time = time();

		$vcr = array();
		$vcr_user = array();

		// Query next job - exit if none
		if ( !query_vcrnew($vcr, $vcr_user) ) break;

		// Alias: choose normal or secure
		$vcr['aliasused'] = $vcr['alias'];
		if ( $vcr['issecurestreamingforced'] == 1 ) $vcr['aliasused'] = $vcr['aliassecure'];

//var_dump($vcr);

		// Start global log
		$global_log .= "Live feed: " . $vcr['feed_name'] . " (ID = " . $vcr['feed_id'] . ")\n";
		$global_log .= "Stream: " . $vcr['name'] . " (ID = " . $vcr['id'] . ")\n";
		$global_log .= "User: " . $vcr_user['nickname'] . " (" . $vcr_user['email'] . ")\n";
		$global_log .= "Recording link: " . $vcr['reclink_name'] . " (ID = " . $vcr['reclink_id'] . ")\n";
		$global_log .= "Call: " . $vcr['calltype'] . ":" . $vcr['number'] . " @ " . $vcr['bitrate'] . "KBps\n";
		$global_log .= "Recording profile: " . $vcr['aliasused'] . "\n\n";

		// Start log entry
		log_recording_conversion($vcr['id'], $myjobid, $jconf['dbstatus_init'], "START Videoconference recording:\n\n" . $global_log, "-", "-", 0, FALSE);

		$app->watchdog();

		// Connect to Cisco TCS
		$soap_rs = TCS_Connect();
		if ( $soap_rs === FALSE ) {
			$sleep_length = 15 * 60;
			break;
		}

		// System health: check
		$err = array();
		$err = TCS_GetSystemHealth();
		if ( !$err['code'] ) {
			log_recording_conversion(0, $jconf['jobid_vcr_control'], $jconf['dbstatus_init'], "[ERROR] VCR system health problems.\n\n" . print_r($err['message'], TRUE), "-", "-", 0, TRUE);
			break;
		}

		// TODO: error handling, DB logging
		$err = array();
		$err = TCS_GetSystemInformation();

		$app->watchdog();

//echo "stat: " . $vcr['status'] . "\n";
//echo "rlstat: " . $vcr['reclink_status'] . "\n";

		// START RECORDING: a recording needs to be started, recording link is available
		if ( ( $vcr['status'] == $jconf['dbstatus_vcr_start'] ) and ( $vcr['reclink_status'] == $jconf['dbstatus_vcr_ready'] ) ) {

			// TCS: Check system capacity
			$err = array();
			$err = TCS_GetCallCapacity();
			if ( $err['data']['maxcalls'] == $err['data']['currentcalls'] ) {
				log_recording_conversion(0, $jconf['jobid_vcr_control'], $jconf['dbstatus_init'], "[ERROR] VCR call capacity reached. Cannot start a new recording. Info:\n\n" . $err['message'], "-", "-", 0, TRUE);
				break;
			}
			if ( $err['data']['maxlivecalls'] == $err['data']['currentlivecalls'] ) {
				log_recording_conversion(0, $jconf['jobid_vcr_control'], $jconf['dbstatus_init'], "[ERROR] VCR live call capacity reached. Cannot start a new recording. Info:\n\n" . $err['message'], "-", "-", 0, TRUE);
				break;
			}

			// Starting recording
			update_db_stream_status($vcr['id'], $jconf['dbstatus_vcr_starting']);

			// TCS: reserve ConferenceID
			$vcr['conf_id'] = TCS_ReserveConfId($vcr);
// Err handling?

			// TCS: dial recording link to start recording session
			$err = array();
			$err = TCS_Dial($vcr);
			if ( $err['code'] ) {
				$global_log .= $err['message'];
				log_recording_conversion($vcr['id'], $myjobid, $jconf['dbstatus_vcr_recording'], $err['message'], "-", "-", 0, FALSE);
				// Recording link: indicate recording status
				update_db_vcr_reclink_status($vcr['reclink_id'], $jconf['dbstatus_vcr_recording']);
			} else {
				log_recording_conversion($vcr['id'], $myjobid, $jconf['dbstatus_vcr_starting'], "[ERROR] VCR call cannot be established. Info:\n\n" . $err['message'], "-", "-", 0, TRUE);
				break;
			}

			// Wait for TCS 5 sec countdown
			sleep(10);

			// Get conference info
			$err = array();
			$err = TCS_GetConfInfo($vcr);
			if ( $err['code'] ) {
				// Update: RTMP URL, aspect ratio, conference ID
				// If RTMP URL is not available
				if ( empty($vcr['rtmp_streamid']) ) {
					log_recording_conversion($vcr['id'], $myjobid, $jconf['dbstatus_vcr_recording'], "[ERROR] VCR live stream RTMP URL is not available. Info:\n\n" . $err['message'], "-", "-", 0, TRUE);
					break;
				}
				update_db_stream_params($vcr['id'], $vcr['rtmp_streamid'], $vcr['aspectratio'], $vcr['conf_id']);
				// Update: recording link with TCS conference ID
				update_db_vcr_reclink_params($vcr['reclink_id'], $vcr['conf_id']);
				// Stream: playable
				update_db_stream_status($vcr['id'], $jconf['dbstatus_vcr_recording']);
			} else {
				log_recording_conversion($vcr['id'], $myjobid, $jconf['dbstatus_vcr_recording'], "[ERROR] VCR call info is not available. Info:\n\n" . $err['message'], "-", "-", 0, TRUE);
				break;
			}

			// Summary log entry and mail
			$total_duration = time() - $start_time;
			$hms = secs2hms($total_duration);
			log_recording_conversion($vcr['id'], $myjobid, $jconf['dbstatus_vcr_recording'], "-", "[OK] Successful recording initiation in " . $hms . " time.\n\nSummary:\n\n" . $global_log, "-", "-", $total_duration, TRUE);

			$app->watchdog();
		}

		// ONGOING RECORDING: check ongoing recording status
		if ( ( $vcr['status'] == $jconf['dbstatus_vcr_recording'] ) and ( $vcr['reclink_status'] == $jconf['dbstatus_vcr_recording'] ) ) {

			$err = array();
			$err = TCS_CallInfo($vcr);
			if ( !$err ) {

				if ( $err['data']['callstate'] != "IN_CALL" ) {
					log_recording_conversion($vcr['id'], $myjobid, $jconf['dbstatus_vcr_recording'], "[ERROR] VCR call was disconnected unexpectedly. Info:\n\n" . $err['message'], "-", print_r($err['data'], TRUE), 0, TRUE);
					// Indicate error on stream
					update_db_stream_status($vcr['id'], $jconf['dbstatus_vcr_recording_err']);
					// Permanent error on recording link?
					update_db_vcr_reclink_status($vcr['reclink_id'], $jconf['dbstatus_vcr_recording_err']);
					break;
				}

				if ( ( $err['data']['mediastate'] != "RECORDING" ) and ( $err['data']['writerstatus'] != "OK" ) ) {
					log_recording_conversion($vcr['id'], $myjobid, $jconf['dbstatus_vcr_recording'], "[ERROR] Unexpected VCR recording error. Info:\n\n" . $err['message'], "-", print_r($err['data'], TRUE), 0, TRUE);
					// Indicate error on stream
					update_db_stream_status($vcr['id'], $jconf['dbstatus_vcr_recording_err']);
					// Permanent error on recording link?
					update_db_vcr_reclink_status($vcr['reclink_id'], $jconf['dbstatus_vcr_recording_err']);
					break;
				}
			}

			$app->watchdog();
		}

		// DISCONNECT: a recording needs to be stopped
		if ( ( $vcr['status'] == $jconf['dbstatus_vcr_disc'] ) and ( $vcr['reclink_status'] == $jconf['dbstatus_vcr_recording'] ) ) {

			// TCS: disconnect call
			$err = array();
			$err = TCS_Disconnect($vcr);
			if ( $err['code'] ) {
				$global_log .= "VCR call disconnected:\n\n" . $err['message'] . "\n\n";
				log_recording_conversion($vcr['id'], $myjobid, $jconf['dbstatus_init'], "[OK] VCR call disconnected. Call info:\n\n" . $err['message'], "-", "-", 0, FALSE);
				update_db_vcr_reclink_status($vcr['reclink_id'], $jconf['dbstatus_vcr_ready']);
				update_db_vcr_reclink_params($vcr['reclink_id'], null);
				update_db_stream_status($vcr['id'], $jconf['dbstatus_vcr_upload']);
			} else {
				log_recording_conversion($vcr['id'], $myjobid, $jconf['dbstatus_vcr_discing'], "[ERROR] VCR call cannot be disconnected. Check recording link! Info:\n\n" . $err['message'], "-", "-", 0, TRUE);
				break;
			}

			// Summary log entry and mail
			$total_duration = time() - $start_time;
			$hms = secs2hms($total_duration);
			log_recording_conversion($vcr['id'], $myjobid, $jconf['dbstatus_vcr_ready'], "-", "[OK] Successfuly disconnected recording in " . $hms . " time.\n\nSummary:\n\n" . $global_log, "-", "-", $total_duration, TRUE);

			$app->watchdog();
		}

		break;
	}	// End of while(1)

	// UPLOAD: a finnished recording needs to be uploaded
	$err = array();
	$vcr_upload = array();
	$vcr_user = array();

	if ( query_vcrupload($vcr_upload, $vcr_user) ) {

//	var_dump($vcr_upload);

		if ( $vcr_upload['status'] == $jconf['dbstatus_vcr_upload'] ) {

		// Connect to Cisco TCS
		$soap_rs = TCS_Connect();
		if ( $soap_rs === FALSE ) {
			$sleep_length = 15 * 60;
			break;
		}

		$err = TCS_GetConfInfo($vcr_upload);
//echo "UPLOAD\n";
// get and check download url. download mp4 with wget?????

//var_dump($err);

//exit;

		}

	}

	// Close DB connection if open
	if ( $db_close ) {
		$db->close();
	}

	$app->watchdog();

	sleep( $sleep_length );
	
}	// End of outer while

exit;

function TCS_Connect() {
global $jconf;

	// SOAP related URLs
	$vcr_wsdl = "http://" . $jconf['vcr_server'] . "/tcs/Helium.wsdl";
	$vcr_api_url = "http://" . $jconf['vcr_server'] . "/tcs/SoapServer.php";

	// TCS: SOAP connection to Cisco TCS
	$soapOptions = array(
		'location'                  => $vcr_api_url,
		'authentication'            => SOAP_AUTHENTICATION_DIGEST,
		'login'                     => $jconf['vcr_user'],
		'password'                  => $jconf['vcr_password'],
		'connection_timeout'        => 10
	);

	try {
		$soap_rs = new SoapClient($vcr_wsdl, $soapOptions);
	} catch (exception $err) {
		log_recording_conversion(0, $jconf['jobid_vcr_control'], $jconf['dbstatus_init'], "[ERROR] Cannot connect to SOAP client. Please check.", print_r($soapOptions, TRUE), $err, 0, TRUE);
		return FALSE;
	}

	return $soap_rs;
}


// *************************************************************************
// *						function query_nextjob()					   *
// *************************************************************************
// Description: queries next job from database recording_elements table
// INPUTS:
//	- AdoDB DB link in $db global variable
// OUTPUTS:
//	- Boolean:
//	  o FALSE: no pending job for conversion
//	  o TRUE: job is available for conversion
//	- $recording: recording_element DB record returned in global $recording variable
function query_vcrnew(&$vcr, &$vcr_user) {
global $jconf, $db;

	$query = "
		SELECT
			a.id,
			a.livefeedid,
			a.name,
			a.status,
			a.recordinglinkid,
			b.id as reclink_id,
			b.name as reclink_name,
			b.organizationid,
			b.calltype,
			b.number,
			b.password,
			b.bitrate,
			b.alias,
			b.aliassecure,
			b.status as reclink_status,
			b.conferenceid as conf_id,
			c.id as feed_id,
			c.userid,
			c.channelid,
			c.name as feed_name,
			c.issecurestreamingforced
		FROM
			livefeed_streams as a,
			recording_links as b,
			livefeeds as c
		WHERE
			( ( a.status = '" . $jconf['dbstatus_vcr_start'] . "' AND b.status = '" . $jconf['dbstatus_vcr_ready'] . "') OR
			  ( a.status = '" . $jconf['dbstatus_vcr_disc'] . "' AND b.status = '" . $jconf['dbstatus_vcr_recording'] . "') OR
			  ( a.status = '" . $jconf['dbstatus_vcr_recording'] . "' AND b.status = '" . $jconf['dbstatus_vcr_recording'] . "') ) AND
			a.recordinglinkid = b.id AND
			b.disabled = 0 AND
			a.livefeedid = c.id
		ORDER BY
			id
		LIMIT 1";
// LIMIT 1???? Mi lesz ha tobb stream tartozik egy felvetelhez? TODO

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		log_recording_conversion(0, $jconf['jobid_vcr_control'], $jconf['dbstatus_init'], "[ERROR] Cannot query next VCR job. SQL query failed.", trim($query), $err, 0, TRUE);
		return FALSE;
	}

	// Check if pending job exsits
	if ( $rs->RecordCount() < 1 ) {
		return FALSE;
	}

	$vcr = $rs->fields;

	$query = "
		SELECT
			id,
			nickname,
			email,
			language,
			organizationid
		FROM
			users
		WHERE
			id = " . $vcr['userid'];

	try {
		$rs2 = $db->Execute($query);
	} catch (exception $err) {
		log_recording_conversion(0, $jconf['jobid_vcr_control'], $jconf['dbstatus_init'], "[ERROR] Cannot query user to VCR. SQL query failed.", trim($query), $err, 0, TRUE);
		return FALSE;
	}

	// Check if user exsits to media
	if ( $rs2->RecordCount() < 1 ) {
		return FALSE;
	}

	$vcr_user = $rs2->fields;

	return TRUE;
}

// Query VCR recording to upload
function query_vcrupload(&$vcr_upload, &$vcr_user) {
global $jconf, $db;

	$query = "
		SELECT
			a.id,
			a.livefeedid,
			a.name,
			a.status,
			a.vcrconferenceid as conf_id,
			a.recordinglinkid,
			b.id as feed_id,
			b.userid,
			b.channelid,
			b.name as feed_name
		FROM
			livefeed_streams as a,
			livefeeds as b
		WHERE
			a.status = '" . $jconf['dbstatus_vcr_upload'] . "' AND
			a.livefeedid = b.id
		ORDER BY
			id
		LIMIT 1";

//echo $query . "\n";

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		log_recording_conversion(0, $jconf['jobid_vcr_control'], $jconf['dbstatus_init'], "[ERROR] Cannot query next VCR recording to upload. SQL query failed.", trim($query), $err, 0, TRUE);
		return FALSE;
	}

	// Check if pending job exsits
	if ( $rs->RecordCount() < 1 ) {
		return FALSE;
	}

	$vcr_upload = $rs->fields;

	$query = "
		SELECT
			id,
			nickname,
			email,
			language,
			organizationid
		FROM
			users
		WHERE
			id = " . $vcr_upload['userid'];

	try {
		$rs2 = $db->Execute($query);
	} catch (exception $err) {
		log_recording_conversion(0, $jconf['jobid_vcr_control'], $jconf['dbstatus_init'], "[ERROR] Cannot query user to VCR. SQL query failed.", trim($query), $err, 0, TRUE);
		return FALSE;
	}

	// Check if user exsits to media
	if ( $rs2->RecordCount() < 1 ) {
		return FALSE;
	}

	$vcr_user = $rs2->fields;

	return TRUE;
}

function TCS_ReserveConfId($vcr) {
global $soap_rs;

// Parameters:
//  password (password for the conference - string). Set password as an empty string for no conference password, this field is limited to 20 characters.
//  startDateTime (start date of the recording using GNU date formats - string). Setting the startDateTime to 0 means that the call will begin immediately.
//  duration (duration of call in seconds - integer). Setting a 0 duration will make the length of the call unlimited.
//  title (the title that will appear in the Content Library - string)
//  groupId (the GUID of the conference’s group, if it is recurring - string). The groupId field needs to be either unset, empty string, or a well formed GUID.
//  isRecurring (indicates whether the conference is recurring - bool)

	$conf = array(
		'owner'				=> 'admin',
		'password'			=> $vcr['password'],
		'startDateTime'		=> 0,
		'duration'			=> 0,
		'title'				=> $vcr['feed_name'],
		'groupID'			=> '',
		'isRecurring'		=> false
    );

    $result = $soap_rs->RequestConferenceID($conf);
    $conf_id = $result->RequestConferenceIDResult;

// ERROR?

    return $conf_id;
}

function TCS_Dial($vcr) {
global $soap_rs, $jconf;

	$err = array();

	// Recording link: update status
	update_db_vcr_reclink_status($vcr['reclink_id'], $jconf['dbstatus_vcr_starting']);

// Parameters:
//  Number (the number to dial - string)
//  Bitrate (the desired bandwidth - string). Must be: 64, 128, 192, 256, 384, 512, 768, 1024, 1280, 1536, 1920 and 2048 kbps (as well as 2560, 3072 and 4000 kbps for Content Servers equipped with the Premium Resolution option).
//  ConferenceID (ConferenceID to be used for this call - string)
//  Alias (alias to use – specifies call settings - string)
//  CallType (protocol for the call – optional (“sip”, “h323”) – string). If CallType is equal to sip then the address to call will be prefixed with sip:, otherwise H.323 is assumed.
//  SetMetadata (inherit conference metadata from the alias – boolean)
//  PIN (MCU conference PIN if required – string)

	$conf = array(
		'ConferenceID'		=> $vcr['conf_id'],
		'Number'			=> $vcr['number'],
		'CallType'			=> $vcr['calltype'],
		'Alias'				=> $vcr['aliasused'],
		'Bitrate'			=> $vcr['bitrate'],
		'SetMetadata'		=> false
	);

	$err['command'] = "Dial():\n\n" . print_r($conf, TRUE);
	$result = $soap_rs->Dial($conf);

//echo "DIALING...\n";
//var_dump($result);

	if ( $result->DialResult->ErrorCode != 0 ) {
		$err['code'] = FALSE;
		$err['message'] = "[ERROR] VCR fatal error. Dial() call returned error. Error message:\n\n". $result->DialResult->Error;
		return $err;
	}

	// Sleep short to wait for call setup
	sleep(2);

	$err_ci = array();
	$err_ci['message'] = "INITIALISING_CALL";

	// Querying call status
	$i = 1;
	while ( $err_ci['message'] != "IN_CALL" ) {

		// Get call status
		$err_ci = TCS_CallInfo($vcr);

		// Call established, exit from loop
		if ( $err_ci['result'] == "IN_CALL" ) {
			break;
		}

		// Something unexpected
		if ( $err_ci['result'] == "NOT_IN_CALL" ) {
			$err['code'] = FALSE;
			$err['result'] = $err_ci['result'];
			$err['message'] = "[ERROR] VCR call init returned an error. Call target:\n\n" . print_r($conf, TRUE) . "\n\nCall state: " . $err_ci['message'];
			return $err;
		}

		// Check 25 seconds of timeout
		if ( $i > 5 ) {
			$err['code'] = FALSE;
			$err['result'] = $err_ci['result'];
			$err['message'] = "[ERROR] VCR call cannot be established in " . $i * 5 . " seconds. Call target:\n\n" . print_r($conf, TRUE) . "\n\nCall state:\n\n" . $err_ci['message'];
			return $err;
		}

		// Wait some longer to leave some time to establish call
		sleep(5);

		$i++;
	}

	$err['code'] = TRUE;
	$err['result'] = $err_ci['result'];
	$err['message'] = $err_ci['message'];
	return $err;
}

function TCS_CallInfo($vcr) {
global $soap_rs, $jconf;

	$err = array();

	$conf = array(
		'ConferenceID'  => $vcr['conf_id']
	);

	$err['command'] = "GetCallInfo():\n\n" . print_r($conf, TRUE);

	$result = $soap_rs->GetCallInfo($conf);
	$callinfo = $result->GetCallInfoResult;
	$err['result'] = $callinfo->CallState;
	$err['data']['callstate'] = $callinfo->CallState;
	$err['data']['mediastate'] = $callinfo->MediaState;
	$err['data']['writerstatus'] = $callinfo->WriterStatus;

//echo "GETINGCALLINFO...\n";
//var_dump($result);

	// Unknown conference ID, fatal error
	if ( $callinfo->CallState == "NOT_IN_CALL" ) {
		$err['code'] = FALSE;
		$err['message'] = "[ERROR] VCR not in call.";
		return $err;
	}

	// Dialing in progress
	if ( $callinfo->CallState == "INITIALISING_CALL" ) {
		$err['code'] = TRUE;
		$err['message'] = "[ERROR] VCR is establishing a call.";
		return $err;
	}

	// Disconnect in progress
	if ( $callinfo->CallState == "ENDING_CALL" ) {
		$err['code'] = TRUE;
		$err['message'] = "[ERROR] VCR is disconnecting a call.";
		return $err;
	}

	// Undefined error
	if ( $callinfo->CallState != "IN_CALL" ) {
		$err['code'] = FALSE;
		$err['message'] = "[ERROR] VCR call is in undefined state.";
		log_recording_conversion(0, $jconf['jobid_vcr_control'], $jconf['dbstatus_init'], "[ERROR] Undefined error in setting up call.", $err['command'], $err['message'], 0, TRUE);
		return $err;
	}

	// Call in progress
	$callinfo_log  = "Remote endpoint: " . $callinfo->RemoteEndpoint . "\n";
	$callinfo_log .= "Call rate: " . $callinfo->CallRate . "\n";
	$callinfo_log .= "Alias: " . $callinfo->RecordingAliasName . "\n";
	$callinfo_log .= "Media state: " . $callinfo->MediaState . "\n";
	$callinfo_log .= "Status: " . $callinfo->WriterStatus . "\n";
	$callinfo_log .= "Audio codec: " . $callinfo->AudioCodec . "\n";
	$callinfo_log .= "Video codec: " . $callinfo->VideoCodec . "\n";

	if ( ( $callinfo->MediaState != "RECORDING" ) and ( $callinfo->WriterStatus != "OK" ) ) {
		// WriterStatus == FAILED? (tele a HDD?) Mas hiba?
		$err['code'] = FALSE;
		$err['message'] = "[ERROR] Undefined error in recording. Call info:\n\n" . $callinfo_log;
		return $err;
	}

	$err['code'] = TRUE;
	$err['message'] = "[OK] VCS call established. Call info:\n\n" . $callinfo_log;

	return $err;
}

function TCS_GetConfInfo(&$vcr) {
global $soap_rs;

	$err = array();

	$conf = array(
		'ConferenceID'  => $vcr['conf_id']
	);

	$err['command'] = "GetConference():\n\n" . print_r($conf, TRUE);
	$result = $soap_rs->GetConference($conf);

//echo "GET INFO\n";
//var_dump($result);

	$err_su = TCS_GetStreamParams($result);
	if ( $err_su['code'] ) {

		// Calculate aspect ratio
		$gcd = GCD($err_su['data']['width'], $err_su['data']['height']);
		$x = $err_su['data']['width'] / $gcd;
		$y = $err_su['data']['height'] / $gcd;
		$vcr['aspectratio'] = $x . ":" . $y;

		// Return stream paramters
		$vcr['mainurl'] = $err_su['data']['mainurl'];
		$vcr['rtmp_server'] = $err_su['data']['rtmp_server'];
		$vcr['rtmp_streamid'] = $err_su['data']['rtmp_streamid'];
		$vcr['width'] = $err_su['data']['width'];
		$vcr['height'] = $err_su['data']['height'];
	} else {
		$err['code'] = FALSE;
		$err['message'] = $err_su['message'];
		return $err;
	}

	$err['code'] = TRUE;
	$err['message'] = "[OK] VCR conference info: " . print_r($err_su['data'], TRUE);
    return $err;
}

// Get RTMP URL
function TCS_GetStreamParams($conf_info) {

	$err = array();

	$is_flashver = FALSE;

	$HasWatchableMovie = $conf_info->GetConferenceResult->HasWatchableMovie;
	if ( $HasWatchableMovie ) {
		$WatchableMovie = $conf_info->GetConferenceResult->WatchableMovies->ArrayOfWatchableMovie->WatchableMovie;

		$num = count($WatchableMovie);

		// Fatal error, no URL is provided by TCS
		if ( $num == 0 ) {
			$err['code'] = FALSE;
			$err['message'] = "[ERROR] VCR does not have watchable video streams.";
			return $err;
		}

// Large?
		if ( $num == 1 ) {
			if ( ( $WatchableMovie->Format == "Flash" ) and ( $WatchableMovie->Quality == "Large" ) ) {
				$err['data']['mainurl'] = $WatchableMovie->MainURL;
				$err['data']['format'] = "flash";
				$err['data']['quality'] = "large";
				$err['data']['bitrate'] = $WatchableMovie->TotalBandwidth;
				$err['data']['width'] = $WatchableMovie->MainWidth;
				$err['data']['height'] = $WatchableMovie->MainHeight;
				$is_flashver = TRUE;
			}
		}

		if ( $num > 1 ) {
			for ( $i = 0; $i < $num; $i++) {
				if ( ( $WatchableMovie[$i]->Format == "Flash" ) and ( $WatchableMovie[$i]->Quality == "Large" ) ) {
					$err['data']['mainurl'] = $WatchableMovie[$i]->MainURL;
					$err['data']['format'] = "flash";
					$err['data']['quality'] = "large";
					$err['data']['bitrate'] = $WatchableMovie[$i]->TotalBandwidth;
					$err['data']['width'] = $WatchableMovie[$i]->MainWidth;
					$err['data']['height'] = $WatchableMovie[$i]->MainHeight;
					$is_flashver = TRUE;
					break;
				}
			}
		}

	}

	if ( $is_flashver ) {

		// Check if live or recording for download
		if ( strpos($err['data']['mainurl'], "rtmp") === FALSE ) {
			$err['data']['download_url'] = $err['data']['mainurl'];
			$err['data']['rtmp_server'] = "";
			$err['data']['rtmp_streamid'] = "";
		} else {
			// Get Wowza RTMP stream ID
			$tmp = explode("mp4:", $err['data']['mainurl'], 2);
			$err['data']['rtmp_server'] = $tmp[0];
			$err['data']['rtmp_streamid'] = $tmp[1];
		}

		$err['code'] = TRUE;
		$err['message'] = "[OK] VCR streaming URL is available.";
	} else {
		$err['code'] = FALSE;
		$err['message'] = "[ERROR] VCR streaming URL is not available.";
	}

	return $err;
}

function TCS_Disconnect($vcr) {
global $soap_rs;

	$err = array();

	$conf = array(
		'ConferenceID'  => $vcr['conf_id']
	);

	$err['command'] = "DisconnectCall():\n\n" . print_r($conf, TRUE);
	$result = $soap_rs->DisconnectCall($conf);

//echo "DISCONNECT:\n";
//var_dump($result);

    if ( $result->DisconnectCallResult->Error != 0 ) {
		$err['code'] = FALSE;
		$err['message'] = "[ERROR] VCR is unable to disconnect call for conference " . $vcr['conf_id'] . ". Error code: " . $result->DisconnectCallResult->ErrorCode;
		return $err;
	}

	$err['code'] = TRUE;
	$err['message'] = "[OK] VCR call " . $vcr['conf_id'] . " disconnected.";

	return $err;
}

function TCS_GetSystemHealth() {
global $soap_rs;

	$err = array();

	$result = $soap_rs->GetSystemHealth();

//var_dump($result);

	$syshealth = $result->GetSystemHealthResult;

	$err['code'] = TRUE;
	$err['message'] = "";

	if ( !$syshealth->EngineOK ) {
		$err['code'] = FALSE;
		$err['message'] .= "VCR engine error. Check system!\n";
	}

	if ( !$syshealth->LibraryOK ) {
		$err['code'] = FALSE;
		$err['message'] .= "VCR library error. Check system!\n";
	}

	if ( !$syshealth->DatabaseOK ) {
		$err['code'] = FALSE;
		$err['message'] .= "VCR database error. Check system!\n";
	}

	return $err;
}

function TCS_GetSystemInformation() {
global $soap_rs;

	$err = array();

	$result = $soap_rs->GetSystemInformation();

//var_dump($result);

/*
  ["GetSystemInformationResult"]=>
  object(stdClass)#6 (15) {
    ["ProductID"]=>
    string(23) "TANDBERG Content Server"
    ["VersionMajor"]=>
    int(5)
    ["VersionMinor"]=>
    int(3)
    ["ReleaseType"]=>
    string(0) ""
    ["ReleaseNumber"]=>
    int(0)
    ["BuildNumber"]=>
    int(3316)
    ["IPAddress"]=>
    string(13) "91.120.59.239"
    ["SerialNumber"]=>
    string(8) "49A21925"
    ["MaxCallOptions"]=>
    int(5)
    ["MaxLiveCallOptions"]=>
    int(2)
    ["EngineOK"]=>
    bool(true)
*/

	return $err;
}

function TCS_GetCallCapacity() {
global $soap_rs;

	$err = array();

	$result = $soap_rs->GetCallCapacity();

	$err['code'] = TRUE;

	$err['data']['maxcalls'] = $result->GetCallCapacityResult->MaxCalls;
	$err['data']['currentcalls'] = $result->GetCallCapacityResult->CurrentCalls;
	$err['data']['maxlivecalls'] = $result->GetCallCapacityResult->MaxLiveCalls;
	$err['data']['currentlivecalls'] = $result->GetCallCapacityResult->CurrentLiveCalls;

	$err['message']  = "";
	$err['message'] .= "Max calls: " . $err['data']['maxcalls'] . "\n";
	$err['message'] .= "Current calls: " . $err['data']['currentcalls'] . "\n";
	$err['message'] .= "Max live calls: " . $err['data']['maxlivecalls'] . "\n";
	$err['message'] .= "Current live calls: " . $err['data']['currentlivecalls'];

//var_dump($result);
//echo $err['message'] . "\n";

	return $err;
}


?>