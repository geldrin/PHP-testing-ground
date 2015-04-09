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
include_once('job_utils_media2.php');

set_time_limit(0);

// Init
$app = new Springboard\Application\Cli(BASE_PATH, PRODUCTION);

// Load jobs configuration file
$app->loadConfig('modules/Jobs/config_jobs.php');
$jconf = $app->config['config_jobs'];
$myjobid = $jconf['jobid_media_convert'];

// Log related init
$debug = Springboard\Debug::getInstance();
$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "*************************** Job: Media conversion started ***************************" ."\n", $sendmail = false);

// Check operating system - exit if Windows
if ( iswindows() ) {
	echo "ERROR: Non-Windows process started on Windows platform\n";
	exit;
}
// DEBUG
$recording = array(
		'id' => 148,
		'master_path' => null,
		'mastervideoextension' => 'webm',
	);
$recording['master_path'] = $jconf['master_dir'] . $recording['id'] . "/";
$err = directory_size($recording['master_path'] ."*.". $recording['mastervideoextension']);
var_dump($err);
exit;
// DEBUG
// Log buffer
$log_buffer = array();

// Start an infinite loop - exit if any STOP file appears
while( !is_file( $app->config['datapath'] . 'jobs/' . $myjobid . '2.stop' ) and !is_file( $app->config['datapath'] . 'jobs/all.stop' ) ) {
	clearstatcache();
	
	while ( 1 ) {

		$app->watchdog();
	
		// Establish database connection
		$db = null;
		$db = db_maintain();

		$converter_sleep_length = $app->config['sleep_media'];

		// Check if temp directory readable/writable
		if ( !is_writable($jconf['media_dir']) ) {
			$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "[FATAL ERROR] Temp directory " . $jconf['media_dir'] . " is not writable. Storage error???\n", $sendmail = true);
			// Sleep one hour then resume
			$converter_sleep_length = 60 * 60;
			break;
		}

		// Query next job
		$recording = getNextConversionJob();
		if ( $recording === false ) break;

		// Log job information
		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "[INFO] Recording id = " . $recording['id'] . " selected for conversion. Recording information:\n" . print_r($recording, true), $sendmail = false);

		// Query recording creator
		$uploader_user = getRecordingCreator($recording['id']);
		if ( $uploader_user === false ) unset($uploader_user);

		// Query encoding profile
		$encoding_profile = getEncodingProfile($recording['encodingprofileid']);
		if ( $encoding_profile === false ) break;

		// Log encoding profile information
		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "[INFO] Encoding profile id = " . $encoding_profile['id'] . " selected. Profile information:\n" . print_r($encoding_profile, true), $sendmail = false);

		// Initialize log for summary mail message and total duration timer
		$total_duration = time();

		// Start log entry
		$global_log  = "Converting: " . $recording['id'] . "." . $recording['mastervideoextension'] . " (" . $encoding_profile['name'] . ")\n";
		$global_log .= "Source front-end: " . $recording['mastersourceip'] . "\n";
		$global_log .= "Original filename: " . $recording['mastervideofilename'] . "\n";
		$global_log .= "Media length: " . secs2hms( $recording['masterlength'] ) . "\n";
		$global_log .= "Media type: " . $recording['mastermediatype'] . "\n";
		$global_log .= "Encoding profile: " . $encoding_profile['name'] . "\n\n";
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_init'], $global_log, "-", "-", 0, false);

		// DOWNLOAD: download media from front-end server
		$err = copyMediaToConverter($recording);

		// Check if we need to stop conversion (do not handle error)
		if ( checkRecordingVersionToStop($recording) ) break;
		if ( !$err ) {
			// recordings_versions.status = "failedcopyingfromfrontend"
			updateRecordingVersionStatus($recording['recordingversionid'], $jconf['dbstatus_copyfromfe_err']);
			break;
		}
		// Picture-in-picture encoding: download content file as well
		if ( $encoding_profile['type'] == "pip" && (
		$recording['contentmasterstatus'] == $jconf['dbstatus_copystorage_ok'] ||
		$recording['contentmasterstatus'] == $jconf['dbstatus_uploaded'] )) {
			$recording['iscontent'] = 1;
			$err = copyMediaToConverter($recording); 
			// Check if we need to stop conversion (do not handle error)
			if ( checkRecordingVersionToStop($recording) ) break;
			if ( !$err ) {
				// recordings_versions.status = "failedcopyingfromfrontend"
				updateRecordingVersionStatus($recording['recordingversionid'], $jconf['dbstatus_copyfromfe_err']);
				break;
			}
		}

		// recordings_versions.status = "copiedfromfrontend"
		updateRecordingVersionStatus($recording['recordingversionid'], $jconf['dbstatus_copyfromfe_ok']);

		// Watchdog
		$app->watchdog();

		// Video thumbnail generation (when first video is converted)
		if ( empty($encoding_profile['parentid']) and ( $encoding_profile['type'] == "recording" ) and ( $encoding_profile['mediatype'] == "video" ) ) {
			$err = convertVideoThumbnails($recording);
			// Check if we need to stop conversion
			if ( checkRecordingVersionToStop($recording) ) break;
		}

		// Watchdog
		$app->watchdog();

		// CONVERT: convert recording version
		$err = convertMedia($recording, $encoding_profile);
		// Check if we need to stop conversion
		if ( checkRecordingVersionToStop($recording) ) break;
		if ( !$err ) {
			updateRecordingVersionStatus($recording['recordingversionid'], $jconf['dbstatus_conv_err']);
			break;
		}

		// Watchdog
		$app->watchdog();

		// UPLOAD: upload resulted file to front-end
		$err = copyMediaToFrontEnd($recording, $encoding_profile);
		// Check if we need to stop conversion
		if ( checkRecordingVersionToStop($recording) ) break;
		if ( !$err ) {
			// recordings_versions.status = "failedcopyingtostorage"
			updateRecordingVersionStatus($recording['recordingversionid'], $jconf['dbstatus_copystorage_err']);
			break;
		}
		// recordings_versions.status = "onstorage"
		updateRecordingVersionStatus($recording['recordingversionid'], $jconf['dbstatus_copystorage_ok']);
		// recordings.(content)smilstatus = "regenerate" (new version is ready, regenerate SMIL file)
		if ( ( $encoding_profile['type'] != "pip" ) and ( $encoding_profile['mediatype'] != "audio" ) ) {
			$type = "smil";
			if  ( $recording['iscontent'] == 1 ) $type = "contentsmil";
			updateRecordingStatus($recording['id'], $jconf['dbstatus_regenerate'], $type);
		}

		// Watchdog
		$app->watchdog();

		//// End of media conversion
		if ( isset($uploader_user) ) $global_log .= "URL: http://" . $uploader_user['domain'] . "/" . $uploader_user['language'] . "/recordings/details/" . $recording['id'] . "\n\n";
		$conversion_duration = time() - $total_duration;
		$hms = secs2hms($conversion_duration);
		if ( !isset($log_buffer[$recording['id']]) ) {
			$log_buffer[$recording['id']] = $global_log;
		} else {
			$log_buffer[$recording['id']] .= "---\n\n" . $global_log;
		}
		// Log this recording version conversion summary
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], "-", "[OK] Successful media conversion in " . $hms . " time.\n\nConversion summary:\n\n" . $global_log, "-", "-", $conversion_duration, false);
		// Have we finished? Then send all logs to admin
		if ( getRecordingVersionsApplyStatusFilter($recording['id'], $type = "all", "convert|reconvert") === false ) {
			$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "[INFO] Recording conversion summary.\n\n" . $log_buffer[$recording['id']], $sendmail = true);
			unset($log_buffer[$recording['id']]);
		}
	}	// End of while(1)
	// Close DB connection if open
	if ( is_resource($db->_connectionID) ) $db->close();

	$app->watchdog();

	sleep($converter_sleep_length);
}	// End of outer while

