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
$app = new Springboard\Application\Cli();

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
while( !is_file( BASE_PATH . 'data/jobs/job_media_convert.stop' ) and !is_file( BASE_PATH . 'data/jobs/all.stop' ) ) {

	clearstatcache();

    while ( 1 ) {

		$app->watchdog();
	
		$db_close = FALSE;
		$converter_sleep_length = $jconf['sleep_media'];

		// Check if temp directory readable/writable
		if ( !is_writable($jconf['media_dir']) ) {
			$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "[FATAL ERROR] Temp directory " . $jconf['media_dir'] . " is not writable. Storage error???\n", $sendmail = true);
			// Sleep one hour then resume
			$converter_sleep_length = 60 * 60;
			break;
		}

		// Establish database connection
		try {
			$db = $app->bootstrap->getAdoDB();
		} catch (exception $err) {
			// Send mail alert, sleep for 15 minutes
			$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "[ERROR] No connection to DB (getAdoDB() failed). Error message:\n" . $err, $sendmail = true);
			// Sleep 15 mins then resume
			$converter_sleep_length = 15 * 60;
			break;
		}

		// Temporary directory cleanup and log result
		$err = tempdir_cleanup($jconf['media_dir']);
		if ( !$err['code'] ) log_recording_conversion(0, $jconf['jobid_media_convert'], "-", $err['message'], $err['command'], $err['result'], 0, TRUE);

		$recording = array();
		$uploader_user = array();

update_db_recording_status(1, "uploaded");

		// Query next job - exit if none
		if ( !query_nextjob($recording, $uploader_user) ) break;

		// Initialize log for closing message and total duration timer
		$global_log = "";
		$total_duration = time();

		// Start log entry
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_init'], "START PROCESSING: " . $recording['id'] . "." . $recording['mastervideoextension'], "-", "-", 0, FALSE);
		$global_log .= "Source front-end: " . $recording['mastersourceip'] . "\n";
		$global_log .= "Original filename: " . $recording['mastervideofilename'] . "\n";
		$global_log .= "Media length: " . secs2hms( $recording['masterlength'] ) . "\n";
		$global_log .= "Media type: " . $recording['mastermediatype'] . "\n\n";

		// Initialize surrogate array
		$recording['surrogates'] = array();

		// Copy media from front-end server
		if ( !copy_recording_to_converter($recording) ) break;

		// Video thumbnail generation, not a failure if fails
		convert_video_thumbnails($recording);

		// Convert audio only surrogate
		if ( !convert_audio($recording, $jconf['profile_audio']) ) {
			update_db_recording_status($recording['id'], $jconf['dbstatus_conv_audio_err']);
			break;
		}

		// Content conversion: surrogates depending on profile
		update_db_recording_status($recording['id'], $jconf['dbstatus_conv_video']);
		//// Normal quality conversion (LQ)
		if ( !convert_video($recording, $jconf['profile_video_lq'], $recording_info_lq) ) {
			update_db_recording_status($recording['id'], $jconf['dbstatus_conv_video_err']);
			break;
		}

		//// Decide about high quality conversion (HQ)
		$res = explode("x", strtolower($recording['mastervideores']), 2);
		$res_x = $res[0];
		$res_y = $res[1];
		$res = explode("x", strtolower($jconf['profile_video_lq']['video_bbox']), 2);
		$bbox_res_x = $res[0];
		$bbox_res_y = $res[1];
		//// Generate HQ version if original recording does not fit LQ bounding box
		if ( ( $res_x > $bbox_res_x ) || ( $res_y > $bbox_res_y ) ) {
			if ( !convert_video($recording, $jconf['profile_video_hq'], $recording_info_hq) ) {
				update_db_recording_status($recording['id'], $jconf['dbstatus_conv_video_err']);
				break;
			}
		}

var_dump($recording);

//var_dump($uploader_user);

echo $global_log . "\n";

break;

	}	// End of while(1)

	// Close DB connection if open
	if ( $db_close ) {
		$db->close();
	}

exit;

	$app->watchdog();

	sleep( $converter_sleep_length );
	
}	// End of outer while

exit;

