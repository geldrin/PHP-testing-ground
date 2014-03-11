<?php
// Media conversion job v0 @ 2012/02/??

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
$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "Media conversion job started", $sendmail = false);

// Check operating system - exit if Windows
if ( iswindows() ) {
	echo "ERROR: Non-Windows process started on Windows platform\n";
	exit;
}

// Start an infinite loop - exit if any STOP file appears
while( !is_file( $app->config['datapath'] . 'jobs/job_media_convert.stop' ) and !is_file( $app->config['datapath'] . 'jobs/all.stop' ) ) {

	clearstatcache();

    while ( 1 ) {

		$app->watchdog();
	
		// Establish database connection
		$db = null;
		$db = db_maintain();

		$converter_sleep_length = $jconf['sleep_media'];

		// Check if temp directory readable/writable
		if ( !is_writable($jconf['media_dir']) ) {
			$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "[FATAL ERROR] Temp directory " . $jconf['media_dir'] . " is not writable. Storage error???\n", $sendmail = true);
			// Sleep one hour then resume
			$converter_sleep_length = 60 * 60;
			break;
		}

// TESTING: Reset media status
/*update_db_recording_status(89, "uploaded");
update_db_masterrecording_status(89, "uploaded");
updateRecordingVersionStatus(1, "convert");
updateRecordingVersionStatus(2, "convert");
*/

		// Query next job
		$recording = getNextConversionJob();
		if ( $recording === false ) break;

		// Query recording creator
		$uploader_user = getRecordingCreator($recording['id']);
		if ( $uploader_user === false ) unset($uploader_user);

		// Query encoding profile
		$encoding_profile = getEncodingProfile($recording['encodingprofileid']);
		if ( $encoding_profile === false ) break;

		// Initialize log for summary mail message and total duration timer
		$total_duration = time();

		// Start log entry
		$global_log  = "Converting: " . $recording['id'] . "." . $recording['mastervideoextension'] . " (" . $encoding_profile['shortname'] . ")\n";
		$global_log .= "Source front-end: " . $recording['mastersourceip'] . "\n";
		$global_log .= "Original filename: " . $recording['mastervideofilename'] . "\n";
		$global_log .= "Media length: " . secs2hms( $recording['masterlength'] ) . "\n";
		$global_log .= "Media type: " . $recording['mastermediatype'] . "\n";
		$global_log .= "Encoding profile: " . $encoding_profile['name'] . "\n\n";
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_init'], $global_log, "-", "-", 0, false);

		// Copy media from front-end server
		if ( !copyMediaToConverter($recording) ) {
			updateRecordingVersionStatus($recording['recordingversionid'], $jconf['dbstatus_copyfromfe_err']);
			break;
		}

		// Watchdog
		$app->watchdog();

		// Video thumbnail generation (when first video is converted)
		if ( empty($encoding_profile['parentid']) and ( $encoding_profile['type'] == "recording" ) and ( $encoding_profile['mediatype'] == "video" ) ) {
			$err = convertVideoThumbnails($recording);
		}

var_dump($recording);

		// Watchdog
		$app->watchdog();

		// Convert recording version
		if ( !convertMedia($recording, $encoding_profile) ) {
			updateRecordingVersionStatus($recording['recordingversionid'], $jconf['dbstatus_conv_err']);
			break;
		}

		// Watchdog
		$app->watchdog();

		// Copy: converted file to front-end
		updateRecordingVersionStatus($recording['recordingversionid'], $jconf['dbstatus_copystorage']);
		if ( !copyMediaToFrontEnd($recording, $encoding_profile) ) {
			updateRecordingVersionStatus($recording['recordingversionid'], $jconf['dbstatus_copystorage_err']);
			break;
		}

		updateRecordingVersionStatus($recording['recordingversionid'], $jconf['dbstatus_copystorage_ok']);

		// Watchdog
		$app->watchdog();

		//// End of media conversion
		if ( isset($uploader_user) ) $global_log .= "URL: http://" . $uploader_user['domain'] . "/" . $uploader_user['language'] . "/recordings/details/" . $recording['id'] . "\n\n";
		$conversion_duration = time() - $total_duration;
		$hms = secs2hms($conversion_duration);
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], "-", "[OK] Successful media conversion in " . $hms . " time.\n\nConversion summary:\n\n" . $global_log, "-", "-", $conversion_duration, true);

	}	// End of while(1)

	// Close DB connection if open
	if ( is_resource($db->_connectionID) ) $db->close();

	$app->watchdog();

	sleep($converter_sleep_length);
	
}	// End of outer while