$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "Media conversion job interrupted.", $sendmail = false);

exit;


// *************************************************************************
// *                    function getNextConversionJob()                    *
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
			rv.status AS recordingversionstatus,
			rv.filename,
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
			r.contentmastervideofilename,
			r.contentmastervideoextension,
			r.contentmastermediatype,
			r.contentmastervideocontainerformat,
			r.contentmastervideofps,
			r.contentmasterlength,
			r.contentmastervideocodec,
			r.contentmastervideores,
			r.contentmastervideobitrate,
			r.contentmastervideoisinterlaced,
			r.contentmastervideodar,
			r.contentmasteraudiocodec,
			r.contentmasteraudiochannels,
			r.contentmasteraudioquality,
			r.contentmasteraudiofreq,
			r.contentmasteraudiobitrate,
			r.contentmasteraudiobitratemode,
			r.status,
			r.masterstatus,
			r.contentstatus,
			r.contentmasterstatus,
			r.ocrstatus,
			r.mastersourceip,
			r.contentmastersourceip,
			r.encodinggroupid,
			eg.islegacy
		FROM
			recordings_versions AS rv,
			recordings AS r,
			encoding_groups AS eg,
			infrastructure_nodes AS inode
		WHERE
			( rv.status = '". $jconf['dbstatus_convert'] ."' OR rv.status = '". $jconf['dbstatus_reconvert'] ."' ) AND
			rv.recordingid = r.id AND
			r.encodinggroupid = eg.id AND
			rv.converternodeid = inode.id AND
			inode.server = '". $node ."' AND
            inode.type = 'converter' AND
			inode.disabled = 0
		ORDER BY
			rv.encodingorder,
			rv.recordingid
		LIMIT 1";
	try {
		$recording = $db->getArray($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
		return false;
	}

	// Check if any record returned
	if ( count($recording) < 1 ) return false;

	return $recording[0];
}


