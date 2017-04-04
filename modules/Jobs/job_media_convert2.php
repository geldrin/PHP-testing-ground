<?php
// Media conversion job v0 @ 2012/02/??

define('BASE_PATH',	realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');
include_once( BASE_PATH . 'libraries/Videosquare/Modules/RunExt.php');

// Utils
include_once('job_utils_base.php');
include_once('job_utils_log.php');
include_once('job_utils_status.php');
include_once('job_utils_media2.php');

use Videosquare\Job\RunExt as runExt;

set_time_limit(0);

// Init
$app = new Springboard\Application\Cli(BASE_PATH, PRODUCTION);

// Load jobs configuration file
$app->loadConfig('modules/Jobs/config_jobs.php');
$jconf = $app->config['config_jobs'];
$myjobid = $jconf['jobid_media_convert'];
$myjobpath = $jconf['job_dir'] . $myjobid . ".php";

// Log related init
$thisjobstarted = time();
$debug = Springboard\Debug::getInstance();
$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "*************************** Job: Media conversion started ***************************" ."\n", $sendmail = false);

// Check operating system - exit if Windows
if ( iswindows() ) {
  echo "ERROR: Non-Windows process started on Windows platform\n";
  exit;
}

// Log buffer
$log_buffer = array();

// Start an infinite loop - exit if any STOP file appears
while( !is_file( $app->config['datapath'] . 'jobs/' . $myjobid . '.stop' ) and !is_file( $app->config['datapath'] . 'jobs/all.stop' ) ) {

  clearstatcache();
  
  // Convpath check
  if ( !is_writeable($app->config['convpath']) ) {
    $attachedDoc->debugLog("[ERROR] Converter temp path " . $app->config['convpath'] . " is not writeable.", false);
    $converter_sleep_length = 15 * 60;
    break;
  }
    
  // Check job file modification - if more fresh version is available, then restart
  if ( ( filemtime($myjobpath) > $thisjobstarted ) or ( filemtime(BASE_PATH . "config.php" ) > $thisjobstarted ) or ( filemtime(BASE_PATH . "config_local.php" ) > $thisjobstarted ) ) {
    $debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Seems like an updated version is available of me. Exiting...", $sendmail = false);
    exit;
  }
  
  while ( 1 ) {

    $app->watchdog();
        
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
    
    // Clean up converter temporary storage
    $err = cleanUpConverterTemporaryStorage();
    if ( !$err ) $debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "[WARNING] Converter temporary storage cleanup error.", $sendmail = false);

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
    while (isMasterBeingCopied($recording)) {
      static $i = 1;
      
      $debug->log($jconf['log_dir'], "{$jconf['jobid_media_convert']}.log", "[WARNING] Master media files are being moved, sleeping for {$converter_sleep_length}s x{$i}.", false);
      
      if (++$i > 3) {
        $debug->log($jconf['log_dir'], "{$jconf['jobid_media_convert']}.log", "[INFO] Can't download master file, stopping process.", false);
        $i = 0;
        break 2;
      }
      sleep($app->config['sleep_media']);
    }
    
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
        if ( ( $encoding_profile['generatethumbnails'] == 1 ) and ( $encoding_profile['type'] == "recording" ) and ( $encoding_profile['mediatype'] == "video" ) ) { 
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
    
    $converter_sleep_length = 0;
    break;
  }	// End of while(1)

  $app->watchdog();

  sleep($converter_sleep_length);
}	// End of outer while

$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "Media conversion job interrupted.", $sendmail = false);

exit;

//-----------------------------------------------

/**
 * Queries next job from recordings_versions table
 * 
 * @global type $jconf
 * @global type $debug
 * @global Springboard\Application\Cli $app
 * @return boolean
 */
function getNextConversionJob() {
global $jconf, $debug, $app;

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
    $model = $app->bootstrap->getModel('recordings_versions');
    $rs = $model->safeExecute($query);
  } catch (exception $err) {
		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
    return false;
  }

  if ( $rs->RecordCount() < 1 ) return false;

  $jobs = adoDBResourceSetToArray($rs);  

	return $jobs[0];
}

/**
 * Download media from converter
 * 
 * @global Springboard\Application\Cli $app
 * @global type $jconf
 * @global type $debug
 * @param type $recording
 * @return boolean
 */