exit;


// *************************************************************************
// *					function getNextConversionJob()					   *
// *************************************************************************
// Description: queries next job from recordings_versions database table
function getNextConversionJob() {
global $jconf, $debug, $db, $app;

	$db = db_maintain();

	$node = $app->config['node_sourceip'];

	$query = "
		SELECT
			rv.recordingid AS id,
			rv.converternodeid,
			rv.id AS recordingversionid,
			rv.encodingprofileid,
			rv.encodingorder,
			rv.iscontent,
			rv.status,
			r.mastervideofilename,
			r.mastervideoextension,
			r.mastermediatype,
			r.mastervideocontainerformat,
			r.mastervideofps,
			r.masterlength,
			r.mastervideocodec,
			r.mastervideores,
			r.mastervideobitrate,
			r.mastervideoisinterlaced,
			r.mastervideodar,
			r.masteraudiocodec,
			r.masteraudiochannels,
			r.masteraudioquality,
			r.masteraudiofreq,
			r.masteraudiobitrate,
			r.masteraudiobitratemode,
			r.masterstatus,
			r.contentstatus,
			r.contentmasterstatus,
			r.mastersourceip
		FROM
			recordings_versions AS rv,
			recordings AS r,
			converter_nodes AS cn
		WHERE
			rv.status = \"convert\" AND
			rv.recordingid = r.id AND
			rv.converternodeid = cn.id AND
			cn.server = '" . $node . "' AND
			cn.disabled = 0
		ORDER BY
			rv.encodingorder,
			rv.recordingid
		LIMIT 1";

	try {
		$recording = $db->getArray($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "[ERROR] Cannot query next job. SQL query failed." . trim($query), $sendmail = true);
		return false;
	}

	// Check if any record returned
	if ( count($recording) < 1 ) return false;

	return $recording[0];
}


// *************************************************************************
// *			function copy_recording_to_converter()   			   	   *
// *************************************************************************
// Description: initial checks, preparation of directories
// INPUTS:
//	- $recording: content information from recording_elements table
// OUTPUTS:
//	- Boolean:
//	  o FALSE: initialization failed (error cause logged in DB and local files)
//	  o TRUE: initialization OK
//	- $master_filename: full path to original file on local disk being converted
//	- $recording['temp_directory']: temporary directory for converting current media
//	- Others:
//	  o logs in local logfile and SQL DB table recordings_log
//	  o updates recording status field
function copyMediaToConverter(&$recording) {
global $app, $jconf, $debug;

	//// Filenames and paths
	// Base filename
// ??? content is lehet
	$idx = "";
	$suffix = "video";
	if ( $recording['iscontent'] != 0 ) {
		$suffix = "content";
		$idx = "content";
	}
	if ( $recording[$idx . 'mastermediatype'] == "audio" ) $suffix = "audio";
	$base_filename = $recording['id'] . "_" . $suffix . "." . $recording[$idx . 'mastervideoextension'];
	// Upload path (Front-End)
	if ( $recording[$idx . 'masterstatus'] == $jconf['dbstatus_uploaded'] ) {
		$uploadpath = $app->config['uploadpath'] . "recordings/";
	} elseif ( $recording[$idx . 'masterstatus'] == $jconf['dbstatus_copystorage_ok'] ) {
		$uploadpath = $app->config['recordingpath'] . ( $recording['id'] % 1000 ) . "/" . $recording['id'] . "/master/";
	} else {
		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "Cannot locate master file:\n\nrecID = " . $recording['id'] . " | status = " . $recording[$idx . 'masterstatus'] . " | type = " . ($idx?"recording":$idx), $sendmail = true);
		return false;
	}