/*mail küldés:
$queue = $app->bootstrap->getMailqueue(); $queue->sendHTMLEmail( $email, $subject, $body, $values = array() );
*/

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
			mastervideofilename,
			mastervideoextension,
			mastermediatype,
			mastervideocontainerformat,
			mastervideofps,
			masterlength,
			mastervideocodec,
			mastervideores,
			mastervideobitrate,
			mastervideoisinterlaced,
			masteraudiocodec,
			masteraudiochannels,
			masteraudioquality,
			masteraudiofreq,
			masteraudiobitrate,
			masteraudiobitratemode,
			status,
			masterstatus,
			conversionpriority,
			mastersourceip
		FROM
			recordings
		WHERE
		( ( status = \"" . $jconf['dbstatus_uploaded']  . "\" AND masterstatus = \"" . $jconf['dbstatus_uploaded'] . "\" ) OR
		( status = \"" . $jconf['dbstatus_reconvert'] . "\" AND masterstatus = \"" . $jconf['dbstatus_copystorage_ok'] . "\" ) ) AND 
		( mastersourceip IS NOT NULL OR mastersourceip != '' )
		AND id = 1
		ORDER BY
			conversionpriority,
			id
		LIMIT 1";

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		log_recording_conversion(0, $jconf['jobid_media_convert'], $jconf['dbstatus_init'], "[ERROR] Cannot query next media conversion job. SQL query failed.", trim($query), $err, 0, TRUE);
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
		log_recording_conversion(0, $jconf['jobid_media_convert'], $jconf['dbstatus_init'], "[ERROR] Cannot query user to media. SQL query failed.", trim($query), $err, 0, TRUE);
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
//	- $temp_directory: temporary directory for converting current media
//	- Others:
//	  o logs in local logfile and SQL DB table recordings_log
//	  o updates recording status field
function copy_recording_to_converter(&$recording) {
global $app, $jconf;

	// Update watchdog timer
	$app->watchdog();

	// Update media status
	update_db_recording_status($recording['id'], $jconf['dbstatus_copyfromfe']);

	if ( !isset($recording['mastersourceip']) ) {
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['copyfromfe'], "[ERROR] Source IP is empty, cannot identify front-end server.", "-", "-", 0, TRUE);
		update_db_recording_status($recording['id'], $jconf['dbstatus_copyfromfe_err']);
		return FALSE;
	}

	// Media is too short (fraud check)
	$playtime = ceil($recording['masterlength']);
	if ( $playtime < $jconf['video_min_length'] ) {
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_init'], "[ERROR] Media length is too short: " . $recording['source_file'], "-", "-", 0, TRUE);
		update_db_recording_status($recording['id'], $jconf['dbstatus_invalidinput']);
		return FALSE;
	}

	// SSH command template
	$ssh_command = "ssh -i " . $jconf['ssh_key'] . " " . $jconf['ssh_user'] . "@" . $recording['mastersourceip'] . " ";
	// Set upload path to default upload area
	$uploadpath = $app->config['uploadpath'] . "recordings/";
	// Media path and filename
	$base_filename = $recording['id'] . "." . $recording['mastervideoextension'];
	// Check reconvert state. In case of reconvert, we copy from recordings area
	if ( ( ( $recording['status'] == $jconf['dbstatus_reconvert'] ) && ( $recording['masterstatus'] == $jconf['dbstatus_copystorage_ok'] ) ) || ( $recording['masterstatus'] == $jconf['dbstatus_copystorage_ok'] ) ) {
		$uploadpath = $jconf['recordingpath'] . ( $recording['id'] % 1000 ) . "/" . $recording['id'] . "/master/";
	}

	$remote_filename = $jconf['ssh_user'] . "@" . $recording['mastersourceip'] . ":" . $uploadpath . $base_filename;
	$master_filename = $jconf['media_dir'] . $recording['id'] . "/master/" . $base_filename;

	$recording['remote_filename'] = $remote_filename;
	$recording['source_file'] = $master_filename;

	// SSH check file size before start copying
	$filesize = 0;
	$command = $ssh_command . "du -b " . $uploadpath . $base_filename . " 2>&1";
	exec($command, $output, $result);
	$output_string = implode("\n", $output);
	if ( $result != 0 ) {
		// If file does not exists then error is logged
		if ( strpos($output_string, "No such file or directory") > 0 ) {
			log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_copyfromfe'], "[ERROR] Input file does not exists at: " . $remote_filename, $command, $output_string, 0, TRUE);
			update_db_recording_status($recording['id'], $jconf['dbstatus_copyfromfe_err']);
			return FALSE;
		} else {
			// Other error occured, maybe locale, so we set status to "uploaded" to allow other nodes to take over the task
			log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_copyfromfe'], "[ERROR] SSH command failed", $command, $output_string, 0, TRUE);
			update_db_recording_status($recording['id'], $jconf['dbstatus_copyfromfe_err']);
			return FALSE;
		}
	} else {
		$tmp = explode(" ", $output_string, 2);
		$filesize = $tmp[0];
		if ( $filesize == 0 ) {
			log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_copyfromfe'], "[ERROR] Input file zero length: " . $remote_filename, $command, $output_string, 0, TRUE);
			update_db_recording_status($recording['id'], $jconf['dbstatus_invalidinput']);
			return FALSE;
		}
	}

	// Check available disk space (input media file size * 3 is the minimum)
	$available_disk = floor(disk_free_space($jconf['media_dir']));
	if ( $available_disk < $filesize * 3 ) {
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_copyfromfe'], "[ERROR] No enough local disk space available (needed = " . ceil(($filesize * 2) / 1024 / 1024) . "Mb, avail = " . ceil($available_disk / 1024 / 1024) . "Mb)", "php: disk_free_space(\"" . $jconf['media_dir'] . "\")", "-", 0, TRUE);
		// Set status to "uploaded" to allow other nodes to take over task
		update_db_recording_status($recording['id'], $jconf['dbstatus_uploaded']);
		return FALSE;
	}

	// Prepare temporary conversion directory, remove any existing content
	$temp_directory = $jconf['media_dir'] . $recording['id'] . "/";
	$recording['temp_directory'] = $temp_directory;
	$err = create_directory($temp_directory);
	if ( !$err['code'] ) {
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_copyfromfe'], $err['message'], $err['command'], $err['result'], 0, TRUE);
		// Set status to "uploaded" to allow other nodes to take over task
		update_db_recording_status($recording['id'], $jconf['dbstatus_uploaded']);
		return FALSE;
	}

	// Prepare master directory
	$err = create_directory($temp_directory . "master/");
	if ( !$err['code'] ) {
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_copyfromfe'], $err['message'], $err['command'], $err['result'], 0, TRUE);
		// Set status to "uploaded" to allow other nodes to take over task
		update_db_recording_status($recording['id'], $jconf['dbstatus_uploaded']);
		return FALSE;
	}

	// SCP copy from remote location
	$command = "scp -B -i " . $jconf['ssh_key'] . " " . $remote_filename . " " . $master_filename . " 2>&1";
	$time_start = time();
	exec($command, $output, $result);
	$duration = time() - $time_start;
	$mins_taken = round( $duration / 60, 2);
	$output_string = implode("\n", $output);
	if ( $result != 0 ) {
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_copyfromfe'], "[ERROR] SCP copy failed from: " . $remote_filename, $command, $output_string, $duration, TRUE);
		// Set status to "uploaded" to allow other nodes to take over task
		update_db_recording_status($recording['id'], $jconf['dbstatus_uploaded']);
		return FALSE;
	} else {
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_copyfromfe'], "[OK] SCP copy finished (in " . $mins_taken . " mins)", $command, $result, $duration, FALSE);
	}

	// Add master as a surrogate
	$recording['surrogates']['master'] = $master_filename;

	// Update watchdog timer
	$app->watchdog();

	return TRUE;
}

// *************************************************************************
// *				function convert_video_thumbnails()			   		   *
// *************************************************************************
// Description: Generate VTHUMB_MAXFRAMES number of VTHUMB_RESOLUTION resolution
//              video thumbnails extracting frames from start to end. Thumbnails are
//		croped according to thumbnail resolution set as define.
// INPUTS:
//	- $recording: recording element information
// OUTPUTS:
//	- Boolean:
//	  o FALSE: video thumbnails generation failed (error cause logged in DB and local files)
//	  o TRUE: video thumbnail generation OK
//	- Others:
//	  o logs in local logfile and SQL DB table recordings_log
//	  o updated recording_elements status field
function convert_video_thumbnails(&$recording) {
global $app, $jconf;

	// Update watchdog timer
	$app->watchdog();

	// Update conversion status
	update_db_recording_status($recording['id'], $jconf['dbstatus_conv_thumbs']);

	// Initiate recording thumbnail attributes
	$recording['thumbnail_size'] = 0;
	$recording['thumbnail_indexphotofilename'] = "";
	$recording['thumbnail_numberofindexphotos'] = 0;

	if ( $recording['mastermediatype'] == "audio" ) {
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_conv_thumbs'], "[OK] Not generating video thumbs for audio only file", "-", "-", 0, FALSE);
		return TRUE;
	}

	// Check input media resolution
	if ( empty($recording['mastervideores']) ) {
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_conv_thumbs'], "[ERROR] Video resolution field is empty. Cannot convert thumbnails. Check input media.\n", "-", "-", 0, TRUE);
		return FALSE;
	}

	$playtime = ceil($recording['masterlength']);

	// Creating thumbnail directories under ./indexpics/
	$thumb_output_dir = $recording['temp_directory'] . "indexpics/";
	$err = create_remove_directory($thumb_output_dir);
	if ( !$err['code'] ) {
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_conv_thumbs'], $err['message'], $err['command'], $err['result'], 0, TRUE);
		return FALSE;
	}

	// Full sized frame
	$err = create_remove_directory($thumb_output_dir . "original/");
	if ( !$err['code'] ) {
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_conv_thumbs'], $err['message'], $err['command'], $err['result'], 0, TRUE);
		return FALSE;
	}

	// Wide frames
	$err = create_remove_directory($thumb_output_dir . $jconf['thumb_video_resw'] . "/");
	if ( !$err['code'] ) {
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_conv_thumbs'], $err['message'], $err['command'], $err['result'], 0, TRUE);
		return FALSE;
	}

	// 4:3 frames
	$err = create_remove_directory($thumb_output_dir . $jconf['thumb_video_res43'] . "/");
	if ( !$err['code'] ) {
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_conv_thumbs'], $err['message'], $err['command'], $err['result'], 0, TRUE);
		return FALSE;
	}

	// High resolution wide frame
	$err = create_remove_directory($thumb_output_dir . $jconf['thumb_video_resw_high'] . "/");
	if ( !$err['code'] ) {
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_conv_thumbs'], $err['message'], $err['command'], $err['result'], 0, TRUE);
		return FALSE;
	}

	// Calculate thumbnail step
	$vthumbs_maxframes = $jconf['thumb_video_numframes'];
	if ( floor($playtime) < $vthumbs_maxframes ) $vthumbs_maxframes = floor($playtime);
	$thumb_steps = floor($playtime) / $vthumbs_maxframes;

	if ( $thumb_steps == 0 ) {
		$thumb_steps = 1;
		$vthumbs_maxframes = floor($playtime);
	}

	log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_conv_thumbs'], "[INFO] Video thumbnail generation summary:\nStep: " . $thumb_steps . " sec\nNumber of thumbs: " . $vthumbs_maxframes . "\nResolutions: " . $jconf['thumb_video_resw'] . ", " . $jconf['thumb_video_res43'] . ", " . $jconf['thumb_video_resw_high'], "-", "-", 0, FALSE);
	$time_start = time();

	// Thumbnail generation under ./indexpics by each recording element
	$res_wide   = explode("x", $jconf['thumb_video_resw'], 2);
	$res_43     = explode("x", $jconf['thumb_video_res43'], 2);
	$res_high	= explode("x", $jconf['thumb_video_resw_high'], 2);
	$filename_prefix = $recording['id'] . "_";
	$frame_number = 0;
	$iserror = FALSE;
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
		$filename_wide = $thumb_output_dir . $jconf['thumb_video_resw'] . "/" . $filename_basename;
		$filename_43 = $thumb_output_dir . $jconf['thumb_video_res43'] . "/" . $filename_basename;
		$filename_highres = $thumb_output_dir . $jconf['thumb_video_resw_high'] . "/" . $filename_basename;

//		$command  = CONVERSION_NICE . " ffmpeg -y -v " . FFMPEG_LOGLEVEL . " -ss " . $position_sec . " -i " . $master_filename . " " . $deinterlace . " -an ";
//		$command .= " -vframes 1 -r 1 -vcodec mjpeg -f mjpeg " . $orig_thumb_filename ." 2>&1";
		$command  = $jconf['nice'] . " ffmpegthumbnailer -i " . $recording['source_file'] . " -o " . $orig_thumb_filename . " -s0 -q8 -t" . secs2hms($position_sec);

		$output = runExternal($command);
		$output_string = $output['cmd_output'];
		$result = $output['code'];

		if ( ( $result != 0 ) || !file_exists($orig_thumb_filename) || ( filesize($orig_thumb_filename) < 1 ) ) {
			// If ffmpeg error, we log messages to an array and jump to first frame
			$errors['messages'] .= "[ERROR] ffmpeg failed (frame: " . $i . ", position: " . $position_sec . "sec).\n";
			$errors['commands'] .= "Frame " . $i . " command:\n" . $command . "\n\n";
			$errors['data'] .= "Frame " . $i . " output: " . $output_string . "\n\n";
			$iserror = TRUE;
			continue;
		}

		if ( file_exists($orig_thumb_filename) && ( filesize($orig_thumb_filename) > 0 ) ) {
			// Copy required instances for resize
			if ( copy($orig_thumb_filename, $filename_wide) && copy($orig_thumb_filename, $filename_43) && copy($orig_thumb_filename, $filename_highres) ) {

				$is_error_now = FALSE;

				// Resize images according to thumbnail requirements. If one of them fails we cancel all the others.
				// Wide thumb
				try {
					\Springboard\Image::resizeAndCropImage($filename_wide, $res_wide[0], $res_wide[1], 'top');
				} catch (exception $err) {
					$errors['messages'] .= "[ERROR] File " . $filename_wide . " resizeAndCropImage() failed to " . $res_wide[0] . "x" . $res_wide[1] . ".\n";
					$iserror = $is_error_now = TRUE;
				}
				// 4:3 thumb
				try {
					\Springboard\Image::resizeAndCropImage($filename_43, $res_43[0], $res_43[1]);
				} catch (exception $err) {
					$errors['messages'] .= "[ERROR] File " . $filename_43 . " resizeAndCropImage() failed to " . $res_43[0] . "x" . $res_43[1] . ".\n";
					$iserror = $is_error_now = TRUE;
				}
				// High resolution thumb
				try {
					\Springboard\Image::resizeAndCropImage($filename_highres, $res_high[0], $res_high[1]);
				} catch (exception $err) {
					$errors['messages'] .= "[ERROR] File " . $filename_highres . " resizeAndCropImage() failed to " . $res_high[0] . "x" . $res_high[1] . ".\n";
					$iserror = $is_error_now = TRUE;
				}

				if ( !$is_error_now ) {

					// We have a valid frame here
					$frame_number++;
					// Select thumbnails with highest filesize (best automatic selection is supposed)
					if ( filesize($filename_43) > $recording['thumbnail_size'] ) {
						$recording['thumbnail_size'] = filesize($filename_43);
						$recording['thumbnail_indexphotofilename'] = "recordings/" . ( $recording['id'] % 1000 ) . "/" . $recording['id'] . "/indexpics/" . $jconf['thumb_video_res43'] . "/" . $filename_basename;
					}
				}

			} else {

				// Rename or copy operation(s) was/were not successful
				$errors['messages'] .= "[ERROR] Cannot copy file (" . $orig_thumb_filename . ").\n";
				$iserror = TRUE;
			}
		}
	}	// for loop

	$recording['thumbnail_numberofindexphotos'] = $frame_number;

	$duration = time() - $time_start;
	$mins_taken = round( $duration / 60, 2);

	// Log gathered messages
	if ( $iserror ) {
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_conv_thumbs'], "[WARNINGS] " . $frame_number . " thumbnails generated (in " . $mins_taken . " mins) with the following errors:\n" . $errors['messages'], $errors['commands'], $errors['data'], $duration, TRUE);
	} else {
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_conv_thumbs'], "[OK] ffmpegthumbnailer conversion OK (in " . $mins_taken . " mins)\n", $command, "-", $duration, FALSE);
	}

	$recording['surrogates']['thumbnails'] = $thumb_output_dir;

	// Update watchdog timer
	$app->watchdog();

	return TRUE;
}

// *************************************************************************
// *					function convert_audio()						   *
// *************************************************************************
// Description: Calculate audio target parameters for HQ and LQ video tracks.
//	Convert audio only track as mp3.
//  1. Analyzis of recording element DB fields
//	2. Calculate HQ/LQ version audio parameters (to be passed to video conversion):
//      o HQ audio: same quality mp3, stereo downmix (use as audio only version)
//      o LQ audio: downmix to mono
//  3. Generate audio only mp3 track:
//    - mp3 audio track:
//      o HQ audio: extract the audio track (use as audio only version)
// INPUTS:
//	- $temp_directory: temporary directory for converting current media (global variable)
//	- $master_filename: full path to media file in temp directory to convert (global variable)
//	- $recording: recording element information
// OUTPUTS:
//	- Boolean:
//	  o FALSE: audio track encoding failed (error cause logged in DB and local files)
//	  o TRUE: audio track encoding OK
//	- $audio_hq, $audio_lq: HQ and LQ audio track information array
//	- Others:
//	  o logs in local logfile and SQL DB table recordings_log
//	  o updated recording_elements status field
function convert_audio(&$recording, $profile) {
global $app, $jconf, $global_log;

	// Update watchdog timer
	$app->watchdog();
 
	update_db_recording_status($recording['id'], $jconf['dbstatus_conv_audio']);

	// No audio track - return to conversion
	if ( $recording['mastermediatype'] == "videoonly" ) {
		return TRUE;
	}

	if ( $recording['mastermediatype'] == "audio" ) {
		$recording['thumbnail_numberofindexphotos'] = 0;
		$recording['thumbnail_indexphotofilename'] = "images/videothumb_audio_placeholder.png?rid=" . $recording['id'];
	}

	$playtime = ceil($recording['masterlength']);

	// Calculate audio parameters
	$audio_info = array();
	//// Basic
	$audio_info['name'] = $profile['name'];
	$audio_info['format'] = $profile['format'];
	$audio_info['playtime'] = $playtime;
	//// Source and target filenames
	$audio_info['source_file'] = $recording['source_file'];
	$extension = $profile['format'];
	$audio_info['output_file'] = $recording['temp_directory'] . $recording['id'] . $profile['file_suffix'] . "." . $extension;

	$audio_bitrate = 0;
	$audio_sample_rate = 0;
	if ( $recording['mastermediatype'] != "videoonly" ) {
		// Samplerate settings: check if applies (f4v possible samplerates: 22050Hz, 44100Hz and 48000Hz)
		if ( ( $recording['masteraudiofreq'] == 22050 ) or ( $recording['masteraudiofreq'] == 44100 ) or ( ( $recording['masteraudiofreq'] == 48000 ) and ( $profile['audio_codec'] == "libfaac" ) ) ) {
			$audio_sample_rate = $recording['masteraudiofreq'];
		} else {
			// Should not occur to have different sample rate from aboves
			if ( ( $recording['masteraudiofreq'] > 22050 ) && ( $recording['masteraudiofreq'] <= 44100 ) ) {
				$audio_sample_rate = 44100;
			} else {
				if ( $recording['masteraudiofreq'] <= 22050 ) {
					$audio_sample_rate = 22050;
				} elseif ( ( $recording['masteraudiofreq'] >= 44100 ) && ( $recording['masteraudiofreq'] < 48000 ) ) {
					$audio_sample_rate = 44100;
				} else {
					// ffmpeg only allows 22050/44100Hz sample rate mp3 with f4v, 48000Hz only possible with AAC
					if ( ( $profile['audio_codec'] == "libmp3lame" ) and ( $profile['format'] == "f4v" ) ) {
						$audio_sample_rate = 44100;
					} else {
						$audio_sample_rate = 48000;
					}
				}
			}
		}

		// Bitrate settings for audio
		$audio_bitrate_perchannel = $profile['audio_bw_ch'];
		if ( $audio_sample_rate <= 22050 ) $audio_bitrate_perchannel = 32;
		// Calculate number of channels
		$audio_info['audio_ch'] = $profile['audio_ch'];
		if ( $recording['masteraudiochannels'] < $profile['audio_ch'] ) {
			$audio_info['audio_ch'] = $recording['masteraudiochannels'];
		}
		$audio_bitrate = $audio_info['audio_ch'] * $audio_bitrate_perchannel;

		// Set audio information
		$audio_info['audio_codec'] = $profile['audio_codec'];
		$audio_info['audio_srate'] = $audio_sample_rate;
		$audio_info['audio_bitrate'] = $audio_bitrate;
		$audio_info['audio_mode'] = $profile['audio_mode'];

	}

	// Log input and target file details
	$log_msg = print_audio_info($audio_info);

	// Log into database and global log
	log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_conv_audio'], $log_msg, "-", "-", 0, FALSE);
	$global_log .= $log_msg. "\n";

	// Update watchdog timer
	$app->watchdog();

	$err = ffmpeg_convert($audio_info, $profile);
	if ( !$err['code'] ) {
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_conv_audio'], $err['message'] . "\nSource file: " . $recording['source_file'] . "\nDestination file: " . $audio_info['output_file'] . "\n\nAdditional information:\n" . $log_msg, $err['command'], $err['command_output'], $err['duration'], TRUE);
		return FALSE;
	} else {
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_conv_audio'], $err['message'] . "\nAudio track converted as: " . $audio_info['output_file'] . "\n\nAdditional information:\n" . $log_msg, $err['command'], $err['command_output'], $err['duration'], FALSE);
		$global_log .= "Audio conversion in " . secs2hms($err['duration']) . " time.\n";
	}

	// Add audio file as a surrogates
	$recording['surrogates']['audio'] = $audio_info['output_file'];

	$global_log .= "\n\n";

	$app->watchdog();

	return TRUE;
}

// ----------------------------------------------------------------------------------

function convert_video($recording, $profile, &$recording_info) {
global $app, $jconf, $global_log;

	// Update watchdog timer
	$app->watchdog();

	$recording_info = array();

	// Temp directory
	$temp_directory = $recording['temp_directory'];

	// Local master file name
	$recording_info['input_file'] = $recording['source_file'];

	// Basic video data for preliminary checks
	$video_in = array();
	$video_in['playtime'] = floor($recording['masterlength']);
	$res = explode("x", strtolower($recording['mastervideores']), 2);
	$video_in['res_x'] = $res[0];
	$video_in['res_y'] = $res[1];
	$video_in['bpp'] = $recording['mastervideobitrate'] / ( $video_in['res_x'] * $video_in['res_y'] * $recording['mastervideofps'] );
	$video_in['interlaced'] = 0;
	if ( $recording['mastervideoisinterlaced'] > 0 ) $video_in['interlaced'] = 1;

	// Max resolution check (fraud check)
	$maxres = explode("x", strtolower($jconf['video_max_res']), 2);
	if ( ( $video_in['res_x'] > $maxres[0] ) || ( $video_in['res_y'] > $maxres[1]) ) {
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_conv_video'], "[ERROR] Invalid video resolution: " . $video_in['res_x'] . "x" . $video_in['res_y'] . "\n", "-", "-", 0, TRUE);
		return FALSE;
	}

	// FPS check and conversion
	if ( $recording['mastervideofps'] > $jconf['video_max_fps'] ) {
		// Log if video FPS is higher than expected (for future finetune of interlace detection algorithm)
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_conv_video'], "[WARNING] Video FPS too high: " . $recording['mastervideofps'] . "\n", "-", "-", 0, TRUE);
	}

	// Calculate audio parameters
	$audio_bitrate = 0;
	$audio_sample_rate = 0;
	if ( $recording['mastermediatype'] != "videoonly" ) {

		// Samplerate settings: check if applies (f4v possible samplerates: 22050Hz, 44100Hz and 48000Hz)
		if ( ( $recording['masteraudiofreq'] == 22050 ) or ( $recording['masteraudiofreq'] == 44100 ) or ( ( $recording['masteraudiofreq'] == 48000 ) and ( $profile['audio_codec'] == "libfaac" ) ) ) {
			$audio_sample_rate = $recording['masteraudiofreq'];
		} else {
			// Should not occur to have different sample rate from aboves
			if ( ( $recording['masteraudiofreq'] > 22050 ) && ( $recording['masteraudiofreq'] <= 44100 ) ) {
				$audio_sample_rate = 44100;
			} else {
				if ( $recording['masteraudiofreq'] <= 22050 ) {
					$audio_sample_rate = 22050;
				} elseif ( ( $recording['masteraudiofreq'] >= 44100 ) && ( $recording['masteraudiofreq'] < 48000 ) ) {
					$audio_sample_rate = 44100;
				} else {
					// ffmpeg only allows 22050/44100Hz sample rate mp3 with f4v, 48000Hz only possible with AAC
					if ( $profile['audio_codec'] == "libmp3lame" ) {
						$audio_sample_rate = 44100;
					} else {
						$audio_sample_rate = 48000;
					}
				}
			}
		}

		// Bitrate settings for audio
		$audio_bitrate_perchannel = $profile['audio_bw_ch'];
		if ( $audio_sample_rate <= 22050 ) $audio_bitrate_perchannel = 32;
		// Calculate number of channels
		$recording_info['audio_ch'] = $profile['audio_ch'];
		if ( $recording['masteraudiochannels'] < $profile['audio_ch'] ) {
			$recording_info['audio_ch'] = $recording['masteraudiochannels'];
		}
		$audio_bitrate = $profile['audio_ch'] * $audio_bitrate_perchannel;

		// Set audio information
		$recording_info['audio_codec'] = $profile['audio_codec'];
		$recording_info['audio_srate'] = $audio_sample_rate;
		$recording_info['audio_bitrate'] = $audio_bitrate;
	}

	// Calculate video parameters
	//// Basics
	$recording_info['name'] = $profile['name'];
	$recording_info['source_file'] = $recording['source_file'];
	$recording_info['format'] = $profile['format'];
	$recording_info['video_codec'] = $profile['video_codec'];
	$recording_info['playtime'] = $video_in['playtime'];
	$recording_info['fps'] = $recording['mastervideofps'];
	$recording_info['interlaced'] = $video_in['interlaced'];
	$recording_info['video_bpp'] = $profile['video_bpp'];
	//// New resolution/scaler according to profile bounding box
	$tmp = calculate_video_scaler($video_in['res_x'], $video_in['res_y'], $profile['video_bbox']);
	$recording_info['scaler'] = $tmp['scaler'];
	$recording_info['res_x'] = $tmp['x'];
	$recording_info['res_y'] = $tmp['y'];
	//// Calculate bitrate and maximize it to avoid too high values
/*	if ( $video_in['bpp'] < $profile['video_bpp'] ) {
		$recording_info['video_bpp'] = round($video_in['bpp'], 2);
	}
echo "bpp profile: " . $profile['video_bpp'] . " | orig: " . $video_in['bpp'] . " | chosen: " . $recording_info['video_bpp'] . "\n"; */
	$recording_info['video_bitrate'] = $recording_info['video_bpp'] * $recording_info['res_x'] * $recording_info['res_y'] * $recording_info['fps'];
	if ( $recording_info['video_bitrate'] > $jconf['video_max_bw'] ) $recording_info['video_bitrate'] = $jconf['video_max_bw'];
	//// Target filename
	$extension = $profile['format'];
	if ( $extension == "flv" ) $extension = "f4v";
	$recording_info['output_file'] = $temp_directory . $recording['id'] . $profile['file_suffix'] . "." . $extension;

	// Log input and target file details
	$log_msg = print_recording_info($recording_info);

	// Log into database and global log
	log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_conv_video'], $log_msg, "-", "-", 0, FALSE);
	$global_log .= $log_msg. "\n";

	// Update watchdog timer
	$app->watchdog();

	// Video conversion execution
	$err = ffmpeg_convert($recording_info, $profile);
	if ( !$err['code'] ) {
		$log_msg = $err['message'] . "\n" . $profile['name'] . " conversion failed.\nSource file: " . $recording_info['input_file'] . "\nDestination file: " . $recording_info['output_file'];
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_conv_video'], $log_msg, $err['command'], $err['command_output'], $err['duration'], TRUE);
		return FALSE;
	} else {
		$log_msg = $err['message'] . "\n" . $profile['name'] . " conversion OK.\nSource file: " . $recording_info['input_file'] . "\nDestination file: " . $recording_info['output_file'];
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_conv_video'], $log_msg, $err['command'], $err['command_output'], $err['duration'], FALSE);
		$global_log .= $profile['name'] . " conversion in " . secs2hms($err['duration']) . " time.\n";
	}

	// Update watchdog timer
	$app->watchdog();

	$global_log .= "\n";

	return TRUE;
}





?>
