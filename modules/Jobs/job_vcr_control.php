<?php
// VCR control job 2015/02/10

define('BASE_PATH',	realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

@include("SOAP/Client.php");

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');
include_once( BASE_PATH . 'resources/apitest/httpapi.php');

// Utils
include_once('job_utils_base.php');
include_once('job_utils_log.php');
include_once('job_utils_status.php');

set_time_limit(0);

// Init
$app = new Springboard\Application\Cli(BASE_PATH, false);

// Load jobs configuration file
$app->loadConfig('modules/Jobs/config_jobs.php');
$jconf = $app->config['config_jobs'];
$myjobid = $jconf['jobid_vcr_control'];

// Log related init
$debug = Springboard\Debug::getInstance();
$debug->log($jconf['log_dir'], $myjobid . ".log", "*************************** Job: VCR Control ***************************" ."\n", $sendmail = false);

// Check operating system - exit if Windows
if ( iswindows() ) {
	echo "ERROR: Non-Windows process started on Windows platform\n";
	exit;
}

// Start an infinite loop - exit if any STOP file appears
while( !is_file( $app->config['datapath'] . 'jobs/job_vcr_control.stop' ) and !is_file( $app->config['datapath'] . 'jobs/all.stop' ) ) {

	clearstatcache();

	$app->watchdog();

	$tcs_isconnected = false;
	$sleep_length = $app->config['sleep_vcr'];

	// Establish database connection
	$db = db_maintain();

	// VCR: start, maintain and stop recording
    while ( 1 ) {

		// Initialize log for closing message and total duration timer
		$global_log = "";
		$start_time = time();

		$vcr = array();
		$vcr_user = array();

		// Query next job - exit if none
		if ( !query_vcrrecording($vcr, $vcr_user) ) break;

		// Alias: choose normal or secure
		$vcr['aliasused'] = $vcr['alias'];
		if ( $vcr['issecurestreamingforced'] == 1 ) $vcr['aliasused'] = $vcr['aliassecure'];

		// Start global log
		$global_log .= "Live feed: " . $vcr['feed_name'] . " (ID = " . $vcr['feed_id'] . ")\n";
		$global_log .= "Stream: " . $vcr['name'] . " (ID = " . $vcr['id'] . ")\n";
		$global_log .= "User: " . $vcr_user['nickname'] . " (" . $vcr_user['email'] . ")\n";
		$global_log .= "Recording link: " . $vcr['reclink_name'] . " (ID = " . $vcr['reclink_id'] . ")\n";
		$global_log .= "Call: " . $vcr['calltype'] . ":" . $vcr['number'] . " @ " . $vcr['bitrate'] . "KBps\n";
		$global_log .= "Recording profile: " . $vcr['aliasused'] . "\n\n";

		$app->watchdog();

		// Connect to Cisco TCS
		$tcs_isconnected = false;
		$soap_rs = TCS_Connect();
		if ( $soap_rs === false ) {
			$sleep_length = 15 * 60;
			break;
		} 
		$tcs_isconnected = true;

		// System health: check
		$err = array();
		$err = TCS_GetSystemHealth();
		if ( !$err['code'] ) {
            $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] VCR system health problem.\n\n" . print_r($err['message'], true), $sendmail = true);
			break;
		}

		// TODO: error handling, DB logging
		$err = array();
		$err = TCS_GetSystemInformation();

		$app->watchdog();

		// START RECORDING: a recording needs to be started, recording link is available
		if ( ( $vcr['status'] == $jconf['dbstatus_vcr_start'] ) and ( $vcr['reclink_status'] == $jconf['dbstatus_vcr_ready'] ) ) {

			// TCS: Check system capacity
			$err = array();
			$err = TCS_GetCallCapacity();
			if ( $err['data']['maxcalls'] == $err['data']['currentcalls'] ) {
                $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] VCR call capacity reached. Cannot start a new recording. Info:\n\n" . $err['message'], $sendmail = true);
				break;
			}
			if ( $err['data']['maxlivecalls'] == $err['data']['currentlivecalls'] ) {
                $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] VCR live call capacity reached. Cannot start a new recording. Info:\n\n" . $err['message'], $sendmail = true);
				break;
			}

			// Starting recording
			updateLiveStreamStatus($vcr['id'], $jconf['dbstatus_vcr_starting']);

			// TCS: reserve ConferenceID
			$err = array();
			$err = TCS_ReserveConfId($vcr);
			if ( !$err['code'] ) {
				$global_log .= $err['message'];
                $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] VCR cannot reserve Conference ID. More info:\n\n" . $err['message'], $sendmail = true);
			}
			$vcr['conf_id'] = $err['data']['conf_id'];

			// TCS: dial recording link to start recording session
			$err = array();
			$err = TCS_Dial($vcr);
			if ( $err['code'] ) {
				$global_log .= $err['message'];
                $debug->log($jconf['log_dir'], $myjobid . ".log", $err['message'], $sendmail = false);
				// Recording link: indicate recording status
				updateVCRReclinkStatus($vcr['reclink_id'], $jconf['dbstatus_vcr_recording']);
			} else {
                $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] VCR call cannot be established. Info:\n\n" . $err['message'], $sendmail = true);
				break;
			}

			// Get live streaming URL for recording started. Try 3x times, then exit with error.
			$is_streamready = false;
			$num_trials = 3;
			while ( ( $is_streamready === false ) and ( $num_trials > 0 ) ) {

				// Wait for TCS 5 sec countdown
				sleep(10);

				// Get conference info
				$err = array();
				$err = TCS_GetConfInfo($vcr);

				if ( $err['code'] and !empty($vcr['rtmp_streamid']) ) {

					// Update feed: stream ID, aspect ratio, conference ID
					updateVCRLiveStreamParams($vcr['id'], $vcr['rtmp_streamid'], $vcr['conf_id']);
					// Update recording link: recording link with TCS conference ID
					updateVCRReclinkParams($vcr['reclink_id'], $vcr['conf_id']);
					// Update stream status: playable
					updateLiveStreamStatus($vcr['id'], $jconf['dbstatus_vcr_recording']);

					// We have live streaming URL
					$is_streamready = true;
				}

				$num_trials--;
			}

			// Live streaming URL error: cannot determine stream URL
			if ( !$is_streamready ) {
                $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] This recording has no movies to play. Cannot determine live streaming URL. TCS cannot send stream to Wowza? Tried 3x times for 30 seconds. Info:\n\n" . print_r($vcr, true), $sendmail = true);
				// Update stream status: disconnect (call is connected, but live streaming URL was not provided)
				updateLiveStreamStatus($vcr['id'], $jconf['dbstatus_vcr_disc']);
				// Skip uploading this very short recording???
				break;
			}

			// Summary log entry and mail
			$total_duration = time() - $start_time;
			$hms = secs2hms($total_duration);
            $debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] Successful recording initiation in " . $hms . " time.\n\nSummary:\n\n" . $global_log, $sendmail = true);

            // Watchdog
			$app->watchdog();
		}

		// ONGOING RECORDING: check ongoing recording status
		if ( ( $vcr['status'] == $jconf['dbstatus_vcr_recording'] ) and ( $vcr['reclink_status'] == $jconf['dbstatus_vcr_recording'] ) ) {

			$err = array();
			$err = TCS_CallInfo($vcr);
			if ( !$err['code'] ) {

				if ( $err['data']['callstate'] != "IN_CALL" ) {
                    $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] VCR call was disconnected unexpectedly. Info:\n\n" . $err['message'] . "\n\nDump:\n\n" . print_r($vcr, true) . "\n\nData:\n\n" . print_r($err['data'], true), $sendmail = true);
					// Indicate error on stream
					updateLiveStreamStatus($vcr['id'], $jconf['dbstatus_vcr_recording_err']);
					// Permanent error on recording link?
					updateVCRReclinkStatus($vcr['reclink_id'], $jconf['dbstatus_vcr_recording_err']);
					break;
				}

				if ( ( $err['data']['mediastate'] != "RECORDING" ) and ( $err['data']['writerstatus'] != "OK" ) ) {
                    $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Unexpected VCR recording error. Info:\n\n" . $err['message'] . "\n\nDump:\n\n" . print_r($vcr, true) . "\n\nData:\n\n" . print_r($err['data'], true), $sendmail = true);
					// Indicate error on stream
					updateLiveStreamStatus($vcr['id'], $jconf['dbstatus_vcr_recording_err']);
					// Permanent error on recording link?
					updateVCRReclinkStatus($vcr['reclink_id'], $jconf['dbstatus_vcr_recording_err']);
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
                $debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] VCR call disconnected. Call info:\n\n" . $err['message'], $sendmail = false);
				updateVCRReclinkStatus($vcr['reclink_id'], $jconf['dbstatus_vcr_ready']);
				updateVCRReclinkParams($vcr['reclink_id'], null);
				if ( $vcr['needrecording'] == 1 ) {
					updateLiveStreamStatus($vcr['id'], $jconf['dbstatus_vcr_upload']);
				} else {
					updateLiveStreamStatus($vcr['id'], $jconf['dbstatus_vcr_ready']);
				}
			} else {
                $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] VCR call cannot be disconnected. Check recording link! Info:\n\n" . $err['message'] . "\n\nDump:\n\n" . print_r($vcr, true), $sendmail = true);
				break;
			}

			sleep(30);

			// Summary log entry and mail
			$total_duration = time() - $start_time;
			$hms = secs2hms($total_duration);
            $debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] Successfuly disconnected recording in " . $hms . " time.\n\nSummary:\n\n" . $global_log, $sendmail = true);

            // Watchdog
			$app->watchdog();
		}

		break;
	}	// End of while(1)

	// VCR UPLOAD: a finnished recording needs to be uploaded
	while (1) {

        // Watchdog
		$app->watchdog();

		$err = array();
		$vcr_upload = array();
		$vcr_user = array();

		if ( !query_vcrupload($vcr_upload, $vcr_user) ) break;

		// Temporary directory check
		if ( !is_writable($jconf['vcr_dir']) ) {
            $debug->log($jconf['log_dir'], $myjobid . ".log", "[FATAL ERROR] Temp directory " . $jconf['vcr_dir'] . " is not writable. Storage error???", $sendmail = true);
			break;
		}
		// Temporary directory cleanup
		$err = tempdir_cleanup($jconf['vcr_dir']);
		if ( !$err['code'] ) {
            $debug->log($jconf['log_dir'], $myjobid . ".log", $err['message'] . "\n\nCommand:\n\n" . $err['command'] . "\n\nResult: " . $err['result'], $sendmail = true);
			$sleep_length = 15 * 60;
			break;
		}

		// Connect to Cisco TCS if connection is not yet established
		if ( $tcs_isconnected === false ) {
			$soap_rs = TCS_Connect();
			if ( $soap_rs === false ) {
				$sleep_length = 15 * 60;
				break;
			}
			$tcs_isconnected = true;
		}

		// Get TCS recording information for download URL
		$err = TCS_GetConfInfo($vcr_upload);
		if ( !$err['code'] ) {
			if ( $err['code_num'] == -1 ) {
                $debug->log($jconf['log_dir'], $myjobid . ".log", "WARNING: recording is not yet available for download? Try how many times?\n" . $vcr_upload['conf_id'] . ").\n\nVCR info:\n\n" . print_r($vcr_upload, true), $sendmail = true);
			}
			if ( $err['code_num'] == -2 ) {
                $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] VCR download failed. Did not find TCS Conference ID (" . $vcr_upload['conf_id'] . ").VCR info:\n\n" . print_r($vcr_upload, true), $sendmail = true);
				updateLiveStreamStatus($vcr_upload['id'], $jconf['dbstatus_vcr_upload_err']);
				break;
			}
		}

		$app->watchdog();

		// VCR DOWNLOAD: use wget to download MP4 file
		if ( !empty($vcr_upload['download_url']) ) {

			// Update stream status
			updateLiveStreamStatus($vcr_upload['id'], $jconf['dbstatus_vcr_uploading']);

			// Get MP4 filename from URL
			$tmp = parse_url($vcr_upload['download_url']);
			$tmp2 = pathinfo($tmp['path']);
			$filename = $tmp2['basename'];

			// Temporary directory
			$temp_directory = $jconf['vcr_dir'];

			// Download with wget
			$command = "wget -nv -P " . $temp_directory . " " . $vcr_upload['download_url'] . " 2>&1";
			$time_start = time();
			exec($command, $output, $result);
			$output_string = implode("\n", $output);
			$duration = time() - $time_start;
			$mins_taken = round( $duration / 60, 2);
			if ( $result != 0 ) {
                $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] VCR download failed. URL:\n\n" . $vcr_upload['download_url'] . "\n\nInfo:\n\n" . print_r($vcr_upload, true) . "\n\nCommand:\n\n" . $command . "\n\nOutput:\n\n" . $output_string, $sendmail = true);
				updateLiveStreamStatus($vcr_upload['id'], $jconf['dbstatus_vcr_upload_err']);
				break;
			}
	
			$media_filename = $temp_directory . $filename;
			if ( !file_exists($media_filename) ) {
                $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot find downloaded VCR recording. File:\n\n" . $media_filename . "\n\nInfo:\n\n" . print_r($vcr_upload, true), $sendmail = true);
				updateLiveStreamStatus($vcr_upload['id'], $jconf['dbstatus_vcr_upload_err']);
				break;
			}

            $debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] VCR recording download finished (in " . $mins_taken . " mins). URL:\n\n" . $vcr_upload['download_url'], $sendmail = false);

			$app->watchdog();

		} else {
			// VCR download URL is not yet available (we are just after disconnect)
			// Stream status: revert to "upload" (try later)
			updateLiveStreamStatus($vcr_upload['id'], $jconf['dbstatus_vcr_upload']);
            $debug->log($jconf['log_dir'], $myjobid . ".log", "[WARNING] Cannot find VCR download URL. Will be trying later. Info:\n\n" . $print_r($vcr_upload, true), $sendmail = false);
			break;
		}

		// API: initialize an API connection
		try {
			$api = new Api($jconf['api_user'], $jconf['api_password']);
		} catch (exception $err) {
			// Stream status: revert to "upload" (try later)
			updateLiveStreamStatus($vcr_upload['id'], $jconf['dbstatus_vcr_upload']);
            $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot connect to Videosquare API. Will be trying later. User: " .  $jconf['api_user'] . "\n\nError:\n\n" . $err, $sendmail = true);
			break;
		}

		// API: update API URL according to site
		$api->apiurl = "http://" . $app->config['baseuri'] . "hu/api";

		// API: upload recording with specific metadata
		$language = "hun";
		$metadata = array(
			'title'					=> $vcr_upload['feed_name'],
			'subtitle'				=> $vcr_upload['datetime'],
			'recordedtimestamp'		=> $vcr_upload['datetime'],
			'accesstype'			=> 'public',	// örökölje a csatornáét?
			'approvalstatus'		=> 'draft',
			'isdownloadable'		=> 0,
			'isaudiodownloadable'	=> 0,
			'isembedable'			=> 1,
			'conversionpriority'	=> 100
		);

		// API: add recording to repository
		try {
			$recording = $api->uploadRecording($media_filename, $language, $vcr_upload['userid']);
		} catch (exception $err) {
			// API: cannot add recording. We log error and will try later.
			// Stream status: revert to "upload" (try later)
			updateLiveStreamStatus($vcr_upload['id'], $jconf['dbstatus_vcr_upload']);
            $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot add recording to Videosquare. Info:\n\nFilename: " . $media_filename . "\n\nMetadata:\n" . print_r($metadata, true), $sendmail = true);
			break;
		}

        // Watchdog
		$app->watchdog();

		// Metadata: add
		if ( $recording and isset( $recording['data']['id'] ) ) {
			$recordingid = $recording['data']['id'];
			// API: add metadata
			try {
				$api->modifyRecording($recordingid, $metadata);
			} catch (exception $err) {
				// API: cannot add metadata. We log error, but upload is successful
                $debug->log($jconf['log_dir'], $myjobid . ".log", "[WARNING] Cannot add metadata to recording. Info:\n\nFilename: " . $media_filename . "\n\nMetadata:\n" . print_r($metadata, true) . "\n\nError:\n\n" . $err, $sendmail = true);
			}

			// Stream status: revert to "ready" (next recording is possible)
			updateLiveStreamStatus($vcr_upload['id'], $jconf['dbstatus_vcr_ready']);
            $debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] VCR recording added. Info:\n\nFilename: " . $media_filename . "\n\nMetadata:\n" . print_r($metadata, true), $sendmail = true);

		} else {
			// Stream status: revert to "upload" (try later)
			updateLiveStreamStatus($vcr_upload['id'], $jconf['dbstatus_vcr_ready']);
            $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot find recording ID in array returned by API. Recording might be added, but metadata did not. Info:\n\nFilename: " . $media_filename . "\n\nRecording array:\n\n" . print_r($recording, true) . "\n\nMetadata:\n" . print_r($metadata, true), $sendmail = true);
			break;
		}