function copyMediaToConverter(&$recording) {
global $app, $jconf, $debug;

  // STATUS: copyingfromfrontend
  updateRecordingVersionStatus($recording['recordingversionid'], $jconf['dbstatus_copyfromfe']);

  //// Filenames and paths
  // Base filename
  $idx    = "";
  $suffix = "video";
  $type   = "recording";
  
  if ( $recording['iscontent'] != 0 ) { $suffix = $idx = $type = "content"; }
  if ( $recording[$idx . 'mastermediatype'] == "audio" ) { $suffix = "audio"; }
  
  $base_filename = $recording['id'] . "_" . $suffix . "." . $recording[$idx . 'mastervideoextension'];
  // Upload path (Front-End)
  if ( $recording[$idx . 'masterstatus'] == $jconf['dbstatus_uploaded'] ) {
    $uploadpath = $app->config['uploadpath'] . "recordings/";
  } elseif ( $recording[$idx . 'masterstatus'] == $jconf['dbstatus_copystorage_ok'] ) {
    $uploadpath = $app->config['recordingpath'] . ( $recording['id'] % 1000 ) . "/" . $recording['id'] . "/master/";
  } else {
    $debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "Cannot locate master file. Failed status for master. Info: recID = " . $recording['id'] . " | status = " . $recording[$idx . 'masterstatus'] . " | type = " . $type, $sendmail = true);
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
  updateMasterRecordingStatus($recording['id'], $jconf['dbstatus_copyfromfe'], $type);
  $err = ssh_filecopy2($recording[$idx . 'mastersourceip'], $recording[$idx .'master_remote_filename'], $recording[$idx .'master_filename'], true);
  updateMasterRecordingStatus($recording['id'], $recording["{$idx}masterstatus"], $type);
  
  if ( !$err['code'] ) {
    $debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "MSG: " . $err['message'] . "\nCOMMAND: " . $err['command'] . "\nRESULT: " . $err['result'], $sendmail = true);
    // Set status to "uploaded" to allow other nodes to take over task??? !!!
    return false;
  }
  log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_copyfromfe'], $err['message'], $err['command'], $err['result'], $err['value'], false);

  return true;
}

/**
 * Generate video thumbnails
 * 
 * @global Springboard\Application\Cli $app
 * @global type $jconf
 * @global type $debug
 * @param type $recording
 * @return boolean
 */
function convertVideoThumbnails(&$recording) {
  global $app, $jconf, $debug;

  $iserror      = false;
  $playtime     = 0;
  $frame_number = 0;
  $thumb_output_dir = null;
  $directories      = array();

  $logd = $jconf['log_dir']; // log directory
  $logf = $jconf['jobid_media_convert'] .".log"; // log file

  $errors = array('messages' => null, 'commands' => null, 'data' => null);
  $resolutions_used = array('4:3', 'wide', 'player', 'live');

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

  $thumb_output_dir = $recording['temp_directory'] . "indexpics/";

  $directories[] = $thumb_output_dir;              // thumbnail directories under ./indexpics/
  $directories[] = $thumb_output_dir ."original/"; // original frame size

  foreach ($resolutions_used as $res) {            // configured frame sizes
    $directories[] = "{$thumb_output_dir}{$app->config['videothumbnailresolutions'][$res]}/";
  }

  // create directories
  foreach ($directories as $directory) {
    $err = create_remove_directory($directory);
    if ( !$err['code'] )
      $debug->log($logd, $logf, "MSG: {$err['message']}\nCOMMAND: {$err['command']}\nRESULT: {$err['result']}", true );
  }

  // Calculate thumbnail step
  $vthumbs_maxframes = $app->config['thumb_video_numframes'];
  if ( floor($playtime) < $vthumbs_maxframes ) $vthumbs_maxframes = floor($playtime);
  $thumb_steps = floor($playtime) / $vthumbs_maxframes;

  if ( $thumb_steps == 0 ) {
    $thumb_steps = 1;
    $vthumbs_maxframes = floor($playtime);
  }

  $log_msg  = "[INFO] Video thumbnail generation summary:\nStep: {$thumb_steps} sec\nNumber of thumbs: {$vthumbs_maxframes}\n";
  $log_msg .= "Resolutions: ". implode(', ', $app->config['videothumbnailresolutions']);
  $time_start = time();

  for ( $i = 0; $i < $vthumbs_maxframes; $i++ ) {
    $is_error_now = false;

    clearstatcache();

    $position_sec = floor($i * $thumb_steps);
    $filename_basename = "{$recording['id']}_". sprintf("%d", $frame_number + 1) .".jpg";
    $orig_thumb_filename = $thumb_output_dir . "original/" . $filename_basename;

    $cmd = "{$app->config['nice_high']} {$app->config['ffmpegthumbnailer']
      } -i {$recording['master_filename']} -o {$orig_thumb_filename
      } -s0 -q8 -t". secs2hms($position_sec) ." 2>&1";

		$job_thumbnail = new runExt($cmd);
    $job_thumbnail->run();

    if ( $job_thumbnail->getCode() != 0 || !file_exists($orig_thumb_filename) || filesize($orig_thumb_filename) < 1 ) {
      $msg = "[ERROR] FFmpeg failed to generate thumbnail #{$i}! Error message: ". $job_thumbnail->getMessage();

      if ( strlen($job_thumbnail->getMessage()) > 500 )
        $errors['messages'] .= substr($msg, 0, 250) ." (...) ". substr($msg, -250, 0);
      else
        $errors['messages'] .= $msg;

      unset($msg);
      $errors['commands'] .= "Failed command: ". $job_thumbnail->command ."\n";
      $errors['data'] .= "Ouput: ". $job_thumbnail->getOutput() ."\n";
      $iserror = true;

      continue;
    }

    foreach ($resolutions_used as $r) {
      $resolution = $app->config['videothumbnailresolutions'][$r]; 
      $thmb_res = explode('x', strtolower($resolution), 2);
      $filename   = "{$thumb_output_dir}{$resolution}/{$filename_basename}";

      try {
        if (!copy($orig_thumb_filename, $filename)) {
          $errors['messages'] .= "[ERROR] failed to copy thumbnail to {$filename}!\n";
          $iserror = true;
        }

        \Springboard\Image::resizeAndCropImage($filename, $thmb_res[0], $thmb_res[1]);
      } catch ( Exception $ex ) {
        $errors['messages'] .= "[ERROR] File " . $filename . " resizeAndCropImage() failed to " . $thmb_res[0] . "x" . $thmb_res[1] . ".\n";
        $iserror = $is_error_now = true;
      }
    }

    if ( $is_error_now === false ) {
      $frame_number++;
      $recording['thumbnail_size'] = filesize("{$thumb_output_dir}{$app->config['videothumbnailresolutions']['4:3']}/{$filename_basename}");
      $recording['thumbnail_indexphotofilename'] = "recordings/". ( $recording['id'] % 1000 ) ."/{$recording['id']}/indexpics/{$app->config['videothumbnailresolutions']['4:3']}/{$filename_basename}";
    }
  }

  $recording['thumbnail_numberofindexphotos'] = $frame_number;

  if ( $recording['thumbnail_numberofindexphotos'] == 0 ) $recording['thumbnail_indexphotofilename'] = "images/videothumb_audio_placeholder.png?rid=" . $recording['id'];

  $duration = time() - $time_start;
  $mins_taken = round( $duration / 60, 2);

  // Log gathered messages
  if ( $iserror ) {
    log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_conv_thumbs'], "[WARNINGS] " . $frame_number . " thumbnails generated (in " . $mins_taken . " mins) with the following errors:\n" . $errors['messages'] . "\n\n" . $log_msg, $errors['commands'], $errors['data'], $duration, true);
  } else {
    log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_conv_thumbs'], "[OK] ffmpegthumbnailer conversion OK (in " . $mins_taken . " mins)\n\n" . $log_msg, null, null, $duration, false);
  }

  return true;
}