// Reconvert: !!! kell ez ide???
/*	if ( $recording['status'] == $jconf['dbstatus_reconvert'] ) {
		$recording['conversion_type'] = $jconf['dbstatus_reconvert'];
	}
*/

	//// Directories: assemble master and temporary directory paths
	// Master: caching directory
	$recording['master_basename'] = $recording['id'] . "_" . $suffix . "." . $recording[$idx . 'mastervideoextension'];
	$recording['master_remote_path'] = $uploadpath;
	$recording['master_remote_filename'] = $recording['master_remote_path'] . $recording['master_basename'];
	$recording['master_path'] = $jconf['master_dir'] . $recording['id'] . "/";
	$recording['master_filename'] = $recording['master_path'] . $recording['master_basename'];
	$recording['master_ssh_filename'] = $jconf['ssh_user'] . "@" . $recording['mastersourceip'] . ":" . $recording['master_remote_filename'];
	// Conversion: temporary directory
	$recording['temp_directory'] = $jconf['media_dir'] . $recording['id'] . "/";
	// Recording: remote storage directory
	$recording['recording_remote_path'] = $app->config['recordingpath'] . ( $recording['id'] % 1000 ) . "/" . $recording['id'] . "/";

	// Prepare temporary directory
	$err = create_directory($recording['temp_directory']);
	if ( !$err['code'] ) {
		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "MSG: " . $err['message'] . "\nCOMMAND: " . $err['command'] . "\nRESULT: " . $err['result'], $sendmail = true);
		// Set status to "uploaded" to allow other nodes to take over task??? !!!!
		return false;
	}

	//// Is file already downloaded? Check based on filesize and file mtime
	$err = ssh_file_cmp_isupdated($recording[$idx . 'mastersourceip'], $recording['master_remote_filename'], $recording['master_filename']);
	$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", $err['message'], $sendmail = false);
	// Local copy is up to date
	if ( $err['value'] ) return true;
	// Error occured
	if ( !$err['code'] ) $debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "MSG: " . $err['message'] . "\nCOMMAND: " . $err['command'] . "\nRESULT: " . $err['result'], $sendmail = true);

	// Prepare master directory
	$err = create_directory($recording['master_path']);
	if ( !$err['code'] ) {
		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "MSG: " . $err['message'] . "\nCOMMAND: " . $err['command'] . "\nRESULT: " . $err['result'], $sendmail = true);
		// Set status to "uploaded" to allow other nodes to take over task??? !!!
		return false;
	}

	// SCP: copy from front end server
	$err = ssh_filecopy($recording['mastersourceip'], $recording['master_remote_filename'], $recording['master_filename'], true);
	if ( !$err['code'] ) {
		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "MSG: " . $err['message'] . "\nCOMMAND: " . $err['command'] . "\nRESULT: " . $err['result'], $sendmail = true);
		// Set status to "uploaded" to allow other nodes to take over task??? !!!
		return false;
	}
	log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_copyfromfe'], $err['message'], $err['command'], $err['result'], $err['value'], false);

	return true;
}