// *************************************************************************
// *                  function copyMediaToConverter()                      *
// *************************************************************************
// Description: download media from converter
function copyMediaToConverter(&$recording) {
global $app, $jconf, $debug;

    $db = db_maintain();

	// STATUS: copyingfromfrontend
	updateRecordingVersionStatus($recording['recordingversionid'], $jconf['dbstatus_copyfromfe']);

	//// Filenames and paths
	// Base filename
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

	//// Directories: assemble master and temporary directory paths
	// Master: caching directory
	$recording[      'master_remote_path'    ] = $uploadpath;
	$recording[      'master_path'           ] = $jconf['master_dir'] . $recording['id'] . "/";
	$recording[$idx .'master_basename'       ] = $recording['id'] . "_" . $suffix . "." . $recording[$idx .'mastervideoextension'];
	$recording[$idx .'master_remote_filename'] = $recording['master_remote_path'] . $recording[$idx .'master_basename'];
	$recording[$idx .'master_filename'       ] = $recording['master_path'] . $recording[$idx .'master_basename'];
	$recording[$idx .'master_ssh_filename'   ] = $app->config['ssh_user'] . "@" . $recording[$idx . 'mastersourceip'] . ":" . $recording[$idx .'master_remote_filename'];
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
	$err = ssh_file_cmp_isupdated($recording[$idx . 'mastersourceip'], $recording[$idx .'master_remote_filename'], $recording[$idx .'master_filename']);
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
	} elseif ($err['code'] === 0) {
	// remove any existing passlogfiles, from directory
		$passlogfiles = array(
			$ffmpeg_pass_prefix = $recording['master_path'] . $recording['id'] ."_". $profile['type'] ."_passlog",
			$ffmpeg_passlogfile = $ffmpeg_pass_prefix ."-0.log",
		);
		foreach($passlogfiles as $plf) {
			$err = remove_file_ifexists($plf);
			if (!$err['code']) {
				$msg = "[ERROR] Removing passlogfiles failed, error message: ". $err['message'];
				$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", $msg, $sendmail = true);
				return false;
			}
		}
	}

	// SCP: copy from front end server
	$err = ssh_filecopy2($recording[$idx . 'mastersourceip'], $recording[$idx .'master_remote_filename'], $recording[$idx .'master_filename'], true);
	if ( !$err['code'] ) {
		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "MSG: " . $err['message'] . "\nCOMMAND: " . $err['command'] . "\nRESULT: " . $err['result'], $sendmail = true);
		// Set status to "uploaded" to allow other nodes to take over task??? !!!
		return false;
	}
	log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_copyfromfe'], $err['message'], $err['command'], $err['result'], $err['value'], false);

	return true;
}