// TODO:
// - channel kreálás a live alapján?
// channel add példa:
//$api->addRecordingToChannel( $channelid, 1 );
// Sikeres feltöltéskor felvétel törlése a TCS-ről? (javasolt egy hosszabb tesztidőszak után bevezetni)

	}

	// Close DB connection if open
	if ( is_resource($db->_connectionID) ) $db->close();

	$app->watchdog();

	sleep($sleep_length);
	
}	// End of outer while

exit;

function TCS_Connect() {
global $app, $jconf, $myjobid;

	// SOAP related URLs
	$vcr_wsdl = "http://" . $app->config['vcr']['server'] . "/tcs/Helium.wsdl";
	$vcr_api_url = "http://" . $app->config['vcr']['server'] . "/tcs/SoapServer.php";

	// TCS: SOAP connection to Cisco TCS
	$soapOptions = array(
		'location'                  => $vcr_api_url,
		'authentication'            => SOAP_AUTHENTICATION_DIGEST,
		'login'                     => $app->config['vcr']['user'],
		'password'                  => $app->config['vcr']['password'],
		'connection_timeout'        => 10
	);

	try {
		$soap_rs = new SoapClient($vcr_wsdl, $soapOptions);
	} catch (exception $err) {
        $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot connect to SOAP client. Please check.\n\n" . print_r($soapOptions, true) . "\n\nError:\n\n" . $err, $sendmail = true);
		return false;
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
function query_vcrrecording(&$vcr, &$vcr_user) {
global $jconf, $db, $myjobid;

	$db = db_maintain();

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
			c.issecurestreamingforced,
			c.needrecording
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
        $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot query next VCR job. SQL query failed.\n\n" . trim($query) . "\n\nError:\n\n" . $err, $sendmail = true);
		return false;
	}

	// Check if pending job exsits
	if ( $rs->RecordCount() < 1 ) {
		return false;
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
        $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot query next VCR job. SQL query failed.\n\n" . trim($query) . "\n\nError:\n\n" . $err, $sendmail = true);
		return false;
	}

	// Check if user exsits to media
	if ( $rs2->RecordCount() < 1 ) {
		return false;
	}

	$vcr_user = $rs2->fields;

	return true;
}

// Query VCR recording to upload
function query_vcrupload(&$vcr_upload, &$vcr_user) {
global $jconf, $db, $myjobid;

	$db = db_maintain();

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
			b.name as feed_name,
			b.needrecording
		FROM
			livefeed_streams as a,
			livefeeds as b
		WHERE
			a.status = '" . $jconf['dbstatus_vcr_upload'] . "' AND
			a.livefeedid = b.id AND
			b.needrecording = 1
		ORDER BY
			id
		LIMIT 1";

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
        $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot query next recording to upload. SQL query failed.\n\n" . trim($query) . "\n\nError:\n\n" . $err, $sendmail = true);
		return false;
	}

	// Check if pending job exsits
	if ( $rs->RecordCount() < 1 ) {
		return false;
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
        $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot query user to VCR. SQL query failed.\n\n" . trim($query) . "\n\nError:\n\n" . $err, $sendmail = true);
		return false;
	}

	// Check if user exsits to media
	if ( $rs2->RecordCount() < 1 ) {
		return false;
	}

	$vcr_user = $rs2->fields;

	return true;
}