// *************************************************************************
// *				function convertVideoThumbnails()			   		   *
// *************************************************************************
// Description: generate video thumbnails
function convertVideoThumbnails(&$recording) {
global $app, $jconf, $debug;

	// Initiate recording thumbnail attributes
	$recording['thumbnail_size'] = 0;
	$recording['thumbnail_indexphotofilename'] = "";
	$recording['thumbnail_numberofindexphotos'] = 0;

	if ( $recording['mastermediatype'] == "audio" ) {
		return true;
	}

	// Check input media resolution
	if ( empty($recording['mastervideores']) ) {
		$debug->log($jconf['log_dir'], $jconf['job_conversion_control'] . ".log", "[ERROR] Video resolution field is empty. Cannot convert thumbnails. Check input media.\n", $sendmail = true);
		return false;
	}

	$playtime = floor($recording['masterlength']);

	// Creating thumbnail directories under ./indexpics/
	$thumb_output_dir = $recording['temp_directory'] . "indexpics/";
	$err = create_remove_directory($thumb_output_dir);
	if ( !$err['code'] ) {
		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "MSG: " . $err['message'] . "\nCOMMAND: " . $err['command'] . "\nRESULT: " . $err['result'], $sendmail = true);
		return false;
	}

	// Full sized frame
	$err = create_remove_directory($thumb_output_dir . "original/");
	if ( !$err['code'] ) {
		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "MSG: " . $err['message'] . "\nCOMMAND: " . $err['command'] . "\nRESULT: " . $err['result'], $sendmail = true);
		return false;
	}

	// Wide frames
	$err = create_remove_directory($thumb_output_dir . $jconf['thumb_video_medium'] . "/");
	if ( !$err['code'] ) {
		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "MSG: " . $err['message'] . "\nCOMMAND: " . $err['command'] . "\nRESULT: " . $err['result'], $sendmail = true);
		return false;
	}

	// 4:3 frames
	$err = create_remove_directory($thumb_output_dir . $jconf['thumb_video_small'] . "/");
	if ( !$err['code'] ) {
		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "MSG: " . $err['message'] . "\nCOMMAND: " . $err['command'] . "\nRESULT: " . $err['result'], $sendmail = true);
		return false;
	}

	// High resolution wide frame
	$err = create_remove_directory($thumb_output_dir . $jconf['thumb_video_large'] . "/");
	if ( !$err['code'] ) {
		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "MSG: " . $err['message'] . "\nCOMMAND: " . $err['command'] . "\nRESULT: " . $err['result'], $sendmail = true);
		return false;
	}

	// Calculate thumbnail step
	$vthumbs_maxframes = $jconf['thumb_video_numframes'];
	if ( floor($playtime) < $vthumbs_maxframes ) $vthumbs_maxframes = floor($playtime);
	$thumb_steps = floor($playtime) / $vthumbs_maxframes;

	if ( $thumb_steps == 0 ) {
		$thumb_steps = 1;
		$vthumbs_maxframes = floor($playtime);
	}

	$log_msg = "[INFO] Video thumbnail generation summary:\nStep: " . $thumb_steps . " sec\nNumber of thumbs: " . $vthumbs_maxframes . "\nResolutions: " . $jconf['thumb_video_medium'] . ", " . $jconf['thumb_video_small'] . ", " . $jconf['thumb_video_large'];
	$time_start = time();

	// Thumbnail generation under ./indexpics by each recording element
	$res_wide   = explode("x", $jconf['thumb_video_medium'], 2);
	$res_43     = explode("x", $jconf['thumb_video_small'], 2);
	$res_high	= explode("x", $jconf['thumb_video_large'], 2);
	$filename_prefix = $recording['id'] . "_";
	$frame_number = 0;
	$iserror = false;
	$errors['messages'] = "";
	$errors['commands'] = "";
	$errors['data'] = "";
	$recording['thumbnail_size'] = 0;
	$recording['thumbnail_indexphotofilename'] = "";
	$recording['thumbnail_numberofindexphotos'] = 0;
	for ( $i = 0; $i < $vthumbs_maxframes; $i++ ) {
		$position_sec = floor($i * $thumb_steps);
		// Set filenames
		$filename_basename = $filename_prefix . sprintf("%d", $frame_number + 1) . ".jpg";
		$orig_thumb_filename = $thumb_output_dir . "original/" . $filename_basename;
		$filename_wide = $thumb_output_dir . $jconf['thumb_video_medium'] . "/" . $filename_basename;
		$filename_43 = $thumb_output_dir . $jconf['thumb_video_small'] . "/" . $filename_basename;
		$filename_highres = $thumb_output_dir . $jconf['thumb_video_large'] . "/" . $filename_basename;

//		$command  = CONVERSION_NICE . " ffmpeg -y -v " . FFMPEG_LOGLEVEL . " -ss " . $position_sec . " -i " . $master_filename . " " . $deinterlace . " -an ";
//		$command .= " -vframes 1 -r 1 -vcodec mjpeg -f mjpeg " . $orig_thumb_filename ." 2>&1";
		$command  = $jconf['nice_high'] . " " . $jconf['ffmpegthumbnailer'] . " -i " . $recording['master_filename'] . " -o " . $orig_thumb_filename . " -s0 -q8 -t" . secs2hms($position_sec) . " 2>&1";

		clearstatcache();

		$output = runExternal($command);
		$output_string = $output['cmd_output'];
		$result = $output['code'];
		// ffmpegthumbnailer (ffmpeg) returns -1 on success (?)
//echo "ffmpegthumb: " . $result . "\n";
//echo "olen = " . strlen($output_string) . "\n";
		if ( $result < 0 ) $result = 0;

		if ( ( $result != 0 ) || !file_exists($orig_thumb_filename) || ( filesize($orig_thumb_filename) < 1 ) ) {
			// If ffmpeg error, we log messages to an array and jump to first frame
			$errors['messages'] .= "[ERROR] ffmpeg failed (frame: " . $i . ", position: " . $position_sec . "sec).\n";
			$errors['commands'] .= "Frame " . $i . " command:\n" . $command . "\n\n";
			if ( strlen($output_string) > 500 ) {
				$output_len = strlen($output_string);
				$output_normalized = substr($output_string, 0, 250) . "\n...\n" . substr($output_string, $output_len - 250, $output_len);
			} else {
				$output_normalized = $output_string;
			}
			$errors['data'] .= "Frame " . $i . " output: " . $output_normalized . "\n\n";
			$iserror = true;
			continue;
		}

		if ( file_exists($orig_thumb_filename) && ( filesize($orig_thumb_filename) > 0 ) ) {
			// Copy required instances for resize
			if ( copy($orig_thumb_filename, $filename_wide) && copy($orig_thumb_filename, $filename_43) && copy($orig_thumb_filename, $filename_highres) ) {

				$is_error_now = false;

				// Resize images according to thumbnail requirements. If one of them fails we cancel all the others.
				// Wide thumb
				try {
					\Springboard\Image::resizeAndCropImage($filename_wide, $res_wide[0], $res_wide[1], 'top');
				} catch (exception $err) {
					$errors['messages'] .= "[ERROR] File " . $filename_wide . " resizeAndCropImage() failed to " . $res_wide[0] . "x" . $res_wide[1] . ".\n";
					$iserror = $is_error_now = true;
				}
				// 4:3 thumb
				try {
					\Springboard\Image::resizeAndCropImage($filename_43, $res_43[0], $res_43[1]);
				} catch (exception $err) {
					$errors['messages'] .= "[ERROR] File " . $filename_43 . " resizeAndCropImage() failed to " . $res_43[0] . "x" . $res_43[1] . ".\n";
					$iserror = $is_error_now = true;
				}
				// High resolution thumb
				try {
					\Springboard\Image::resizeAndCropImage($filename_highres, $res_high[0], $res_high[1]);
				} catch (exception $err) {
					$errors['messages'] .= "[ERROR] File " . $filename_highres . " resizeAndCropImage() failed to " . $res_high[0] . "x" . $res_high[1] . ".\n";
					$iserror = $is_error_now = true;
				}

				if ( !$is_error_now ) {

					// We have a valid frame here
					$frame_number++;
					// Select thumbnails with highest filesize (best automatic selection is supposed)
					if ( filesize($filename_43) > $recording['thumbnail_size'] ) {
						$recording['thumbnail_size'] = filesize($filename_43);
						$recording['thumbnail_indexphotofilename'] = "recordings/" . ( $recording['id'] % 1000 ) . "/" . $recording['id'] . "/indexpics/" . $jconf['thumb_video_small'] . "/" . $filename_basename;
					}
				}

			} else {

				// Rename or copy operation(s) was/were not successful
				$errors['messages'] .= "[ERROR] Cannot copy file (" . $orig_thumb_filename . ").\n";
				$iserror = true;
			}
		}
	}	// for loop

	$recording['thumbnail_numberofindexphotos'] = $frame_number;

	if ( $recording['thumbnail_numberofindexphotos'] == 0 ) $recording['thumbnail_indexphotofilename'] = "images/videothumb_audio_placeholder.png?rid=" . $recording['id'];

	$duration = time() - $time_start;
	$mins_taken = round( $duration / 60, 2);

	// Log gathered messages
	if ( $iserror ) {
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_conv_thumbs'], "[WARNINGS] " . $frame_number . " thumbnails generated (in " . $mins_taken . " mins) with the following errors:\n" . $errors['messages'] . "\n\n" . $log_msg, $errors['commands'], $errors['data'], $duration, true);
	} else {
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_conv_thumbs'], "[OK] ffmpegthumbnailer conversion OK (in " . $mins_taken . " mins)\n\n" . $log_msg, $command, "-", $duration, false);
	}

	return true;
}