// *************************************************************************
// *                  function convertVideoThumbnails()                    *
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
	$err = create_remove_directory($thumb_output_dir . $app->config['videothumbnailresolutions']['wide'] ."/");
	if ( !$err['code'] ) {
		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "MSG: " . $err['message'] . "\nCOMMAND: " . $err['command'] . "\nRESULT: " . $err['result'], $sendmail = true);
		return false;
	}

	// 4:3 frames
	$err = create_remove_directory($thumb_output_dir . $app->config['videothumbnailresolutions']['4:3'] ."/");
	if ( !$err['code'] ) {
		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "MSG: " . $err['message'] . "\nCOMMAND: " . $err['command'] . "\nRESULT: " . $err['result'], $sendmail = true);
		return false;
	}

	// High resolution wide frame
	$err = create_remove_directory($thumb_output_dir . $app->config['videothumbnailresolutions']['player'] ."/");
	if ( !$err['code'] ) {
		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "MSG: " . $err['message'] . "\nCOMMAND: " . $err['command'] . "\nRESULT: " . $err['result'], $sendmail = true);
		return false;
	}

	// Calculate thumbnail step
	$vthumbs_maxframes = $app->config['thumb_video_numframes'];
	if ( floor($playtime) < $vthumbs_maxframes ) $vthumbs_maxframes = floor($playtime);
	$thumb_steps = floor($playtime) / $vthumbs_maxframes;

	if ( $thumb_steps == 0 ) {
		$thumb_steps = 1;
		$vthumbs_maxframes = floor($playtime);
	}

	$log_msg = "[INFO] Video thumbnail generation summary:\nStep: " . $thumb_steps . " sec\nNumber of thumbs: " . $vthumbs_maxframes . "\nResolutions: " . $app->config['videothumbnailresolutions']['wide'] . ", " . $app->config['videothumbnailresolutions']['4:3'] . ", " . $app->config['videothumbnailresolutions']['player'];
	$time_start = time();

	// Thumbnail generation under ./indexpics by each recording element
	$res_wide   = explode("x", $app->config['videothumbnailresolutions']['wide'  ], 2);
	$res_43     = explode("x", $app->config['videothumbnailresolutions']['4:3'   ], 2);
	$res_high	  = explode("x", $app->config['videothumbnailresolutions']['player'], 2);
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
		$filename_wide    = $thumb_output_dir . $app->config['videothumbnailresolutions']['wide'  ] ."/". $filename_basename;
		$filename_43      = $thumb_output_dir . $app->config['videothumbnailresolutions']['4:3'   ] ."/". $filename_basename;
		$filename_highres = $thumb_output_dir . $app->config['videothumbnailresolutions']['player'] ."/". $filename_basename;

		$command  = $app->config['nice_high'] . " " . $app->config['ffmpegthumbnailer'] . " -i " . $recording['master_filename'] . " -o " . $orig_thumb_filename . " -s0 -q8 -t" . secs2hms($position_sec) . " 2>&1";

		clearstatcache();

		$output = runExt4($command);
		$output_string = $output['cmd_output'];
		$result = $output['code'];
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
						$recording['thumbnail_indexphotofilename'] = "recordings/" . ( $recording['id'] % 1000 ) . "/" . $recording['id'] . "/indexpics/" . $app->config['videothumbnailresolutions']['4:3'] . "/" . $filename_basename;
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
// *                       function convertMedia()                         *
// *************************************************************************
// Description: convert media file based on encoding profile
function convertMedia(&$recording, $profile) {
global $app, $jconf, $global_log;

    $db = db_maintain();

	// STATUS: converting
	updateRecordingVersionStatus($recording['recordingversionid'], $jconf['dbstatus_conv']);
	// Output filename
	if ($recording['islegacy'] == 1)  {
		// $recording['output_basename'] = $recording['id'] . $profile['filenamesuffix'] .".". $profile['filecontainerformat'];
		$recording['output_basename'] = $recording['filename'];
	} else {
		$recording['output_basename'] = $recording['id'] ."_". $recording['recordingversionid'] . $profile['filenamesuffix'] .".". $profile['filecontainerformat'];
	}
	$recording['output_file'] = $recording['temp_directory'] . $recording['output_basename'];

	// Audio only: add placeholder thumbnail with a speaker icon
	if ( $recording['mastermediatype'] == "audio" ) {
		$recording['thumbnail_numberofindexphotos'] = 0;
		$recording['thumbnail_indexphotofilename'] = "images/videothumb_audio_placeholder.png?rid=" . $recording['id'];
	}

	$msg = '';
	$encoding_params_main    = null;
	$encoding_params_overlay = null;
	
	if ($profile['type'] === 'pip') {
		$no_cntnt = array($jconf['dbstatus_deleted'], $jconf['dbstatus_markedfordeletion'], NULL);
		
		if (in_array($recording['contentstatus'], $no_cntnt, 1) === false) {
		// legacy mobile versions can lacking of content component, so we need to check content availabilty every time.
		// if there's no content, skip the overlay parameter preparation step
			$recording['iscontent'] = false;
			$tmp = ffmpegPrep($recording, $profile);
			$encoding_params_overlay = $tmp['params'];
			$msg .= ($tmp['result'] === false) ? ($tmp['message']) : ("");
			
			$recording['iscontent'] = true;
		}
		
		$tmp = ffmpegPrep($recording, $profile);
		$encoding_params_main    = $tmp['params'];
		$msg .= ($tmp['result'] === false) ? ($tmp['message']) : ("");
		
		unset($no_cntnt);
	} else {
		$tmp = ffmpegPrep($recording, $profile);
		$encoding_params_main    = $tmp['params'];
		$encoding_params_overlay = null;
		$msg = $tmp['message'];
	}

	if (($encoding_params_main === null) && ($encoding_params_overlay === null)) {
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_conv_err'], "[ERROR] Encoding parameter preparation failed!\n". $msg ."\n(Recording version: ". $profile['name'] .")", '-', '-', 0, false);
		return false;
	}

	$err = advancedFFmpegConvert($recording, $profile, $encoding_params_main, $encoding_params_overlay);
	$recording['encodingparams'] = $err['encodingparams'];

	// Log input and target file details
	$log_msg = printMediaInfo($recording, $profile);
	$global_log .= $log_msg. "\n";

	if ( !$err['code'] ) {
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_conv_err'], $err['message'] . "\nSource file: " . $recording['master_filename'] . "\nDestination file: " . $recording['output_file'] . "\n\n" . $log_msg, $err['command'], $err['command_output'], $err['duration'], true);
		return false;
	} else {
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_conv'], $err['message'] . "\nConversion done for: " . $recording['output_file'] . "\n\n" . $log_msg, $err['command'], $err['command_output'], $err['duration'], false);
		$global_log .= "Conversion in " . secs2hms($err['duration']) . " time.\n";
	}

	$global_log .= "\n";

	return true;
}

