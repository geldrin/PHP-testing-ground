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
		if ( $uploader_user === false ) break;

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
echo "!!! thumbconv\n";
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

// ??? logot itt? ffmpeg logging drop?
		//// End of media conversion
		$global_log .= "URL: http://" . $uploader_user['domain'] . "/" . $uploader_user['language'] . "/recordings/details/" . $recording['id'] . "\n\n";
		$conversion_duration = time() - $total_duration;
		$hms = secs2hms($conversion_duration);
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], "-", "[OK] Successful media conversion in " . $hms . " time.\n\nConversion summary:\n\n" . $global_log, "-", "-", $conversion_duration, true);

exit;

		break;
	}	// End of while(1)

	// Close DB connection if open
	if ( is_resource($db->_connectionID) ) $db->close();

	$app->watchdog();

	sleep($converter_sleep_length);
	
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
function query_nextjob() {
//function query_nextjob(&$recording) {
global $jconf, $debug, $db;

	$db = db_maintain();

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
			mastervideodar,
			masteraudiocodec,
			masteraudiochannels,
			masteraudioquality,
			masteraudiofreq,
			masteraudiobitrate,
			masteraudiobitratemode,
			status,
			masterstatus,
			contentstatus,
			contentmasterstatus,
			conversionpriority,
			mastersourceip
		FROM
			recordings
		WHERE
			( ( status = \"" . $jconf['dbstatus_uploaded']  . "\" AND masterstatus = \"" . $jconf['dbstatus_uploaded'] . "\" ) OR
			( status = \"" . $jconf['dbstatus_reconvert'] . "\" AND masterstatus = \"" . $jconf['dbstatus_copystorage_ok'] . "\" ) ) AND 
			( mastersourceip IS NOT NULL OR mastersourceip != '' )
		ORDER BY
			conversionpriority,
			id
		LIMIT 1";

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		log_recording_conversion(0, $jconf['jobid_media_convert'], $jconf['dbstatus_init'], "[ERROR] Cannot query next media conversion job. SQL query failed.", trim($query), $err, 0, TRUE);
		return false;
	}

	// Check if pending job exsits
	if ( $rs->RecordCount() < 1 ) {
		return false;
	}

	$recording = $rs->fields;

	return $recording;
}

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

// Full rewrite: pehelykonnyu, egyszeru kod
// 1. Megnezzuk megvan-e mar az adott fajl es egyezik-e a filesize a temp directory-ban:
//	- IGEN: letoltjuk
//	- NEM: hasznaljuk a meglevot. KERDES: Mi van ha kezzel felulirjuk es reconvert? (filesize? file date?)
// Megallapitasok:
//	- Nem akarunk tudni ennyi statusz informaciot, csak "converting" elegendo, hiszen a reszletes logobol minden kideritheto

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
	$uploadpath = $app->config['uploadpath'] . "recordings/";
	// Reconvert state: copy from storage area
// ???
	$recording['conversion_type'] = "convert";
// !!! total mashogy kell kezelni!!!
/*	if ( ( ( $recording['status'] == $jconf['dbstatus_reconvert'] ) && ( $recording['masterstatus'] == $jconf['dbstatus_copystorage_ok'] ) ) || ( $recording['masterstatus'] == $jconf['dbstatus_copystorage_ok'] ) ) {
		$uploadpath = $app->config['recordingpath'] . ( $recording['id'] % 1000 ) . "/" . $recording['id'] . "/master/";
		$recording['conversion_type'] = $jconf['dbstatus_reconvert'];
	}
*/
	if ( $recording['status'] == $jconf['dbstatus_reconvert'] ) {
		if ( $recording[$idx . 'masterstatus'] == $jconf['dbstatus_copystorage_ok'] ) $uploadpath = $app->config['recordingpath'] . ( $recording['id'] % 1000 ) . "/" . $recording['id'] . "/master/";
// ???
		$recording['conversion_type'] = $jconf['dbstatus_reconvert'];
	}

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
		// Set status to "uploaded" to allow other nodes to take over task
// ???
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
		// Set status to "uploaded" to allow other nodes to take over task
// ???
//		update_db_recording_status($recording['id'], $jconf['dbstatus_uploaded']);
		return false;
	}

	// SCP: copy from front end server
	$err = ssh_filecopy($recording['mastersourceip'], $recording['master_remote_filename'], $recording['master_filename'], true);
	if ( !$err['code'] ) {
		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "MSG: " . $err['message'] . "\nCOMMAND: " . $err['command'] . "\nRESULT: " . $err['result'], $sendmail = true);
		// Set status to "uploaded" to allow other nodes to take over task
// ???
		return false;
	}
	log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_copyfromfe'], $err['message'], $err['command'], $err['result'], $err['value'], false);

	return true;
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
	$err = create_remove_directory($thumb_output_dir . $jconf['thumb_video_medium'] . "/");
	if ( !$err['code'] ) {
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_conv_thumbs'], $err['message'], $err['command'], $err['result'], 0, TRUE);
		return FALSE;
	}

	// 4:3 frames
	$err = create_remove_directory($thumb_output_dir . $jconf['thumb_video_small'] . "/");
	if ( !$err['code'] ) {
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_conv_thumbs'], $err['message'], $err['command'], $err['result'], 0, TRUE);
		return FALSE;
	}

	// High resolution wide frame
	$err = create_remove_directory($thumb_output_dir . $jconf['thumb_video_large'] . "/");
	if ( !$err['code'] ) {
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_conv_thumbs'], $err['message'], $err['command'], $err['result'], 0, TRUE);
		return FALSE;
	}

