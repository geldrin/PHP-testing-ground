<?php
// Content conversion job v0 @ 2012/02/??

define('BASE_PATH',	realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');

// Utils
include_once('job_utils_base.php');
include_once('job_utils_log.php');
include_once('job_utils_status.php');
include_once('job_utils_media.php');

set_time_limit(0);

// Init
$app = new Springboard\Application\Cli(BASE_PATH, PRODUCTION);

// Load jobs configuration file
$app->loadConfig('modules/Jobs/config_jobs.php');
$jconf = $app->config['config_jobs'];

// Log related init
$debug = Springboard\Debug::getInstance();
$debug->log($jconf['log_dir'], $jconf['jobid_content_convert'] . ".log", "Content conversion job started", $sendmail = false);

// Check operating system - exit if Windows
if ( iswindows() ) {
	echo "ERROR: Non-Windows process started on Windows platform\n";
	exit;
}

// Start an infinite loop - exit if any STOP file appears
while( !is_file( $app->config['datapath'] . 'jobs/job_content_convert.stop' ) and !is_file( $app->config['datapath'] . 'jobs/all.stop' ) ) {

	clearstatcache();

    while ( 1 ) {

		$app->watchdog();
	
		$db_close = FALSE;
		$converter_sleep_length = $jconf['sleep_media'];

		// Check if temp directory readable/writable
		if ( !is_writable($jconf['content_dir']) ) {
			$debug->log($jconf['log_dir'], $jconf['jobid_content_convert'] . ".log", "[FATAL ERROR] Temp directory " . $jconf['content_dir'] . " is not writable. Storage error???\n", $sendmail = true);
			// Sleep one hour then resume
			$converter_sleep_length = 60 * 60;
			break;
		}

		// Establish database connection
		try {
			$db = $app->bootstrap->getAdoDB();
		} catch (exception $err) {
			// Send mail alert, sleep for 15 minutes
			$debug->log($jconf['log_dir'], $jconf['jobid_content_convert'] . ".log", "[ERROR] No connection to DB (getAdoDB() failed). Error message:\n" . $err, $sendmail = true);
			// Sleep 15 mins then resume
			$converter_sleep_length = 15 * 60;
			break;
		}

		// Temporary directory cleanup and log result
		$err = tempdir_cleanup($jconf['content_dir']);
		if ( !$err['code'] ) log_recording_conversion(0, $jconf['jobid_content_convert'], "-", $err['message'], $err['command'], $err['result'], 0, TRUE);

		$recording = array();
		$uploader_user = array();

//update_db_content_status(1, "uploaded");
//update_db_mastercontent_status(1, "uploaded");
//update_db_content_status(1, "reconvert");
//update_db_mastercontent_status(1, "onstorage");

		// Query next job - exit if none
		if ( !query_nextjob($recording, $uploader_user) ) break;

		// Initialize log for closing message and total duration timer
		$global_log = "";
		$total_duration = time();

		// Start log entry
		log_recording_conversion($recording['id'], $jconf['jobid_content_convert'], $jconf['dbstatus_init'], "START PROCESSING: " . $recording['id'] . "_content." . $recording['contentmastervideoextension'], "-", "-", 0, FALSE);
		$global_log .= "Source front-end: " . $recording['contentmastersourceip'] . "\n";
		$global_log .= "Original filename: " . $recording['contentmastervideofilename'] . "\n";
		$global_log .= "Media length: " . secs2hms( $recording['contentmasterlength'] ) . "\n";
		$global_log .= "Media type: " . $recording['contentmastermediatype'] . "\n\n";

		// Copy media from front-end server
		if ( !copy_content_to_converter($recording) ) break;

		//// Media conversion
		$content_info_lq = array();
		$content_info_hq = array();

		update_db_content_status($recording['id'], $jconf['dbstatus_conv_video']);

		// Normal quality conversion (LQ)
		if ( !convert_video($recording, $jconf['profile_content_lq'], $content_info_lq) ) {
			update_db_content_status($recording['id'], $jconf['dbstatus_conv_video_err']);
			break;
		}

		// Decide about high quality conversion (HQ)
		$res = explode("x", strtolower($recording['contentmastervideores']), 2);
		$res_x = $res[0];
		$res_y = $res[1];
		$res = explode("x", strtolower($jconf['profile_content_lq']['video_bbox']), 2);
		$bbox_res_x = $res[0];
		$bbox_res_y = $res[1];
		// Generate HQ version if original recording does not fit LQ bounding box
		if ( ( $res_x > $bbox_res_x ) || ( $res_y > $bbox_res_y ) ) {
		if ( !convert_video($recording, $jconf['profile_content_hq'], $content_info_hq) ) {
				update_db_content_status($recording['id'], $jconf['dbstatus_conv_video_err']);
				break;
			}
		}

		// Media finalization
		if ( !copy_content_to_frontend($recording, $content_info_lq, $content_info_hq) ) {
			update_db_content_status($recording['id'], $jconf['dbstatus_copystorage_err']);
			break;
		}

		//// End of media conversion
		$global_log .= "URL: http://video.teleconnect.hu/hu/recordings/details/" . $recording['id'] . "\n\n";
		$conversion_duration = time() - $total_duration;
		$hms = secs2hms($conversion_duration);
		log_recording_conversion($recording['id'], $jconf['jobid_content_convert'], "-", "[OK] Successful content conversion in " . $hms . " time.\n\nConversion summary:\n\n" . $global_log, "-", "-", $conversion_duration, TRUE);

		// Send e-mail to user about successful conversion
/*		$smarty = getSmarty();
		$smarty->assign('filename', $recording['mastervideofilename']);
		$smarty->assign('language', $uploader_user['language']);
		$smarty->assign('recid', $recording['id']);
		if ( $uploader_user['language'] == "hu" ) {
			$subject = "Video konverzió kész";
		} else {
			$subject = "Video conversion ready";
		}
		if ( !empty($recording['mastervideofilename']) ) $subject .= ": " . $recording['mastervideofilename'];
		$queue = $app->bootstrap->getMailqueue();
		$queue->sendHTMLEmail($uploader_user['email'], $subject, $smarty->fetch('emails/converter_email.tpl'), $values = array() ); */

		break;
	}	// End of while(1)

	// Close DB connection if open
	if ( $db_close ) {
		$db->close();
	}

	$app->watchdog();

	sleep( $converter_sleep_length );
	
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
function query_nextjob(&$recording, &$uploader_user) {
global $jconf, $db;

	$query = "
		SELECT
			id,
			hascontentvideo,
			contentmastervideoisinterlaced,
			contentmastervideofilename,
			contentmastervideoextension,
			contentmastermediatype,
			contentmastervideocontainerformat,
			contentmasterlength,
			contentmastervideocodec,
			contentmastervideores,
			contentmastervideofps,
			contentmastervideobitrate,
			contentmasteraudiocodec,
			contentmasteraudiobitratemode,
			contentmasteraudiochannels,
			contentmasteraudioquality,
			contentmasteraudiofreq,
			contentmasteraudiobitrate,
			contentmasterstatus,
			contentstatus,
			conversionpriority,
			contentmastersourceip
		FROM
			recordings
		WHERE
			( ( contentmasterstatus = \"" . $jconf['dbstatus_uploaded'] . "\" AND contentstatus = \"" . $jconf['dbstatus_uploaded'] . "\" ) OR
			( contentmasterstatus = \"" . $jconf['dbstatus_copystorage_ok'] . "\" AND contentstatus = \"" . $jconf['dbstatus_reconvert']  . "\" ) ) AND
			( contentmastersourceip IS NOT NULL OR contentmastersourceip != '' )
		ORDER BY
			conversionpriority,
			id
		LIMIT 1";

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		log_recording_conversion(0, $jconf['jobid_content_convert'], $jconf['dbstatus_init'], "[ERROR] Cannot query next content conversion job. SQL query failed.", trim($query), $err, 0, TRUE);
		return FALSE;
	}

	// Check if pending job exsits
	if ( $rs->RecordCount() < 1 ) {
		return FALSE;
	}

	$recording = $rs->fields;

	$query = "
		SELECT
			a.userid,
			b.nickname,
			b.email,
			b.language
		FROM
			recordings as a,
			users as b
		WHERE
			a.userid = b.id AND
			a.id = " . $recording['id'];

	try {
		$rs2 = $db->Execute($query);
	} catch (exception $err) {
		log_recording_conversion($recording['id'], $jconf['jobid_content_convert'], $jconf['dbstatus_init'], "[ERROR] Cannot query user to content. SQL query failed.", trim($query), $err, 0, TRUE);
		return FALSE;
	}

	// Check if user exsits to media
	if ( $rs2->RecordCount() < 1 ) {
		return FALSE;
	}

	$uploader_user = $rs2->fields;

	return TRUE;
}

// *************************************************************************
// *			function copy_content_to_converter()   			   	   	   *
// *************************************************************************
// Description: initial checks, preparation of directories
// INPUTS:
//	- $recording: content information from recording_elements table
// OUTPUTS:
//	- Boolean:
//	  o FALSE: initialization failed (error cause logged in DB and local files)
//	  o TRUE: initialization OK
//	- $master_filename: full path to original file on local disk being converted
//	- $temp_directory: temporary directory for converting current media
//	- Others:
//	  o logs in local logfile and SQL DB table recordings_log
//	  o updates recording status field
function copy_content_to_converter(&$recording) {
global $app, $jconf;

	// Update watchdog timer
	$app->watchdog();

	// Update media status
	update_db_content_status($recording['id'], $jconf['dbstatus_copyfromfe']);

	if ( !isset($recording['contentmastersourceip']) ) {
		log_recording_conversion($recording['id'], $jconf['jobid_content_convert'], $jconf['copyfromfe'], "[ERROR] Source IP is empty, cannot identify front-end server.", "-", "-", 0, TRUE);
		update_db_content_status($recording['id'], $jconf['dbstatus_copyfromfe_err']);
		return FALSE;
	}

	// Media is too short (fraud check)
	$playtime = ceil($recording['contentmasterlength']);
	if ( $playtime < $jconf['video_min_length'] ) {
		log_recording_conversion($recording['id'], $jconf['jobid_content_convert'], $jconf['dbstatus_init'], "[ERROR] Content length is too short: " . $recording['id'] . "_content." . $recording['contentmastervideoextension'], "-", "-", 0, TRUE);
		update_db_content_status($recording['id'], $jconf['dbstatus_invalidinput']);
		return FALSE;
	}

	// SSH command template
	$ssh_command = "ssh -i " . $jconf['ssh_key'] . " " . $jconf['ssh_user'] . "@" . $recording['contentmastersourceip'] . " ";
	// Set upload path to default upload area
	$uploadpath = $app->config['uploadpath'] . "recordings/";
	// Media path and filename
	$base_filename = $recording['id'] . "_content." . $recording['contentmastervideoextension'];
	// Check reconvert state. In case of reconvert, we copy from recordings area
	$recording['conversion_type'] = "convert";
	if ( ( ( $recording['contentstatus'] == $jconf['dbstatus_reconvert'] ) && ( $recording['contentmasterstatus'] == $jconf['dbstatus_copystorage_ok'] ) ) || ( $recording['contentmasterstatus'] == $jconf['dbstatus_copystorage_ok'] ) ) {
		$uploadpath = $app->config['recordingpath'] . ( $recording['id'] % 1000 ) . "/" . $recording['id'] . "/master/";
		$recording['conversion_type'] = $jconf['dbstatus_reconvert'];
	}

	$remote_filename = $jconf['ssh_user'] . "@" . $recording['contentmastersourceip'] . ":" . $uploadpath . $base_filename;
	$master_filename = $jconf['content_dir'] . $recording['id'] . "/master/" . $base_filename;

	$recording['remote_filename'] = $remote_filename;
	$recording['source_file'] = $master_filename;

	// SSH check file size before start copying
	$err = ssh_filesize($recording['contentmastersourceip'], $uploadpath . $base_filename);
	if ( !$err['code'] ) {
		log_recording_conversion($recording['id'], $jconf['jobid_content_convert'], $jconf['dbstatus_copyfromfe'], $err['message'], $err['command'], $err['result'], 0, TRUE);
		// Set status to "invalidinput"
		update_db_content_status($recording['id'], $jconf['dbstatus_invalidinput']);
		return FALSE;
	}
	$filesize = $err['value'];

	// Check available disk space (input media file size * 3 is the minimum)
	$available_disk = floor(disk_free_space($jconf['content_dir']));
	if ( $available_disk < $filesize * 3 ) {
		log_recording_conversion($recording['id'], $jconf['jobid_content_convert'], $jconf['dbstatus_copyfromfe'], "[ERROR] No enough local disk space available (needed = " . ceil(($filesize * 2) / 1024 / 1024) . "Mb, avail = " . ceil($available_disk / 1024 / 1024) . "Mb)", "php: disk_free_space(\"" . $jconf['content_dir'] . "\")", "-", 0, TRUE);
		// Set status to "uploaded" to allow other nodes to take over task
		update_db_content_status($recording['id'], $jconf['dbstatus_uploaded']);
		return FALSE;
	}

	// Prepare temporary conversion directory, remove any existing content
	$temp_directory = $jconf['content_dir'] . $recording['id'] . "/";
	$recording['temp_directory'] = $temp_directory;
	$err = create_directory($temp_directory);
	if ( !$err['code'] ) {
		log_recording_conversion($recording['id'], $jconf['jobid_content_convert'], $jconf['dbstatus_copyfromfe'], $err['message'], $err['command'], $err['result'], 0, TRUE);
		// Set status to "uploaded" to allow other nodes to take over task
		update_db_content_status($recording['id'], $jconf['dbstatus_uploaded']);
		return FALSE;
	}

	// Prepare master directory
	$err = create_directory($temp_directory . "master/");
	if ( !$err['code'] ) {
		log_recording_conversion($recording['id'], $jconf['jobid_content_convert'], $jconf['dbstatus_copyfromfe'], $err['message'], $err['command'], $err['result'], 0, TRUE);
		// Set status to "uploaded" to allow other nodes to take over task
		update_db_content_status($recording['id'], $jconf['dbstatus_uploaded']);
		return FALSE;
	}

	// SCP copy from remote location
	$err = ssh_filecopy_from($recording['contentmastersourceip'], $uploadpath . $base_filename, $master_filename);
	if ( !$err['code'] ) {
		log_recording_conversion($recording['id'], $jconf['jobid_content_convert'], $jconf['dbstatus_copyfromfe'], $err['message'], $err['command'], $err['result'], $err['value'], TRUE);
		// Set status to "uploaded" to allow other nodes to take over task
		update_db_content_status($recording['id'], $jconf['dbstatus_uploaded']);
		return FALSE;
	}
	log_recording_conversion($recording['id'], $jconf['jobid_content_convert'], $jconf['dbstatus_copyfromfe'], $err['message'], $err['command'], $err['result'], $err['value'], FALSE);

	// Update watchdog timer
	$app->watchdog();

	return TRUE;
}

// *************************************************************************
// *				function copy_content_to_frontend()		   			   *
// *************************************************************************
// Description: Copy (SCP) content media file(s) back to front-end server.
// INPUTS:
//	- $recording: recording information
//	- $recording_info_lq, $recording_info_hq: HQ and LQ content information
// OUTPUTS:
//	- Boolean:
//	  o FALSE: audio track encoding failed (error cause logged in DB and local files)
//	  o TRUE: audio track encoding OK
//	- Others:
//	  o Media file(s)
//	  o Log entries (file and database)
function copy_content_to_frontend($recording, $recording_info_lq, $recording_info_hq) {
global $app, $jconf;

	update_db_content_status($recording['id'], $jconf['dbstatus_copystorage']);

	// Reconvert: remove master file (do not copy back as already in place)
	if ( $recording['conversion_type'] == $jconf['dbstatus_reconvert'] ) {
		$err = remove_file_ifexists($recording['source_file']);
		if ( !$err['code'] ) log_recording_conversion($recording['id'], $jconf['jobid_content_convert'], $jconf['dbstatus_copystorage'], $err['message'], $err['command'], $err['result'], 0, TRUE);
	}

	// SSH command templates
	$ssh_command = "ssh -i " . $jconf['ssh_key'] . " " . $jconf['ssh_user'] . "@" . $recording['contentmastersourceip'] . " ";
	$scp_command = "scp -B -r -i " . $jconf['ssh_key'] . " ";
	$remote_recording_directory = $app->config['recordingpath'] . ( $recording['id'] % 1000 ) . "/" . $recording['id'] . "/";

	// Target path for SCP command
	$remote_path = $jconf['ssh_user'] . "@" . $recording['contentmastersourceip'] . ":" . $app->config['recordingpath'] . ( $recording['id'] % 1000 ) . "/";

	// Create remote directories, does nothing if exists
	$command = $ssh_command . "mkdir -m " . $jconf['directory_access'] . " -p " . $app->config['recordingpath'] . ( $recording['id'] % 1000 ) . " 2>&1";
	exec($command, $output, $result);
	$output_string = implode("\n", $output);
	if ( $result != 0 ) {
		log_recording_conversion($recording['id'], $jconf['jobid_content_convert'], $jconf['dbstatus_copystorage'], "[ERROR] Failed creating remote directory (SSH)", $command, $output_string, 0, TRUE);
		return FALSE;
	}

	//// Remove all previous files from recording directory (from previous conversions)
	// Content files: LQ and HQ (_content_lq.mp4 and _content_hq.mp4)
	$media_regex = ".*" . $recording['id'] . "_\(content_lq\.mp4\|content_hq\.mp4\)";
	$command1 = "find " . $remote_recording_directory . " -mount -maxdepth 1 -type f -regex '" . $media_regex . "' -exec rm -f {} \\; 2>/dev/null";
	$command = $ssh_command . " " . $command1;
	exec($command, $output, $result);

	// Master file exists: move (only file with matching name, if extension is different then no change)
	$master_file_torename = $remote_recording_directory . "/master/" . $recording['id'] . "_content." . $recording['contentmastervideoextension'];
	$err = ssh_filesize($recording['contentmastersourceip'], $master_file_torename);
	if ( ( $err['code'] ) and ( $err['value'] > 0 ) ) {
		$master_file_newname = $remote_recording_directory . "/master/" . $recording['id'] . "_content_" . date("YmdHis") . "." . $recording['contentmastervideoextension'];
//echo "van! " . $master_file_torename . " " . $master_file_newname . "\n";
		$err = ssh_filerename($recording['contentmastersourceip'], $master_file_torename, $master_file_newname);
	}

	// SCP copy from local temp to remote location
	$command = $scp_command . $recording['temp_directory'] . " " . $remote_path . " 2>&1";
	$time_start = time();
	exec($command, $output, $result);
	$duration = time() - $time_start;
	$mins_taken = round( $duration / 60, 2);
	$output_string = implode("\n", $output);
	if ( $result != 0 ) {
		log_recording_conversion($recording['id'], $jconf['jobid_content_convert'], $jconf['dbstatus_copystorage'], "[ERROR] SCP copy failed to: " . $remote_filename, $command, $output_string, $duration, TRUE);
		return FALSE;
	}
	log_recording_conversion($recording['id'], $jconf['jobid_content_convert'], $jconf['dbstatus_copystorage'], "[OK] SCP copy finished (in " . $mins_taken . " mins)", $command, $result, $duration, FALSE);

	// Set file and directory access rights of content files
	$command = $ssh_command . " chmod -f " . $jconf['file_access'] . " " . $remote_recording_directory . "*_content_*.mp4";
	exec($command, $output, $result);

	// Update database (status and media information)
	// Video media: set MOBILE, LQ and HQ resolution and thumbnail data
	$err = update_db_contentinfo($recording['id'], $recording_info_lq, $recording_info_hq);
	if ( !$err ) {
		log_recording_conversion($recording['id'], $jconf['jobid_content_convert'], $jconf['dbstatus_copystorage'], "[ERROR] Content video info final DB update failed", "-", $err, 0, TRUE);
		return FALSE;
	}

	// Update recording status
	update_db_content_status($recording['id'], $jconf['dbstatus_copystorage_ok']);
	update_db_mastercontent_status($recording['id'], $jconf['dbstatus_copystorage_ok']);

	// Remove master from upload area if not reconvert!
	if ( $recording['conversion_type'] != $jconf['dbstatus_reconvert'] ) {
		$uploadpath = $app->config['uploadpath'] . "recordings/";
		$base_filename = $recording['id'] . "_content." . $recording['contentmastervideoextension'];
		$err = ssh_fileremove($recording['contentmastersourceip'], $uploadpath . $base_filename);
		if ( !$err['code'] ) log_recording_conversion($recording['id'], $jconf['jobid_content_convert'], $jconf['dbstatus_copystorage'], $err['message'], $err['command'], $err['result'], 0, TRUE);
	}

	// Remove temporary directory, no failure if not successful
	$err = remove_file_ifexists($recording['temp_directory']);
	if ( !$err['code'] ) log_recording_conversion($recording['id'], $jconf['jobid_content_convert'], $jconf['dbstatus_copystorage'], $err['message'], $err['command'], $err['result'], 0, TRUE);

	return TRUE;
}



?>