// *************************************************************************
// *                    function copyMediaToFrontEnd()                     *
// *************************************************************************
// Description: Copy (SCP) media file back to front-end server
function copyMediaToFrontEnd($recording, $profile) {
 global $app, $jconf, $debug;

    $db = db_maintain();
 
	// STATUS: copyingtostorage
	updateRecordingVersionStatus($recording['recordingversionid'], $jconf['dbstatus_copystorage']);

	$idx = "";
	if ( $recording['iscontent'] != 0 ) $idx = "content";

	// SSH command templates
	$ssh_command = "ssh -i " . $app->config['ssh_key'] . " " . $app->config['ssh_user'] . "@" . $recording[$idx .'mastersourceip'] . " ";
	$scp_command = "scp -B -r -i " . $app->config['ssh_key'] . " ";
	$recording['output_ssh_filename'] = $app->config['ssh_user'] . "@" . $recording[$idx . 'mastersourceip'] . ":" . $recording['recording_remote_path'];

	// Create remote directories, does nothing if exists
	$command1 = "mkdir -m " . $jconf['directory_access'] . " -p " . $app->config['recordingpath'] . ( $recording['id'] % 1000 ) . "/" . " 2>&1";
	$command2 = "mkdir -m " . $jconf['directory_access'] . " -p " . $recording['recording_remote_path'] . " 2>&1";
	$command  = $ssh_command . "\"" . $command1 . " ; " . $command2 . "\"\n";
	exec($command, $output, $result);
	$output_string = implode("\n", $output);
	if ( $result != 0 ) {
		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "[ERROR] Failed creating remote directory (SSH)\n\nCOMMAND: " . $command . "\nRESULT: " . $output_string, $sendmail = true);
		return false;
	} else {
		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "[INFO] Remote front-end directories created. (SSH)\n\nCOMMAND: " . $command, $sendmail = false);
	}

	//// Remove existing remote files (e.g. reconvert) and index thumbnails if needed
	$command = "rm -f " . $recording['recording_remote_path'] . $recording['output_basename'] . " 2>&1";
	// Remove indexpics if generated
	if ( !empty($recording['thumbnail_numberofindexphotos']) ) {
		$command .= " ; rm -f -r " . $recording['recording_remote_path'] . "indexpics/ 2>&1 ";
	}
	$command  = $ssh_command . "\"" . $command . "\"\n";
	exec($command, $output, $result);
	$output_string = implode("\n", $output);
	if ( $result != 0 ) {
		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "[WARN] Failed removing remote files (SSH)\nCOMMAND: " . $command . "\nRESULT: " . $output_string, $sendmail = true);
	} else {
		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "[INFO] Remote files removed. (SSH)\n\nCOMMAND: " . $command, $sendmail = false);
	}

	// SCP: copy converted file to front end server
	$err = ssh_filecopy2($recording[$idx .'mastersourceip'], $recording['output_file'], $recording['recording_remote_path'], false);
	if ( !$err['code'] ) {
		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "MSG: " . $err['message'] . "\nCOMMAND: " . $err['command'] . "\nRESULT: " . $err['result'], $sendmail = true);
		return false;
	} else {
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_copystorage'], $err['message'], $err['command'], $err['result'], $err['value'], false);
	}

	$chmod_command = "chmod -f " . $jconf['file_access'] . " " . $recording['recording_remote_path'] . $recording['output_basename'];

	// SCP: copy video thumbnails if updated
	if ( !empty($recording['thumbnail_numberofindexphotos']) ) {
		$err = ssh_filecopy2($recording[$idx .'mastersourceip'], $recording['temp_directory'] . "indexpics/", $recording['recording_remote_path'], false);
		if ( !$err['code'] ) $debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "MSG: " . $err['message'] . "\nCOMMAND: " . $err['command'] . "\nRESULT: " . $err['result'], $sendmail = true);
		$chmod_command .= " ; chmod -f " . $jconf['directory_access'] . " " . $recording['recording_remote_path'] . "indexpics/";
	}

	// SSH: chmod new remote files
	$command = $ssh_command . "\"" . $chmod_command . "\"";
	exec($command, $output, $result);
	$output_string = implode("\n", $output);
	if ( $result != 0 ) {
		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "[WARN] Cannot chmod new remote files (SSH)\nCOMMAND: " . $command . "\nRESULT: " . $output_string, $sendmail = true);
	} else {
		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "[INFO] Remote chmod OK. (SSH)\n\nCOMMAND: " . $command, $sendmail = false);
	}

	// Update recording version information (recordings_versions)
	if ( !updateMediaInfo($recording, $profile) ) {
		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "[ERROR] Recording version update failed.", $sendmail = true);
		return false;
	}

	// Recording size: update database value
	$msg = null;
	$master_filesize = 0;
	$recording_filesize = 0;
	
	if ($recording[$idx .'masterstatus'] == $jconf['dbstatus_uploaded']) {
		$err = directory_size($recording['master_path']);
	} else {
		$err = ssh_filesize($recording[$idx .'mastersourceip'], $recording['recording_remote_path'] . "master/");
	}
	
	if ( !$err['code']) {
		$msg .= "[WARN] Master filesize cannot be acquired! Message:\n". $err['command_output'] ."\n";
	} else {
		$master_filesize = $err['value'];
	}
	
	$err = ssh_filesize($recording[$idx .'mastersourceip'], $recording['recording_remote_path']);
	if ( $err['code'] ) {
		$recording_filesize = $err['value'];
	} else {
		$msg .= "[WARN] Recording filesize cannot be acquired! Message:\n". $err['command_output'] ."\n";
	}
	
	// Update DB
	$values = array(
		'masterdatasize'    => $master_filesize,
		'recordingdatasize' => $recording_filesize,
	);
	$recDoc = $app->bootstrap->getModel('recordings');
	$recDoc->select($recording['id']);
	$recDoc->updateRow($values);
	$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", $msg ."[INFO] Recording filesize updated.\n\n" . print_r($values, true), $sendmail = false);
	unset($err, $master_filesize, $msg, $recording_filesize);

	// Remove temporary directory, no failure if not successful
	$err = remove_file_ifexists($recording['temp_directory']);
	if ( !$err['code'] ) $debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "MSG: " . $err['message'] . "\nCOMMAND: " . $err['command'] . "\nRESULT: " . $err['result'], $sendmail = true);

	return true;
}

