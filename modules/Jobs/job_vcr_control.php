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

// SOAP related URLs
$vcr_wsdl = "http://" . $jconf['vcr_server'] . "/tcs/Helium.wsdl";
$vcr_api_url = "http://" . $jconf['vcr_server'] . "/tcs/SoapServer.php";

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

		// Initialize log for closing message and total duration timer
		$global_log = "";
		$total_duration = time();

		$vcr = array();
		$vcr_user = array();

// !!! 
update_db_stream_status(4, $jconf['dbstatus_vcr_start']);
update_db_vcr_reclink_status(2, $jconf['dbstatus_vcr_ready']);
// !!!

		// Query next job - exit if none
		if ( !query_vcrnew($vcr, $vcr_user) ) break;

		// Starting recording
		update_db_stream_status($vcr['id'], $jconf['dbstatus_vcr_starting']);

// TODO: start, disconnect
// TODO: status and stream update fuctions

		// Start global log
		$global_log .= "Live feed: " . $vcr['feed_name'] . " (ID = " . $vcr['feed_id'] . ")\n";
		$global_log .= "Stream: " . $vcr['name'] . " (ID = " . $vcr['id'] . ")\n";
		$global_log .= "User: " . $vcr_user['nickname'] . " (" . $vcr_user['email'] . ")\n";
		$global_log .= "Recording link: " . $vcr['reclink_name'] . " (ID = " . $vcr['reclink_id'] . ")\n";
		$global_log .= "Call: " . $vcr['calltype'] . ":" . $vcr['number'] . " @ " . $vcr['bitrate'] . "KBps\n";
		$global_log .= "Recording profile: " . $vcr['alias'] . "\n\n";

		// Start log entry
		log_recording_conversion($vcr['id'], $myjobid, $jconf['dbstatus_init'], "START Videoconference recording:\n" . $global_log, "-", "-", 0, FALSE);

// ------------------------------------------------------------------------------------

		$soapOptions = array(
			'location'                  => $vcr_api_url,
			'authentication'            => SOAP_AUTHENTICATION_DIGEST,
			'login'                     => $jconf['vcr_user'],
			'password'                  => $jconf['vcr_password'],
			'connection_timeout'        => 10
		);

		// TCS: establish SOAP connection
		try {
			$soap_rs = new SoapClient($vcr_wsdl, $soapOptions);
		} catch (exception $err) {
			log_recording_conversion(0, $jconf['jobid_vcr_control'], $jconf['dbstatus_init'], "[ERROR] Cannot connect to SOAP client. Please check.", print_r($soapOptions), $err, 0, TRUE);
			$sleep_length = 15 * 60;
			break;
		}

//$result = $soap_rs->GetStatus();
//var_dump($result);

		// TCS: reserve ConferenceID
		$vcr['conf_id'] = tcs_reserve_conf_id($vcr);

//var_dump($vcr);

		// TCS: dial recording link to start recording session
		$err = tcs_dial($vcr);
		if ( $err['code'] ) {
			$global_log .= $err['message'] . "\n\n";
			log_recording_conversion($vcr['id'], $myjobid, $jconf['dbstatus_init'], $err['message'], "-", "-", 0, FALSE);
			update_db_vcr_reclink_status($vcr['reclink_id'], $jconf['dbstatus_vcr_recording']);
			update_db_stream_status($vcr['id'], $jconf['dbstatus_vcr_recording']);
		} else {
			log_recording_conversion($vcr['id'], $myjobid, $jconf['dbstatus_init'], "[ERROR] VCR call cannot be established. Info:\n\n" . $err['message'], "-", "-", 0, TRUE);
			break;
		}

sleep(10);

// Nehany percenkent chekkolni?
	$err = tcs_getconfinfo($vcr);

sleep(60);

		$err = tcs_disconnect($vcr);
		if ( $err['code'] ) {
			$global_log .= "VCR call disconnected:\n\n" . $err['message'] . "\n\n";
			log_recording_conversion($vcr['id'], $myjobid, $jconf['dbstatus_init'], "[OK] VCR call disconnected. Call info:\n\n" . $err['message'], "-", "-", 0, FALSE);
			update_db_vcr_reclink_status($vcr['reclink_id'], $jconf['dbstatus_vcr_ready']);
			update_db_stream_status($vcr['id'], $jconf['dbstatus_vcr_upload']);
		} else {
			log_recording_conversion($vcr['id'], $myjobid, $jconf['dbstatus_init'], "[ERROR] VCR call cannot be disconnected. Info:\n\n" . $err['message'], "-", "-", 0, TRUE);
			break;
		}


echo $global_log;

exit;

		break;
	}	// End of while(1)

	// Close DB connection if open
	if ( $db_close ) {
		$db->close();
	}

	$app->watchdog();

	sleep( $sleep_length );
	
}	// End of outer while

exit;


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
			b.status as reclink_status,
			c.id as feed_id,
			c.userid,
			c.channelid,
			c.name as feed_name
		FROM
			livefeed_streams as a,
			recording_links as b,
			livefeeds as c
		WHERE
			a.status = '" . $jconf['dbstatus_vcr_start'] . "' AND
			a.recordinglinkid = b.id AND
			b.disabled = 0 AND
			b.status = '" . $jconf['dbstatus_vcr_ready'] . "' AND
			a.livefeedid = c.id
		ORDER BY
			id
		LIMIT 1";