//echo "playtime = " . secs2hms($playtime) . "\n";

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
						$recording['thumbnail_indexphotofilename'] = "recordings/" . ( $recording['id'] % 1000 ) . "/" . $recording['id'] . "/indexpics/" . $jconf['thumb_video_small'] . "/" . $filename_basename;
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

	if ( $recording['thumbnail_numberofindexphotos'] == 0 ) $recording['thumbnail_indexphotofilename'] = "images/videothumb_audio_placeholder.png?rid=" . $recording['id'];

	$duration = time() - $time_start;
	$mins_taken = round( $duration / 60, 2);

	// Log gathered messages
	if ( $iserror ) {
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_conv_thumbs'], "[WARNINGS] " . $frame_number . " thumbnails generated (in " . $mins_taken . " mins) with the following errors:\n" . $errors['messages'] . "\n\n" . $log_msg, $errors['commands'], $errors['data'], $duration, TRUE);
	} else {
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_conv_thumbs'], "[OK] ffmpegthumbnailer conversion OK (in " . $mins_taken . " mins)\n\n" . $log_msg, $command, "-", $duration, FALSE);
	}

	// Update watchdog timer
	$app->watchdog();

	return TRUE;
}

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
// *					function convert_audio()						   *
// *************************************************************************
// Description: Generate audio only mp3 track.
// INPUTS:
//	- $recording: recording element information
// OUTPUTS:
//	- Boolean:
//	  o FALSE: audio track encoding failed (error cause logged in DB and local files)
//	  o TRUE: audio track encoding OK
//	- $recording: all important info is injected into recording array
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
	$audio_info['source_file'] = $recording['master_filename'];
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
	$global_log .= $log_msg. "\n";

	// Update watchdog timer
	$app->watchdog();

	$err = ffmpeg_convert($audio_info, $profile);
	if ( !$err['code'] ) {
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_conv_audio'], $err['message'] . "\nSource file: " . $recording['master_filename'] . "\nDestination file: " . $audio_info['output_file'] . "\n\n" . $log_msg, $err['command'], $err['command_output'], $err['duration'], TRUE);
		return FALSE;
	} else {
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_conv_audio'], $err['message'] . "\nAudio track converted as: " . $audio_info['output_file'] . "\n\n" . $log_msg, $err['command'], $err['command_output'], $err['duration'], FALSE);
		$global_log .= "Audio conversion in " . secs2hms($err['duration']) . " time.\n";
	}

	$global_log .= "\n";

	$app->watchdog();

	return TRUE;
}

// Convert media file based on profile
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