function TCS_ReserveConfId($vcr) {
global $soap_rs, $jconf, $myjobid;

	$err = array();

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

	$err['command'] = "RequestConferenceID():\n\n" . print_r($conf, true);
	try {
		$result = $soap_rs->RequestConferenceID($conf);
	} catch (exception $error) {
		$err['code'] = false;
		$err['message'] = "[ERROR] TCS API Exception. VCR info:\n\n" . print_r($vcr, true) . "\n\nException:\n\n" . $error->getMessage();
        $debug->log($jconf['log_dir'], $myjobid . ".log", $err['message'] . "\n\nCommand:\n\n" . $err['command'], $sendmail = true);
		return $err;
	}

	$err['code'] = true;
    $err['data']['conf_id'] = $result->RequestConferenceIDResult;
    return $err;
}

function TCS_Dial($vcr) {
global $soap_rs, $jconf, $myjobid;

	$err = array();

	// Recording link: update status
	updateVCRReclinkStatus($vcr['reclink_id'], $jconf['dbstatus_vcr_starting']);

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

	$err['command'] = "Dial():\n\n" . print_r($conf, true);
	try {
		$result = $soap_rs->Dial($conf);
	} catch (exception $error) {
		$err['code'] = false;
		$err['message'] = "[ERROR] TCS API Exception. VCR info:\n\n" . print_r($vcr, true) . "\n\nException:\n\n" . $error->getMessage();
        $debug->log($jconf['log_dir'], $myjobid . ".log", $err['message'] . "\n\nCommand:\n\n" . $err['command'], $sendmail = true);
		return $err;
	}

	if ( $result->DialResult->ErrorCode != 0 ) {
		$err['code'] = false;
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
			$err['code'] = false;
			$err['result'] = $err_ci['result'];
			$err['message'] = "[ERROR] VCR call init returned an error. Call target:\n\n" . print_r($conf, true) . "\n\nCall state: " . $err_ci['message'];
			return $err;
		}

		// Check 25 seconds of timeout
		if ( $i > 5 ) {
			$err['code'] = false;
			$err['result'] = $err_ci['result'];
			$err['message'] = "[ERROR] VCR call cannot be established in " . $i * 5 . " seconds. Call target:\n\n" . print_r($conf, true) . "\n\nCall state:\n\n" . $err_ci['message'];
			return $err;
		}

		// Wait some longer to leave some time to establish call
		sleep(5);

		$i++;
	}

	$err['code'] = true;
	$err['result'] = $err_ci['result'];
	$err['message'] = $err_ci['message'];
	return $err;
}