// *************************************************************************
// *					function convertMedia()						   	   *
// *************************************************************************
// Description: convert media file based on encoding profile
function convertMedia(&$recording, $profile) {
global $app, $jconf, $global_log;

	// STATUS: converting
	updateRecordingVersionStatus($recording['recordingversionid'], $jconf['dbstatus_conv']);

	// Output filename
	$recording['output_basename'] = $recording['id'] . $profile['filenamesuffix'] . "." . $profile['filecontainerformat'];
	$recording['output_file'] = $recording['temp_directory'] . $recording['output_basename'];

	// Audio only: add placeholder thumbnail with a speaker icon
	if ( $recording['mastermediatype'] == "audio" ) {
		$recording['thumbnail_numberofindexphotos'] = 0;
		$recording['thumbnail_indexphotofilename'] = "images/videothumb_audio_placeholder.png?rid=" . $recording['id'];
	}

	$err = ffmpegConvert($recording, $profile);
	$recording['encodingparams'] = $err['value'];

	// Log input and target file details
	$log_msg = printMediaInfo($recording, $profile);
	$global_log .= $log_msg. "\n";

echo "AFTER CONVERSION:\n";
var_dump($recording);

	if ( !$err['code'] ) {
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_conv_err'], $err['message'] . "\nSource file: " . $recording['master_filename'] . "\nDestination file: " . $recording['output_file'] . "\n\n" . $log_msg, $err['command'], $err['command_output'], $err['duration'], true);
		return false;
	} else {
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_conv'], $err['message'] . "\nConvertion done for: " . $recording['output_file'] . "\n\n" . $log_msg, $err['command'], $err['command_output'], $err['duration'], false);
		$global_log .= "Conversion in " . secs2hms($err['duration']) . " time.\n";
	}

	$global_log .= "\n";

	return true;
}