/*
echo "------------------------------\n";

echo $log_msg . "\n";

echo "------------------------------\n";
*/
var_dump($recording);
//exit;
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
// *		     function copy_media_to_frontend()			   			   *
// *************************************************************************
// Description: Copy (SCP) media file(s) and video thumbnails back to front-end.
// INPUTS:
//	- $recording['temp_directory']: temporary directory for converting current media (global variable)
//	- $master_filename: full path to media file in temp directory to convert (global variable)
//	- $recording: recording element information
//	- $audio_hq, $audio_lq, $video_hq, $video_lq: HQ and LQ audio/video track information
// OUTPUTS:
//	- Boolean:
//	  o FALSE: audio track encoding failed (error cause logged in DB and local files)
//	  o TRUE: audio track encoding OK
//	- Others:
//	  o Media file(s) and video thumbnails on front-end machine
//	  o Log entries (file and database)
function copy_media_to_frontend($recording, $recording_info_mobile_lq, $recording_info_mobile_hq, $recording_info_lq, $recording_info_hq) {
 global $app, $jconf, $debug;

	$app->watchdog();

	update_db_recording_status($recording['id'], $jconf['dbstatus_copystorage']);

	// Reconvert: remove master file (do not copy back as already in place)
/*	if ( $recording['conversion_type'] == $jconf['dbstatus_reconvert'] ) {
		$err = remove_file_ifexists($recording['master_filename']);
		if ( !$err['code'] ) log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_copystorage'], $err['message'], $err['command'], $err['result'], 0, TRUE);
	}
*/

	// SSH command templates
	$ssh_command = "ssh -i " . $jconf['ssh_key'] . " " . $jconf['ssh_user'] . "@" . $recording['mastersourceip'] . " ";
	$scp_command = "scp -B -r -i " . $jconf['ssh_key'] . " ";
//	$remote_recording_directory = $app->config['recordingpath'] . ( $recording['id'] % 1000 ) . "/" . $recording['id'] . "/";

	// Target path for SCP command
	$remote_path = $jconf['ssh_user'] . "@" . $recording['mastersourceip'] . ":" . $app->config['recordingpath'] . ( $recording['id'] % 1000 ) . "/";

	// Create remote directories, does nothing if exists
	$command = $ssh_command . "mkdir -m " . $jconf['directory_access'] . " -p " . $app->config['recordingpath'] . ( $recording['id'] % 1000 ) . " 2>&1";
	exec($command, $output, $result);
	$output_string = implode("\n", $output);
	if ( $result != 0 ) {
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_copystorage'], "[ERROR] Failed creating remote directory (SSH)", $command, $output_string, 0, TRUE);
		return FALSE;
	}

	//// Remove all previous files from recording directory (from previous conversions)
	// Media files: audio only version (_audio.mp3), LQ and HQ (_video_lq.mp4 and _video_hq.mp4) and mobile (_mobile_lq.mp4 and _mobile_hq.mp4) versions
	$media_regex = ".*" . $recording['id'] . "_\(audio\.mp3\|video_lq\.mp4\|video_hq\.mp4\)";
	if ( $recording['is_mobile_convert'] ) {
		// Remove mobile version if converted
		$media_regex = ".*" . $recording['id'] . "_\(audio\.mp3\|video_lq\.mp4\|video_hq\.mp4\|mobile_lq\.mp4\|mobile_hq\.mp4\)";
	}
	$command1 = "find " . $recording['recording_remote_path'] . " -mount -maxdepth 1 -type f -regex '" . $media_regex . "' -exec rm -f {} \\; 2>/dev/null";
	// Indexpic: all recording related .jpg thumbnails and full sized .png images
	$command2 = "find " . $recording['recording_remote_path'] . "indexpics/" . " -mount -mindepth 1 -maxdepth 1 -type d -exec rm -r -f {} \\; 2>/dev/null";
	$command = $ssh_command . "\"" . $command1 . " ; " . $command2 . "\"";
	exec($command, $output, $result);

	// SCP copy from local temp to remote location
	$command = $scp_command . $recording['temp_directory'] . " " . $remote_path . " 2>&1";
	$time_start = time();
	exec($command, $output, $result);
	$duration = time() - $time_start;
	$mins_taken = round( $duration / 60, 2);
	$output_string = implode("\n", $output);
	if ( $result != 0 ) {
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_copystorage'], "[ERROR] SCP copy failed to: " . $recording['master_filename_remote'], $command, $output_string, $duration, TRUE);
		return FALSE;
	} else {
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_copystorage'], "[OK] SCP copy finished (in " . $mins_taken . " mins)", $command, $result, $duration, FALSE);
		//// Set file and directory access rights
		$command = "";
		// Recording directory and surrogate media file access rights
		if ( $recording['conversion_type'] != $jconf['dbstatus_reconvert'] ) {
			$command .= "chmod -f " . $jconf['directory_access'] . " " . $recording['recording_remote_path'] . " ; ";
			$command .= "chmod -f " . $jconf['file_access']      . " " . $recording['recording_remote_path'] . "/master/*_video.* ; ";
		}
		// Surrogates
		$command .= "find " . $recording['recording_remote_path'] . " -mount -maxdepth 1 -type f -regex '" . $media_regex . "' -exec chmod -f " . $jconf['file_access'] . " {} \\; 2>/dev/null ; ";
		// Index pics: all directories and files under this level
		$command .= "find " . $recording['recording_remote_path'] . "indexpics/ -mount -maxdepth 1 -type d -exec chmod -f " . $jconf['directory_access'] . " {} \\; 2>/dev/null ; ";
		$command .= "find " . $recording['recording_remote_path'] . "indexpics/ -mount -maxdepth 2 -type f -exec chmod -f " . $jconf['file_access'] . " {} \\; 2>/dev/null ; ";
		$command = $ssh_command . "\"" . $command . "\"";
		exec($command, $output, $result);
		$output_string = implode("\n", $output);
		if ( $result != 0 ) {
			log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_copystorage'], "[WARNING] Cannot stat media files.", $command, $output_string, 0, TRUE);
			return FALSE;
		}
	}

	// Update database (status and media information)
	if ( $recording['mastermediatype'] != "audio" ) {
		// Video media: set MOBILE, LQ and HQ resolution and thumbnail data
		$err = update_db_mediainfo($recording, $recording_info_mobile_lq, $recording_info_mobile_hq, $recording_info_lq, $recording_info_hq);
		if ( !$err ) {
			log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_copystorage'], "[ERROR] Media video info final DB update failed", "-", $err, 0, TRUE);
			return FALSE;
		}
	} else {
		// Audio only media: set only default thumbnail picture for audio only recordings
		$err = update_db_mediainfo($recording, null, null, null, null);
		if ( !$err ) {
			log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_copystorage'], "[ERROR] Media audio thumbnail info final DB update failed", "-", $err, 0, TRUE);
			return FALSE;
		}
	}

	// Update recording status
	update_db_recording_status($recording['id'], $jconf['dbstatus_copystorage_ok']);
	update_db_masterrecording_status($recording['id'], $jconf['dbstatus_copystorage_ok']);
	// If mobile version was converted, then set status
	if ( $recording['is_mobile_convert'] ) {
		update_db_mobile_status($recording['id'], $jconf['dbstatus_copystorage_ok']);
	} else {
		// If not, set "reconvert" for content converter
		update_db_mobile_status($recording['id'], $jconf['dbstatus_reconvert']);
	}

	// Remove master from upload area if not reconvert!
/*	if ( $recording['conversion_type'] != $jconf['dbstatus_reconvert'] ) {
		$uploadpath = $app->config['uploadpath'] . "recordings/";
		$suffix = "video";
		if ( $recording['mastermediatype'] == "audio" ) $suffix = "audio";
		$base_filename = $recording['id'] . "_" . $suffix . "." . $recording['mastervideoextension'];
		$err = ssh_fileremove($recording['mastersourceip'], $uploadpath . $base_filename);
		if ( !$err['code'] ) log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_copystorage'], $err['message'], $err['command'], $err['result'], 0, TRUE);
	}
*/

	// Update recording size
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
//	$err = remove_file_ifexists($recording['temp_directory']);
//	if ( !$err['code'] ) log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_copystorage'], $err['message'], $err['command'], $err['result'], 0, TRUE);
// !!! TEMP CODE
	$command1 = "find " . $recording['temp_directory'] . " -mount -maxdepth 1 -type f -regex '" . $media_regex . "' -exec rm -f {} \\; 2>/dev/null";
	// Indexpic: all recording related .jpg thumbnails and full sized .png images
	$command2 = "rm -r -f " . $recording['temp_directory'] . "indexpics/ 2>/dev/null";
	$command = $command1 . " ; " . $command2;
//	echo $command . "\n";
	exec($command, $output, $result);

	$app->watchdog();

	return TRUE;
}

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
echo $command . "\n";
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

	// SCP copy from local temp to remote location