// LIMIT 1???? Mi van ha tobb stream tartozik egy felvetelhez? TODO

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

function tcs_reserve_conf_id($vcr) {
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

echo "RESERVE:\n";
var_dump($result);

// ERROR?

    return $conf_id;
}

function tcs_dial($vcr) {
global $soap_rs;

	$err = array();

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
		'Alias'				=> $vcr['alias'],
		'Bitrate'			=> $vcr['bitrate'],
		'SetMetadata'		=> false
	);

//var_dump($conf);
	$err['command'] = "Dial():\n\n" . print_r($conf);
	$result = $soap_rs->Dial($conf);

echo "DIAL:\n";
var_dump($result);

	if ( $result->DialResult->ErrorCode != 0 ) {
		$err['code'] = FALSE;
		$err['message'] = "[ERROR] VCR fatal error. Dial() call returned error. Error message:\n\n". $result->DialResult->Error;
		return $err;
	}

	// Sleep short
echo "alszunk(2)\n";
	sleep(2);

	$err_ci = array();
	$err_ci['message'] = "INITIALISING_CALL";

	// Querying call status
	$i = 1;
	while ( $err_ci['message'] != "IN_CALL" ) {

		// Get call status
		$err_ci = tcs_callinfo($vcr);

echo "STAT: " . $err_ci['message'] . "\n";

		// Exit loop if call established
//		if ( $err_ci['code'] ) {
//			break;
//		}

		// Call established, exit from loop
		if ( $err_ci['result'] == "IN_CALL" ) {
			$err['code'] = TRUE;
			$err['result'] = $err_ci['result'];
			$err['message'] = "[OK] VCR call established.";
			return $err;
		}

		// Something unexpected
		if ( $err_ci['result'] == "NOT_IN_CALL" ) {
			$err['code'] = FALSE;
			$err['result'] = $err_ci['result'];
			$err['message'] = "[ERROR] VCR call init returned an error. Call state: " . $err_ci['message'];
			return $err;
		}

		// Check 25 seconds of timeout
		if ( $i > 5 ) {
			$err['code'] = FALSE;
			$err['result'] = $err_ci['result'];
			$err['message'] = "[ERROR] VCR call cannot be established in " . $i * 5 . " seconds. Call state:\n\n" . $err_ci['message'];
			break;
		}

		// Wait some longer to leave some time to establish call
		sleep(5);

		$i++;
	}

//var_dump($err);

	$err['code'] = TRUE;
	$err['message'] = $err_ci['message'];
	return $err;
}

function tcs_callinfo($vcr) {
global $soap_rs, $jconf;

	$err = array();

	$conf = array(
		'ConferenceID'  => $vcr['conf_id']
	);

	$err['command'] = "GetCallInfo():\n\n" . print_r($conf);

	$result = $soap_rs->GetCallInfo($conf);
	$callinfo = $result->GetCallInfoResult;
	$err['result'] = $callinfo->CallState;

//NOT_IN_CALL
//IN_CALL
//INITIALISING_CALL
//ENDING_CALL

echo "************************ GETCALLINFO:\n";
var_dump($result);

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
		log_recording_conversion(0, $jconf['jobid_vcr_control'], $jconf['dbstatus_init'], $err['message'], $err['command'], $err['message'], 0, TRUE);
		return $err;
	}

	$err['code'] = TRUE;
	$err['message'] = "[OK] VCS call established. Call info:\n\n" . $callinfo_log;

	return $err;
}

function tcs_getconfinfo($vcr) {
global $soap_rs;

    echo "GET INFO\n";

	$err = array();

	$conf = array(
		'ConferenceID'  => $vcr['conf_id']
	);

	$err['command'] = "GetConference():\n\n" . print_r($conf);
	$result = $soap_rs->GetConference($conf);

var_dump($result);

	$err_su = tcs_getstreamurl($result);
	if ( $err_su['code'] ) {
// live streaming information
		print_r($err_su['data']);
	} else {
echo $err_su['message'];
	}

	$err['code'] = TRUE;
    return $err;
}

// Get RTMP URL
function tcs_getstreamurl($conf_info) {

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
				$err['data']['rtmp_url'] = $WatchableMovie->MainURL;
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
					$err['data']['rtmp_url'] = $WatchableMovie[$i]->MainURL;
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
		$err['code'] = TRUE;
		$err['message'] = "[OK] VCR streaming URL is available.";
	} else {
		$err['code'] = FALSE;
		$err['message'] = "[ERROR] VCR streaming URL is not available.";
	}

	return $err;
}

function tcs_disconnect($vcr) {
global $soap_rs;

    echo "DISCONNECT\n";

	$err = array();

	$conf = array(
		'ConferenceID'  => $vcr['conf_id']
	);

	$err['command'] = "DisconnectCall():\n\n" . print_r($conf);
	$result = $soap_rs->DisconnectCall($conf);

	var_dump($result);

    if ( $result->DisconnectCallResult->Error != 0 ) {
		$err['code'] = FALSE;
		$err['message'] = "[ERROR] VCR is unable to disconnect call for conference " . $vcr['conf_id'] . ". Error code: " . $result->DisconnectCallResult->ErrorCode;
		return $err;
	}

	$err['code'] = TRUE;
	$err['message'] = "[OK] VCR call " . $vcr['conf_id'] . " disconnected.";

	return $err;
}

?>