function checkRecordingVersionToStop($recording) {
global $jconf, $debug, $myjobid;

    $db = db_maintain();

	// Is it recording or content? Get appropriate status.
	$type = "recording";
	if ( $recording['iscontent'] ) $type = "content";
	$r_status = getRecordingStatus($recording['id'], $type);
	// recording_versions.status = "stop" / "markedfordeletion" / "deleted"? OR recordings.status = "markedfordeletion" / "deleted"?
	$rv_status = getRecordingVersionStatus($recording['recordingversionid']);

	if ( ( $rv_status == $jconf['dbstatus_stop'] ) or ( $rv_status == $jconf['dbstatus_markedfordeletion'] ) or ( $rv_status == $jconf['dbstatus_deleted'] ) or ( $r_status == $jconf['dbstatus_markedfordeletion'] ) or ( $r_status == $jconf['dbstatus_deleted'] ) ) {
		// Cleanup temp directory
		$err = remove_file_ifexists($recording['temp_directory']);
		if ( !$err['code'] ) $debug->log($jconf['log_dir'], $myjobid . ".log", "MSG: " . $err['message'] . "\nCOMMAND: " . $err['command'] . "\nRESULT: " . $err['result'], $sendmail = true);
		// If recordings_versions.status = "stop" then recordings_versions.status := "markedfordeletion" (mark this version to be deleted)
		updateRecordingVersionStatus($recording['recordingversionid'], $jconf['dbstatus_markedfordeletion']);
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Conversion STOPPED for recording version id = " . $recording['recordingversionid'] . ", recordingid = " . $recording['id'] . ".", $sendmail = false);
		return true;
	}

	return false;
}

?>