/*	$command = $scp_command . $recording['output_file'] . " " . $recording['output_ssh_filename'] . " 2>&1";
echo $command . "\n";
	$time_start = time();
	exec($command, $output, $result);
	$duration = time() - $time_start;
	$mins_taken = round( $duration / 60, 2);
	$output_string = implode("\n", $output);
	if ( $result != 0 ) {
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_copystorage'], "[ERROR] SCP copy failed to: " . $recording['recording_remote_path'] . $recording['output_basename'], $command, $output_string, $duration, true);
		return false;
	} else {
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_copystorage'], "[OK] SCP copy finished (in " . $mins_taken . " mins)", $command, $result, $duration, false);
		//// Set file and directory access rights
		$command = "chmod -f " . $jconf['file_access'] . " " . $recording['recording_remote_path'] . $recording['output_basename'];
echo $command . "\n";
		$command = $ssh_command . "\"" . $command . "\"";
		exec($command, $output, $result);
		$output_string = implode("\n", $output);
		if ( $result != 0 ) {
			$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "[WARNING] Cannot stat media files.\nCOMMAND: " . $command . "\nRESULT: " . $output_string, $sendmail = true);
			return false;
		}
	}
*/

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

	// Update recording version information
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


// Kell-e szurni valamivel?
/*function query_encoding_profiles() {
 global $jconf, $db;

	$db = db_maintain();
	$unset($encoding_profiles);

	$query = "
		SELECT
			eg.id AS encodinggroupid,
			eg.name AS encodinggroupname,
			ep.name,
			ep.shortname,
			ep.type,
			ep.mediatype,
			ep.isdesktopcompatible,
			ep.isioscompatible,
			ep.isandroidcompatible,
			ep.filenamesuffix,
			ep.filecontainerformat,
			ep.videocodec,
			ep.videopasses,
			ep.videobboxsizex,
			ep.videobboxsizey,
			ep.videomaxfps,
			ep.videobpp,
			ep.ffmpegh264profile,
			ep.ffmpegh264preset,
			ep.audiocodec,
			ep.audiomaxchannels,
			ep.audiobitrateperchannel,
			ep.audiomode,
			ep.pipenabled,
			ep.pipcodecprofile,
			ep.pipposx,
			ep.pipposy,
			ep.pipalign,
			ep.pipsize
			disabled
		FROM
			encoding_groups AS eg,
			encoding_profiles_groups AS epg,
			encoding_profiles AS ep
		WHERE
			eg.default = 1 AND
			eg.disabled = 0 AND
			eg.id = epg.encodingprofilegroupid AND
			epg.encodingprofileid = ep.id AND
			ep.disabled = 0
		ORDER BY
			epg.encodingorder
	";


	try {
		$encoding_profiles = $db->Execute($query);
	} catch (exception $err) {
		log_recording_conversion(0, $jconf['jobid_media_convert'], $jconf['dbstatus_init'], "[ERROR] Cannot query next media conversion job. SQL query failed.", trim($query), $err, 0, TRUE);
		return FALSE;
	}

	// Check if any encoding profiles exist
	if ( $encoding_profiles->RecordCount() < 1 ) {
		return FALSE;
	}

	return $encoding_profiles;
}
*/

?>