function TCS_CallInfo($vcr) {
global $soap_rs, $jconf, $myjobid;

	$err = array();

	$conf = array(
		'ConferenceID'  => $vcr['conf_id']
	);

	$err['command'] = "GetCallInfo():\n\n" . print_r($conf, true);
	try {
		$result = $soap_rs->GetCallInfo($conf);
	} catch (exception $error) {
		$err['code'] = false;
		$err['message'] = "[ERROR] TCS API Exception. VCR info:\n\n" . print_r($vcr, true) . "\n\nException:\n\n" . $error->getMessage();
        $debug->log($jconf['log_dir'], $myjobid . ".log", $err['message'] . "\n\nCommand:\n\n" . $err['command'], $sendmail = true);
		return $err;
	}

	$callinfo = $result->GetCallInfoResult;
	$err['result'] = $callinfo->CallState;
	$err['data']['callstate'] = $callinfo->CallState;
	$err['data']['mediastate'] = $callinfo->MediaState;
	$err['data']['writerstatus'] = $callinfo->WriterStatus;

	// Unknown conference ID, fatal error
	if ( $callinfo->CallState == "NOT_IN_CALL" ) {
		$err['code'] = false;
		$err['message'] = "[ERROR] VCR not in call.";
		return $err;
	}

	// Dialing in progress
	if ( $callinfo->CallState == "INITIALISING_CALL" ) {
		$err['code'] = true;
		$err['message'] = "[ERROR] VCR is establishing a call.";
		return $err;
	}

	// Disconnect in progress
	if ( $callinfo->CallState == "ENDING_CALL" ) {
		$err['code'] = true;
		$err['message'] = "[ERROR] VCR is disconnecting a call.";
		return $err;
	}

	// Undefined error
	if ( $callinfo->CallState != "IN_CALL" ) {
		$err['code'] = false;
		$err['message'] = "[ERROR] VCR call is in undefined state.";
        $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Undefined error in setting up call.\n\nCommand:\n\n" . $err['command'] . "\n\nError:\n\n" . $err['message'], $sendmail = true);
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
		$err['code'] = false;
		$err['message'] = "[ERROR] Undefined error in recording. Call info:\n\n" . $callinfo_log;
		return $err;
	}

	$err['code'] = true;
	$err['message'] = "[OK] VCS call established. Call info:\n\n" . $callinfo_log;

	return $err;
}