// *************************************************************************
// *					function copyMediaToFrontEnd()			   		   *
// *************************************************************************
// Description: Copy (SCP) media file back to front-end server
function copyMediaToFrontEnd($recording, $profile) {
 global $app, $jconf, $debug;

	$idx = "";
	if ( $recording['iscontent'] != 0 ) $idx = "content";

	// SSH command templates
	$ssh_command = "ssh -i " . $jconf['ssh_key'] . " " . $jconf['ssh_user'] . "@" . $recording['mastersourceip'] . " ";
	$scp_command = "scp -B -r -i " . $jconf['ssh_key'] . " ";
	$recording['output_ssh_filename'] = $jconf['ssh_user'] . "@" . $recording[$idx . 'mastersourceip'] . ":" . $recording['recording_remote_path'];

	// Create remote directories, does nothing if exists
	$command1 = "mkdir -m " . $jconf['directory_access'] . " -p " . $app->config['recordingpath'] . ( $recording['id'] % 1000 ) . "/" . " 2>&1";
	$command2 = "mkdir -m " . $jconf['directory_access'] . " -p " . $recording['recording_remote_path'] . " 2>&1";
	$command  = $ssh_command . "\"" . $command1 . " ; " . $command2 . "\"\n";
//echo $command . "\n";
	exec($command, $output, $result);
	$output_string = implode("\n", $output);
	if ( $result != 0 ) {
		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "[ERROR] Failed creating remote directory (SSH)\nCOMMAND: " . $command . "\nRESULT: " . $output_string, $sendmail = true);
		return false;
	}

	//// Remove existing remote files (e.g. reconvert) and index thumbnails if needed
	$command = "rm -f " . $recording['recording_remote_path'] . $recording['output_basename'] . " 2>&1";
	// Remove indexpics if generated
	if ( !empty($recording['thumbnail_numberofindexphotos']) ) {
		$command .= " ; rm -f -r " . $recording['recording_remote_path'] . "indexpics/ 2>&1 ";
	}
	$command  = $ssh_command . "\"" . $command . "\"\n";