/**
 * Convert media file based on encoding profile
 * 
 * @global type $jconf
 * @global type $global_log
 * @param type $recording
 * @param type $profile
 * @return boolean
 */
function convertMedia(&$recording, $profile) {
global $jconf, $global_log;

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

/**
 * Copy (SCP) media file back to front-end server
 * 
 * @global Springboard\Application\Cli $app
 * @global type $jconf
 * @global type $debug
 * @param array $recording
 * @param type $profile
 * @return boolean
 */
function copyMediaToFrontEnd($recording, $profile) {
 global $app, $jconf, $debug;
 
  $type = (($recording['iscontent'] != 0) ? 'content' : 'recording');
  $idx = (($type == 'content') ? $type : '');
  // STATUS: copyingtostorage
  updateRecordingVersionStatus($recording['recordingversionid'], $jconf['dbstatus_copystorage']);

  // SSH command templates
  $ssh_command = "ssh -q -i " . $app->config['ssh_key'] . " " . $app->config['ssh_user'] . "@" . $recording[$idx .'mastersourceip'] . " ";
  $scp_command = "scp -q -B -r -i " . $app->config['ssh_key'] . " ";
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

  // Get master storage directory size (does not exist if a recording is first processed)
  $err = ssh_filesize($recording[$idx .'mastersourceip'], $recording['recording_remote_path'] . "master/");
  if ( $err['code'] ) $master_filesize =+ $err['value'];
    
  // Recording directory size
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

/**
 * checkRecordingVersionToStop
 * 
 * @global type $jconf
 * @global type $debug
 * @global type $myjobid
 * @param type $recording
 * @return boolean
 */
function checkRecordingVersionToStop($recording) {
global $jconf, $debug, $myjobid;

  // Is it recording or content? Get appropriate status.
  $type = ($recording['iscontent'] ? "content" : "recording");
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

/**
 * Updates the recording array to the current state, and checks if the status
 * indicates the recording masters are being moved.
 * 
 * @global Springboard\Application\Cli $app
 * @global array $jconf
 * @param array $recording Recording data array.
 * @return bool TRUE if any of the master files are being copied
 */
function isMasterBeingCopied(&$recording) {
  global $app, $jconf;
  
  $beingcopied = false;
  
  $recordingObj = $app->bootstrap->getModel('recordings');
  $recordingObj->select($recording['id']);
  $rec = $recordingObj->getRow(); // refresh recording state
  unset($recordingObj);
  
  $recording = array_merge($recording, array_diff($rec, array('id' => null))); // update recording array
  $beingcopied = ($rec['masterstatus'] == $jconf['dbstatus_copystorage'] || $rec['contentmasterstatus'] == $jconf['dbstatus_copystorage']);
  
  return $beingcopied;
}