function TCS_GetConfInfo(&$vcr) {
global $soap_rs, $jconf, $myjobid;

	$err = array();
	$err['code_num'] = 0;

	$conf = array(
		'ConferenceID'  => $vcr['conf_id']
	);

	$err['command'] = "GetConference():\n\n" . print_r($conf, true);
	try {
		$result = $soap_rs->GetConference($conf);
	} catch (exception $error) {
		$err['code'] = false;
		$err['code_num'] = -2;
		$err['message'] = "[ERROR] TCS API Exception. VCR info:\n\n" . print_r($vcr, true) . "\n\nException:\n\n" . $error->getMessage();
        $debug->log($jconf['log_dir'], $myjobid . ".log", $err['message'] . "\n\nCommand:\n\n" . $err['command'], $sendmail = true);
		return $err;
	}

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
		if ( !empty($err_su['data']['download_url']) ) $vcr['download_url'] = $err_su['data']['download_url'];
		if ( !empty($err_su['data']['datetime']) ) {
			$vcr['datetime'] = $err_su['data']['datetime'];
		} else {
			$vcr['datetime'] = date("Y-m-d H:i:s");
		}
		$vcr['width'] = $err_su['data']['width'];
		$vcr['height'] = $err_su['data']['height'];
	} else {
		$err['code'] = false;
		$err['code_num'] = -1;
		$err['message'] = $err_su['message'];
		return $err;
	}

	$err['code'] = true;
	$err['message'] = "[OK] VCR conference info: " . print_r($err_su['data'], true);
    return $err;
}