echo $command . "\n";
	exec($command, $output, $result);
	$output_string = implode("\n", $output);
	if ( $result != 0 ) $debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "[WARN] Failed removing remote files (SSH)\nCOMMAND: " . $command . "\nRESULT: " . $output_string, $sendmail = true);

	// SCP: copy converted file to front end server
	$err = ssh_filecopy($recording['mastersourceip'], $recording['output_file'], $recording['recording_remote_path'], false);
	if ( !$err['code'] ) {
		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "MSG: " . $err['message'] . "\nCOMMAND: " . $err['command'] . "\nRESULT: " . $err['result'], $sendmail = true);
		return false;
	} else {
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_copystorage'], $err['message'], $err['command'], $err['result'], $err['value'], false);
	}

	// SCP: copy video thumbnails if updated
	if ( !empty($recording['thumbnail_numberofindexphotos']) ) {
		$err = ssh_filecopy($recording['mastersourceip'], $recording['temp_directory'] . "indexpics/", $recording['recording_remote_path'], false);
		if ( !$err['code'] ) $debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "MSG: " . $err['message'] . "\nCOMMAND: " . $err['command'] . "\nRESULT: " . $err['result'], $sendmail = true);
	}

	// Update recording version information (recordings_versions)
	if ( !updateMediaInfo($recording, $profile) ) {
		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "[ERROR] Recording version update failed.", $sendmail = true);
		return false;
	}

	// Recording size: update database value
	$err = ssh_filesize($recording['mastersourceip'], $recording['recording_remote_path'] . "master/");
	$master_filesize = 0;
	if ( $err['code'] ) $master_filesize = $err['value'];
	$err = ssh_filesize($recording['mastersourceip'], $recording['recording_remote_path']);
	$recording_filesize = 0;
	if ( $err['code'] ) $recording_filesize = $err['value'];
	// Update DB
	$update = array(
		'masterdatasize'	=> $master_filesize,
		'recordingdatasize'	=> $recording_filesize
	);
	$recDoc = $app->bootstrap->getModel('recordings');
	$recDoc->select($recording['id']);
	$recDoc->updateRow($update);

	// Remove temporary directory, no failure if not successful
	$err = remove_file_ifexists($recording['temp_directory']);
	if ( !$err['code'] ) $debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "MSG: " . $err['message'] . "\nCOMMAND: " . $err['command'] . "\nRESULT: " . $err['result'], $sendmail = true);

	return true;
}

?>