// Get RTMP URL
function TCS_GetStreamParams($conf_info) {

	$err = array();

	$is_flashver = false;

	// Get recording date and time
	if ( !empty($conf_info->GetConferenceResult->DateTime) ) {
		$err['data']['datetime'] = date('Y-m-d H:i:s', $conf_info->GetConferenceResult->DateTime);
	}

	$err['data']['width'] = 0;
	$err['data']['height'] = 0;

	$HasWatchableMovie = $conf_info->GetConferenceResult->HasWatchableMovie;
	if ( $HasWatchableMovie ) {
		$WatchableMovie = $conf_info->GetConferenceResult->WatchableMovies->ArrayOfWatchableMovie->WatchableMovie;

		$num = count($WatchableMovie);

		// Fatal error, no URL is provided by TCS
		if ( $num == 0 ) {
			$err['code'] = false;
			$err['message'] = "[ERROR] VCR does not have watchable video streams.";
			return $err;
		}

		// Quality options: Large, Medium, Small, Audio only (?)
		if ( $num == 1 ) {
//			if ( ( $WatchableMovie->Format == "Flash" ) and ( $WatchableMovie->Quality == "Large" ) ) {
			if ( $WatchableMovie->Format == "Flash" ) {
				$err['data']['mainurl'] = $WatchableMovie->MainURL;
				$err['data']['format'] = "flash";
				$err['data']['quality'] = $WatchableMovie->Quality;
				$err['data']['bitrate'] = $WatchableMovie->TotalBandwidth;
				$err['data']['width'] = $WatchableMovie->MainWidth;
				$err['data']['height'] = $WatchableMovie->MainHeight;
				$is_flashver = true;
			}
		}

		if ( $num > 1 ) {
			for ( $i = 0; $i < $num; $i++) {
//				if ( ( $WatchableMovie[$i]->Format == "Flash" ) and ( $WatchableMovie[$i]->Quality == "Large" ) ) {
				if ( $WatchableMovie[$i]->Format == "Flash" ) {
					// If lesser quality that is already identified then jump
					if ( $WatchableMovie[$i]->MainWidth < $err['data']['width'] ) break;
					$err['data']['mainurl'] = $WatchableMovie[$i]->MainURL;
					$err['data']['format'] = "flash";
					$err['data']['quality'] = $WatchableMovie->Quality;
					$err['data']['bitrate'] = $WatchableMovie[$i]->TotalBandwidth;
					$err['data']['width'] = $WatchableMovie[$i]->MainWidth;
					$err['data']['height'] = $WatchableMovie[$i]->MainHeight;
					$is_flashver = true;
					break;
				}
			}
		}

	}

	if ( $is_flashver and ( $err['data']['width'] > 0 ) and ( $err['data']['height'] > 0 ) ) {
		// Check if live or recording for download
		if ( strpos($err['data']['mainurl'], "rtmp") === false ) {
			$err['data']['download_url'] = $err['data']['mainurl'];
			$err['data']['rtmp_server'] = "";
			$err['data']['rtmp_streamid'] = "";
		} else {
			// Get Wowza RTMP stream ID
			$tmp = explode("mp4:", $err['data']['mainurl'], 2);
			$err['data']['rtmp_server'] = $tmp[0];
			$err['data']['rtmp_streamid'] = $tmp[1];
		}

		$err['code'] = true;
		$err['message'] = "[OK] VCR streaming URL is available.";
	} else {
		$err['code'] = false;
		$err['message'] = "[ERROR] VCR streaming URL is not available.";
	}

	return $err;
}

function TCS_Disconnect($vcr) {
global $soap_rs, $jconf, $myjobid;

	$err = array();

	$conf = array(
		'ConferenceID'  => $vcr['conf_id']
	);

	$err['command'] = "DisconnectCall():\n\n" . print_r($conf, true);
	try {
		$result = $soap_rs->DisconnectCall($conf);
	} catch (exception $error) {
		$err['code'] = false;
		$err['message'] = "[ERROR] TCS API Exception. VCR info:\n\n" . print_r($vcr, true) . "\n\nException:\n\n" . $error->getMessage();
        $debug->log($jconf['log_dir'], $myjobid . ".log", $err['message'] . "\n\nCommand:\n\n" . $err['command'], $sendmail = true);
		return $err;
	}

    if ( $result->DisconnectCallResult->Error != 0 ) {
		$err['code'] = false;
		$err['message'] = "[ERROR] VCR is unable to disconnect call for conference " . $vcr['conf_id'] . ". Error code: " . $result->DisconnectCallResult->ErrorCode;
		return $err;
	}

	$err['code'] = true;
	$err['message'] = "[OK] VCR call " . $vcr['conf_id'] . " disconnected.";

	return $err;
}

function TCS_GetSystemHealth() {
global $soap_rs, $jconf, $myjobid;

	$err = array();

	$err['command'] = "GetSystemHealth()";
	try {
		$result = $soap_rs->GetSystemHealth();
	} catch (exception $error) {
		$err['code'] = false;
		$err['message'] = "[ERROR] TCS API Exception. Exception:\n\n" . $error->getMessage();
        $debug->log($jconf['log_dir'], $myjobid . ".log", $err['message'] . "\n\nCommand:\n\n" . $err['command'], $sendmail = true);
		return $err;
	}

	$syshealth = $result->GetSystemHealthResult;

	$err['code'] = true;
	$err['message'] = "";

	if ( !$syshealth->EngineOK ) {
		$err['code'] = false;
		$err['message'] .= "VCR engine error. Check system!\n";
	}

	if ( !$syshealth->LibraryOK ) {
		$err['code'] = false;
		$err['message'] .= "VCR library error. Check system!\n";
	}

	if ( !$syshealth->DatabaseOK ) {
		$err['code'] = false;
		$err['message'] .= "VCR database error. Check system!\n";
	}

	return $err;
}

function TCS_GetSystemInformation() {
global $soap_rs, $jconf, $myjobid;

	$err = array();

	$err['command'] = "GetSystemInformation()";
	try {
		$result = $soap_rs->GetSystemInformation();
	} catch (exception $error) {
		$err['code'] = false;
		$err['message'] = "[ERROR] TCS API Exception. Exception:\n\n" . $error->getMessage();
        $debug->log($jconf['log_dir'], $myjobid . ".log", $err['message'] . "\n\nCommand:\n\n" . $err['command'], $sendmail = true);
		return $err;
	}

	$err['code'] = true;

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
global $soap_rs, $jconf, $myjobid;

	$err = array();

	$err['command'] = "GetCallCapacity()";
	try {
		$result = $soap_rs->GetCallCapacity();
	} catch (exception $error) {
		$err['code'] = false;
		$err['message'] = "[ERROR] TCS API Exception. Exception:\n\n" . $error->getMessage();
        $debug->log($jconf['log_dir'], $myjobid . ".log", $err['message'] . "\n\nCommand:\n\n" . $err['command'], $sendmail = true);
		return $err;
	}

	$err['code'] = true;

	$err['data']['maxcalls'] = $result->GetCallCapacityResult->MaxCalls;
	$err['data']['currentcalls'] = $result->GetCallCapacityResult->CurrentCalls;
	$err['data']['maxlivecalls'] = $result->GetCallCapacityResult->MaxLiveCalls;
	$err['data']['currentlivecalls'] = $result->GetCallCapacityResult->CurrentLiveCalls;

	$err['message']  = "";
	$err['message'] .= "Max calls: " . $err['data']['maxcalls'] . "\n";
	$err['message'] .= "Current calls: " . $err['data']['currentcalls'] . "\n";
	$err['message'] .= "Max live calls: " . $err['data']['maxlivecalls'] . "\n";
	$err['message'] .= "Current live calls: " . $err['data']['currentlivecalls'];

	return $err;
}


?>