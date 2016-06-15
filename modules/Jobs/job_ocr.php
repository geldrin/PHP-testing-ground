<?php
define('BASE_PATH', realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

define('OCR_OK',              0);
define('OCR_PREP_FAILED',     1);
define('OCR_DOWNLOAD_FAILED', 2);
define('OCR_UPLOAD_FAILED',   4);
define('OCR_CONV_FAILED',     8);

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');

// Utils
include_once('job_utils_base.php');
include_once('job_utils_media2.php');
include_once('job_utils_log.php');
include_once('job_utils_status.php');

// Init
set_time_limit(0);
$app = new Springboard\Application\Cli(BASE_PATH, PRODUCTION);
if ($app->config['node_role'] !== 'converter') die;

// Load jobs configuration file
$app->loadConfig('modules/Jobs/config_jobs.php');
$jconf = $app->config['config_jobs'];
$myjobid = $jconf['jobid_ocr_convert'];

// Log related init
$debug = Springboard\Debug::getInstance();
$logdir = $jconf['log_dir'];
$logfile = $myjobid .'.log';

// Init database connection
$db = db_maintain();
// $db = $app->bootstrap->getAdoDB();

// OCR nice level
$onice = $app->config['nice'];
$nicelevel = $tmp = null;
$tmp = preg_match('/[-]?[\d]+$/', $app->config['encoding_nice'], $nicelevel);
if ($tmp === 1) {
  if ((intval($nicelevel[0]) + 5) > 19)
    $onice = "nice -n 19";
  else
    $onice = "nice -n ". (intval($nicelevel[0]) + 5);
} else {
  // jconf parameter is invalid, or given param cannot be found => set process priority to the lowest value
  $onice = "nice -n 19";
}
unset($tmp, $nicelevel);

/////////////////////////////////////////// LAUNCH AREA ///////////////////////////////////////////

$debug->log($logdir, $logfile, str_pad("[ OCR Job started ]", 100, '=', STR_PAD_BOTH), false);
Main(); // Run main loop
$debug->log($logdir, $logfile, str_pad("[ OCR Job stopped ]", 100, '=', STR_PAD_BOTH), false);
die;

///////////////////////////////////////////////////////////////////////////////////////////////////
class OCRException extends Exception {
///////////////////////////////////////////////////////////////////////////////////////////////////
//
// Egyedi kivetel osztaly az OCR folyamatbol valo kiugrashoz es hibaelemzeshez.
//
///////////////////////////////////////////////////////////////////////////////////////////////////
  protected $message;
  private $data;
  private $command;
  
  public function __construct($message = null, $data = null, $command = null, $code = 0, Exception $previous = null) {
    parent::__construct($message, $code, $previous);
    $this->command = $command;
    $this->data = $data;
    if ($message === null)
      $this->message = 'Unkown OCR exception.';
    else
      $this->message = $message;
  }
  
  final public function getData() {
    return $this->data;
  }
  
  final public function getCommand() {
    return $this->command;
  }
}

///////////////////////////////////////////////////////////////////////////////////////////////////
function Main() {
///////////////////////////////////////////////////////////////////////////////////////////////////
//
// Dscrptn...
//
///////////////////////////////////////////////////////////////////////////////////////////////////
  global $db, $app, $jconf, $debug, $logdir, $logfile, $myjobid;
  
  while (!is_file( $app->config['datapath'] .'jobs/'. $myjobid .'.stop') && !is_file($app->config['datapath'] .'jobs/all.stop')) {
    $db = db_maintain();
    $app->watchdog();
    
    $action         = null;
    $OCRresult      = null;
    $OCRduration    = 0;
    $sleep_duration = $app->config['sleep_long'];
    
    try {
      $action = 'PREPARING';
      $tsk = getOCRtasks();

      if( $tsk['result'] === true && !empty($tsk['output']) ) {
        $recording = $tsk['output'][0];
        $msg  = "[INFO] Recording #". $recording['id'];
        $msg .= " (". $recording['title'] . (!empty($recording['subtitle']) ? " / ". $recording['subtitle'] : null) .")";
        $msg .= " has been selected for OCR process.";
        $debug->log($logdir, $logfile, $msg, false);
        echo $msg ."\n";
      } else {
        if ($tsk['output'] === null) {
          echo $tsk['message'] . PHP_EOL;
          // no OCR task found, go to sleep
          throw new OCRException('Nothing to do...', null, null, -1);
        } else {
          // some error happened (whine about it then go to sleep)
          $sleep_duration *= 10;
          throw new OCRException($tsk['message'], var_export($tsk['output'], 1), null, -1);
        }
      }
      
      if ( in_array($recording['ocrstatus'], array($jconf['dbstatus_reconvert'], $jconf['dbstatus_convert'], $jconf['dbstatus_conv'] ))) {
        $debug->log($logdir, $logfile, "[INFO] Marking previous ocr_frames elements for delete.\n", false);
        $err = updateOCRStatus($recording['id'], null, $jconf['dbstatus_markedfordeletion']);
        if ($err['result'] === false) {
          throw new OCRException($err['message'], $err['output'], $err['command'], OCR_PREP_FAILED);
        }
      }
      
      // PATH ASSEMBLY ////////////////////////////////////
      
      $server = $recording['contentmastersourceip'];
      $basename = $recording['id'] ."_content.". $recording['contentmastervideoextension'];
      $rec_base_dir_remote = $app->config['recordingpath'] . ( $recording['id'] % 1000 ) .'/'. $recording['id'] .'/';
      $ocr_dst_dir = $rec_base_dir_remote ."ocr/";
      
      $convpath = $app->config['convpath'] ."master/". $recording['id'] . DIRECTORY_SEPARATOR;
      $masterpath = "";
      if ($recording['contentmasterstatus'] == $jconf['dbstatus_uploaded']) {
        $masterpath = $app->config['uploadpath'] ."recordings/";
      } elseif($recording['contentmasterstatus'] == $jconf['dbstatus_copystorage_ok']) {
        $masterpath = $rec_base_dir_remote ."master/";
      } else {
        // invalid contentmasterstatus?
        throw new OCRException($msg, null, null, OCR_PREP_FAILED);
      }
      
      $remote = $masterpath . $basename;
      $local = $convpath . $basename;
      $recording['contentmasterfile'] = $local;
      
      // MUNKAKONYVTAR ELOKESZITESE ///////////////////////
      
      if (!is_dir($convpath)) {
        restore_error_handler();
        $msg = "[INFO] Preparing working directory.\n";
        
        $createdir = mkdir($convpath, $mode = 0775, $recursive = true);
        $debug->log($logdir, $logfile, $msg, false);
        
        if (!$createdir) {
          $tmp = error_get_last();
          $msg .= "[WARNING] Failed to create directory - ". $tmp['message'] ."\n";
          throw new OCRException($msg, null, null, OCR_PREP_FAILED);
        }
      }
      
      // FAJLOK OSSZEHASONLITASA //////////////////////////
      
      $msg = "[INFO] Comparing remote files.";
      $debug->log($logdir, $logfile, $msg, false);
      echo $msg;
      
      $ssh_cmp_result = ssh_file_cmp_isupdated($server, $remote, $local);
      
      $msg = "CMP RESULT:\n". $ssh_cmp_result['message'];
      $debug->log($logdir, $logfile, $ssh_cmp_result['message'], false);
      echo $msg;
      
      if ($ssh_cmp_result['code'] === false) {
        $sleep_duration *= 10;
        throw new OCRException($ssh_cmp_result['message'], $ssh_cmp_result['command_output'], null, OCR_DOWNLOAD_FAILED);
      }

      // FAJL LETOLTESE ///////////////////////////////////
      
      $action = 'DOWNLOADING';
      
      if ($ssh_cmp_result['value'] === false) {
        $msg = " > Downloading file.";
        $debug->log($logdir, $logfile, $msg, false);
        echo $msg;
        
        // Remote file has been updated, refresh needed
        updateRecordingStatus($recording['id'], $jconf['dbstatus_copyfromfe'], 'ocr');
        $ssh_copy_result = ssh_filecopy2($server, $remote, $local);
        $msg = "SSH COPY RESULT:\n". $ssh_copy_result['message'] ."\n";
        echo $msg;
        $debug->log($logdir, $logfile, $msg, false);
        
        if ($ssh_copy_result['code'] === false) {
          $msg = $ssh_copy_result['message'];
          $sleep_duration *= 10;
          throw new OCRException($msg, $ssh_copy_result['result'], $ssh_copy_result['command'], OCR_DOWNLOAD_FAILED);
        }
        $debug->log($logdir, $logfile, $ssh_copy_result['message'], false);
        
      } else {
        // Remote file is up-2-date, carry on
      }
      
      // OCR FUGGVENY FUTTATASA ///////////////////////////
      
      $action = 'CONVERTING';
      echo "Starting OCR conversion.\n";
      $debug->log($logdir, $logfile, "Starting OCR conversion.\n", false);
      updateRecordingStatus($recording['id'], $jconf['dbstatus_conv'], 'ocr');
      
      $mt_start = microtime($get_as_float = true);
      $OCRresult = convertOCR($recording);
      $OCRduration = round(microtime($get_as_float = true) - $mt_start, 3);
      
      unset($mt_start);
      
      if ($OCRresult['result'] === false) {
        $msg = "[ERROR] OCR processing failed at the following phase: '". $OCRresult['phase'] ."'\n\nMessage:\n". $OCRresult['message'] ."\n";
        echo $msg;
        
        $sleep_duration *= 10;
        throw new OCRException($msg, null, null, OCR_CONV_FAILED);
      } else {
        $msg = $OCRresult['message'];
        $debug->log($logdir, $logfile, $msg, false);
        echo $msg;
        
        if ($OCRresult['output'] === null) {
          // if there was no result, skip uploading and finish process.
          throw new OCRException($msg, null, null, OCR_OK);
        }
        
        updateRecordingStatus($recording['id'], $jconf['dbstatus_copystorage'], $type = "ocr");
        updateOCRStatus($recording['id'], null, $jconf['dbstatus_copystorage']);
      }
      
      // FAJLOK FELTOLTESE //////////////////////////////////
      $action = 'UPLOADING';
      $ssh_template  = "ssh -i " . $app->config['ssh_key'] . " " . $app->config['ssh_user'] . "@" . $recording['contentmastersourceip'] . " ";
      
      // Tavoli konyvtar elokeszitese
      $cmd_check_dst = $ssh_template ."find ". $ocr_dst_dir ." -maxdepth 0 -not -empty";
      $msg = "[INFO] Moving files.\n > Checking remote directory '". $ocr_dst_dir ."'\n COMMAND: '". $cmd_check_dst ."'";
      $debug->log($logdir, $logfile, $msg, false);
      echo $msg;
      $check_dst = new runExt($cmd_check_dst);
			$check_dst->run();
			$output = $check_dst->getOutput();
      
      if (!empty($output)) {
      // not empty or error
        if ($check_dst->getCode() !== 0) {
          // directory does not exist / inaccessible
          $cmd_create_dir =  $ssh_template . "mkdir -m ". $jconf['directory_access'] ." -p ". $ocr_dst_dir;
          $msg = "Creating remote directory '". $ocr_dst_dir ."'\n COMMAND: '". $cmd_create_dir ."'\n";
          $debug->log($logdir, $logfile, $msg, false);
          echo $msg;
          
          $create_dir = new runExt($cmd_create_dir);
          
          if (!$create_dir->run()) {
            $msg = "[ERROR] Remote directory cannot be created!\nCOMMAND OUTPUT:\n". $create_dir->getOutput();
            echo $msg;
            
            throw new OCRException($msg, $create_dir->getOutput(), $cmd_create_dir, OCR_UPLOAD_FAILED);
          }
          
          echo " > OK.\n";
          
        } elseif ($check_dst->getCode() === 0 && $recording['ocrstatus'] == $jconf['dbstatus_reconvert']) {
          // directory is not empty
          $cmd_rmdir = $ssh_template . "rm -R ". $ocr_dst_dir ."*";
          
          $msg = "Cleaning remote directory: '". $ocr_dst_dir ."'\n COMMAND: '". $cmd_rmdir ."'\n";
          $debug->log($logdir, $logfile, $msg, false);
          echo $msg;
          
          $rmdir = new runExt($cmd_rmdir);
          
          if (!$rmdir->run()) {
            $msg = "[ERROR] Failed to delete the contents of remote directory!\nCOMMAND OUTPUT:\n". $rmdir->getOutput() ."\n";
            echo $msg;
            
            throw new OCRException($msg, $rmdir->getOutput(), $cmd_rmdir, OCR_UPLOAD_FAILED);
          } else {
            $msg = "Result:\n". $rmdir->getOutput() ."\n";
            $debug->log($logdir, $logfile, $msg, false);
            echo $msg;
          }
        }
      }
      // we have the directory nice and clean
      unset($msg, $output);
      
      // Konyvtarak feltoltese
      $snapdir = $jconf['ocr_dir'] . $recording['id'] ."/ocr/";
      $msg = "Uploading OCR frames from '". $snapdir ."' to '". $rec_base_dir_remote ."'\n";
      $dat = null;
      $cmd = null;
      
      try {
        $copy_err = $err_fsize = null;
        $copy_err = ssh_filecopy2($server, $snapdir, $rec_base_dir_remote, $upload = false);
        
        if (!$copy_err['code']) {
          $msg .= "[ERROR] Copying OCR snapshots to frontend has been failed!\nMESSAGE:\n". $copy_err['message'] ."\nRESULT:". $copy_err['result'];
          $dat  = $copy_err['result'];
          $cmd  = $copy_err['command'];
          
          $sleep_duration *= 10;
          throw new Exception($msg);
        }
        $msg .= "OCR copying to frontend has been completed.\nMESSAGE: ". $copy_err['message'];
        $debug->log($logdir, $logfile, $msg, false);
        
        updateOCRstatus($recording['id'], null, $jconf['dbstatus_copystorage_ok']);
        
        $err_fsize = ssh_filesize($server, $rec_base_dir_remote);
        
        if (!$err_fsize['code']) {
          $msg = "[WARN] ssh_filesize() failed. Message: ". $err_fsize['message'];
          $debug->log($logdir, $logfile, $msg, false);
        }
        
        $update = array('recordingdatasize' => intval($err_fsize['value']));
        $recDoc = $app->bootstrap->getModel('recordings');
        $recDoc->select($recording['id']);
        $recDoc->updateRow($update);
        $recDoc->updateFulltextCache();
        
        $msg = "[INFO] recording_datasize updated. Values: ". print_r($update, 1);
        $debug->log($logdir, $logfile, $msg, false);
          
        unset($msg, $recDoc, $update);
        
      } catch(Exception $e) {
        throw new OCRException($e->getMessage(), $dat, $cmd, OCR_UPLOAD_FAILED);
      }
      
      $report  = "OCR PROCESS WAS SUCCESSFUL!\n";
      $report .= " > Total frames: ". $OCRresult['total'] ."\n";
      $report .= " > Processed frames: ". $OCRresult['processed'] ."\n";
      $report .= " > Warnings: ". $OCRresult['warnings'] ."\n";
      $report .= "Duration: ". intval($OCRduration) ." s\n";
      echo $report . PHP_EOL;
      updateRecordingStatus($recording['id'], $jconf['dbstatus_copystorage_ok'], $type = 'ocr');
      log_recording_conversion($recording['id'], $myjobid, 'COMPLETE', $jconf['dbstatus_copystorage_ok'], null, $report, $OCRduration, true);
      
      $debug->log($logdir, $logfile, str_pad("[ CONVERSION END ]", 100, '-', STR_PAD_BOTH), false);
      
    } catch (OCRException $ox) {
      // Kapjuk el a kulonbozo hibakodokat, es a returncode-ok alapjan allitsuk be a 'recordings.ocrstatus' mezot.
      if ($ox->getCode() === -1) {
        // nincs elvegzendo feladat
        if (is_resource($db->_connectionID)) $db->close();
      } else {
        echo $ox->getMessage() ."\n";
        $status = null;
        $report = $action ." FAILED.\n";
        
        switch ($ox->getCode()) {
          case OCR_PREP_FAILED:
          case OCR_DOWNLOAD_FAILED:
            $status = $jconf['dbstatus_copyfromfe_err'];
            break;
          case OCR_CONV_FAILED:
            $status = $jconf['dbstatus_conv_err'];
            break;
          case OCR_UPLOAD_FAILED:
            $status = $jconf['dbstatus_copystorage_err'];
            break;
          case OCR_OK:
          default:
            $status = 'NULL';
            $report = "OCR PROCESS COMPLETE.\n";
            break;
        }
        
        if (isset($OCRresult)) $report .= " > Phase:". $OCRresult['phase'] ."\n";
        if ($ox->getCommand()) $report .= " > Command: ". $ox->getCommand() ."\n";
        if ($ox->getMessage()) $report .= " > Message: ". $ox->getMessage() ."\n";
        if ($ox->getData())    $report .= " > Info: ". $ox->getData() ."\n";
        
        updateRecordingStatus($recording['id'], $status, $type = 'ocr');
        if ($app->config['jobs'][$app->config['node_role']][$myjobid]['supresswarnings'] == false)
          log_recording_conversion($recording['id'], $myjobid, $action, ($status === null ? 'NULL' : $status), $ox->getCommand(), $report, $OCRduration, true);
        $debug->log($logdir, $logfile, str_pad("[ CONVERSION END ]", 100, '-', STR_PAD_BOTH), false);
      }
    }
    unset($msg, $OCRresult, $tsk, $recording, $status); // cleanup junk
    echo "Sleeping.\n";
    
    $app->watchdog();
    sleep($sleep_duration);
  } // Main cycle

  if (is_resource($db->_connectionID)) $db->close();
}

///////////////////////////////////////////////////////////////////////////////////////////////////
function convertOCR($rec) {
///////////////////////////////////////////////////////////////////////////////////////////////////
//
// Description
// goes
// here.
//
///////////////////////////////////////////////////////////////////////////////////////////////////
  global $app, $debug, $jconf, $logdir, $logfile, $onice;
  
  $result = array(
    'result'         => false,                                                     // success
    'output'         => null,                                                      // array of processed frames
    'message'        => null,                                                      // message
    'phase'          => 'init',                                                    // current state of processing
    'total'          => 0,                                                         // total number of frames
    'processed'      => 0,                                                         // total number of successfully processed frames
    'warnings'       => 0,                                                         // total number of warnings during processing
  );
  $numocrwarns = 0;
  /////////////////////////////////////////////////////////
  $maindir = $jconf['ocr_dir'] . $rec['id'] . DIRECTORY_SEPARATOR;
  $wdir    = $jconf['ocr_dir'] . $rec['id'] ."/wdir". DIRECTORY_SEPARATOR;         // source directory
  $cmpdir  = $jconf['ocr_dir'] . $rec['id'] ."/cmp". DIRECTORY_SEPARATOR;          // folder for comparing downsized frames
  $tempdir = $jconf['ocr_dir'] . $rec['id'] ."/temp". DIRECTORY_SEPARATOR;         // folder for prepared frames
  $snapdir = $jconf['ocr_dir'] . $rec['id'] ."/ocr". DIRECTORY_SEPARATOR;          // output directory
  
  $resolutions2used = array_intersect_key(
    $app->config['videothumbnailresolutions'],
    array_flip(array('4:3', 'wide', 'player'))
  ); // pick resolutions
  
  $snapshotparams = array('resize' => array(), 'folders' => array());              // resize values and destination folders for ocr-snashots
  foreach ($resolutions2used as $tres) { // foldernames are derived from thumbnailresolutions + original
    $tmp = explode("x", $tres);
    $snapshotparams['resize' ][] = $tres;
    $snapshotparams['folders'][] = $snapdir . $tmp[0];
  }
  $snapshotparams['folders']['original'] = $snapdir .'original'. DIRECTORY_SEPARATOR;
  
  $frames = array(
    'frames'      => array(), // arrays of frame names, ocrtext, and dbid
    'transitions' => array(), // pointers to 'frames' which are on transition points
    'sorted'      => array(), // pointers to 'frames' to be passed to OCR engine
    'processed'   => array(), // pointers to 'frames' which could be updated to database
  );

	$worker = new runExt();
	
  $errstr = "Function convertOCR(rec#". $rec['id'] .") failed!"; //// Megtarthato?????
  
  $app->watchdog();
  $debug->log($logdir, $logfile, str_pad("[ Starting ocr process on recording #". $rec['id'] ." ]", 100, '-', STR_PAD_BOTH), false);

  // IDEIGLENES KONYVTARAK ELOKESZITESE ///////////////////
  $result['phase'] = "Preparing temporary directories";
  $prepOCRdirs = array();
  $folders2create = array_merge(array($maindir, $wdir, $cmpdir, $tempdir, $snapdir), $snapshotparams['folders']);
  foreach($folders2create as $d) {
    $prepOCRdirs = create_remove_directory($d);
    if ($prepOCRdirs['code'] === false) {
      $result['message'] = $prepOCRdirs['message'] ." (". $d .")";
      $debug->log($logdir, $logfile, $result['message'], $sendmail = false);
      return $result;
    }
  }
  $debug->log($logdir, $logfile, "[INFO] Temporary directories have been created.", $sendmail = false);
  unset($prepOCRdirs);

  // 3RD PARTY UTILITY-K ELLENORZESE //////////////////////
  $result['phase'] = "Checking 3rd party utilites";
  $cmd_test_imagick = "convert -version";
  $cmd_test_ocr = "type \"". $app->config['ocr_alt'] ."\"";
  $imtest  = new runExt($cmd_test_imagick);
  $ocrtest = new runExt($cmd_test_ocr);
  if (!$imtest->run()) {
    $result['message'] = $errstr ." Imagick utility missing!";
    $debug->log($logdir, $logfile, $result['message'], $sendmail = false);
    return $result;
  } elseif (!$ocrtest->run()) {
    $result['message'] = $errstr ." OCR engine cannot be found!";
    $debug->log($logdir, $logfile, $result['message'], $sendmail = false);
    return $result;
  }
  
  // NYELVI KOD ELLENORZESE ///////////////////////////////
  $result['phase'] = "Checking language code";
  $langcode = getLangCode($rec['id'], $app->config['ocr_engine']);
  // VAGY INKABB ADJUK MEG A JCONF-BAN, HOGY MIKET TUD HASZNALNI A BEKONFIGOLT OCR ENGINE????
  if ($langcode === false) {
    $msg = "[ERROR] Querying langcode(". $rec['languageid'] .") failed!";
    $debug->log($logdir, $logfile, $msg, false);
    // failing mysql queries can point out more serious problems, so return with error
    $result['message'] = "[ERROR] Querying langcode(". $rec['languageid'] .") failed!";
    return $result;
  } elseif ($langcode === null) {
    $msg = "[WARN] Language code is not available or not supported by OCR engine. Using default charset ('hun').";
    $debug->log($logdir, $logfile, $msg, false);
    $numocrwarns++;
    $langcode = 'hun';
  }
  $msg = "[INFO] Selected language code is: '". $langcode ."'.";
  $debug->log($logdir, $logfile, $msg, false);
  unset($msg);
  
  // KEPKOCKAK KINYERESE //////////////////////////////////
  $result['phase'] = "Extracting frames from video";
  
  $loglevel = $app->config['ffmpeg_loglevel'];
  if ((is_integer($loglevel + 0) && $loglevel < 32) || in_array($loglevel, array('warning', 'error', 'fatal', 'panic', 'quiet'))) {
    $loglevel = 32;
  }
  $cmd_explode = escapeshellcmd($onice ." ". $app->config['ffmpeg_alt'] ." -v ". $loglevel ." -i ". $rec['contentmasterfile'] ." -filter_complex 'scale=w=320:h=180:force_original_aspect_ratio=decrease' -r ". $app->config['ocr_frame_distance'] ." -q:v 1 -f image2 ". $cmpdir ."%06d.png -r ". $app->config['ocr_frame_distance'] ." -q:v 1 -f image2 ". $wdir ."%06d.jpg");
  
  $debug->log($logdir, $logfile, "Extracting frames from video. Command line:". PHP_EOL . $cmd_explode);
  
	$worker->run($cmd_explode);
  $app->watchdog();
  
  // $err['code'] = 0; //// DEBUG
  if ($worker->getCode() !== 0) {
    $msg = "[ERROR] Can't extract frames from video! Message:\n". $worker->getOutput() ."\nCommand:\n". $cmd_explode ."\nReturn code: ". $worker->getCode();
    $debug->log($logdir, $logfile, $msg, $sendmail = false);
    $result['message'] = $msg;
    return $result;
  } else {
    // KEPKOCKAK BETOLTESE TOMBBE /////////////////////////
    $files = glob($wdir .'*.jpg');
    if (empty($files)) {
      $msg = "[ERROR] Can't extract frames from video! No frames found.\nCommand:\n". $cmd_explode;
      $debug->log($logdir, $logfile, $msg, $sendmail = false);
      $result['message'] = $msg;
      return $result;
    } else {
      foreach($files as $file) {
        $frames['frames'][] = array(
          'file' => pathinfo($file, PATHINFO_BASENAME),    // Basename of extracted frames
          'flnm' => pathinfo($file, PATHINFO_FILENAME),    // Filename without extension
          'text' => null,                                  // UTF-8 text extracted from image
          'dbid' => null,                                  // OCR-hit's database id (required for thumbnail filenames!)
        );
      }
    }
    unset($files);
    $result['total'] = count($frames['frames']);
    $debug->log($logdir, $logfile, "Frame extraction finished. Total number of frames: ". count($frames['frames']), false);
  }

  // REDUNDANS FRAME-EK KISZURESE /////////////////////////
  $result['phase'] = "Discarding redundant frames";
  $debug->log($logdir, $logfile, "Discarding redundant frames...", false);
  $p1 = 0;
  $p2 = 0;
  $cntr = 0;
  $timedif = 0;
  
  for($i = 0; $i <= (count($frames['frames']) - 2); $i++) {
    $p1 = $i;
    $p2 = $i + 1;
    $mean = 0;
    
    $img1 = $cmpdir . $frames['frames'][$p1]['flnm'] .'.png';
    $img2 = $cmpdir . $frames['frames'][$p2]['flnm'] .'.png';

    $cmdIMdiff = $onice ." convert \"". $img1 ."\" \"". $img2 ."\" -compose difference -colorspace gray -composite png:- | identify -verbose -format %[fx:mean] png:-";
		$worker->run($cmdIMdiff, 20);

    if ($worker->getCode() !== 0) {
      $numocrwarns++;
      continue;
    } else {
      $mean = floatval($worker->getOutput());
      if ($mean > $app->config['ocr_threshold']) {    // kulonbozik
        $frames['transitions'][] = $p2;
      }
    }
  }
  
  // MOZGOKEP KISZURESE ///////////////////////////////////
  $result['phase'] = "Discarding motion scenes";
  $debug->log($logdir, $logfile, "Detecting motion scenes.", false);
  $motion = array();
  
  for($i = 0; $i < (count($frames['transitions']) - 1); $i++) {
    // calculate distance between frames
    $timedif = $frames['transitions'][$i + 1] - $frames['transitions'][$i];
    if ($timedif <= 1 ) {
      // if two frames are next to each other, then increment counter (possible motion section)
      $cntr++;
    } elseif($timedif > 1 && $cntr >= 4) {
      // if counter is >= 4, and difference is > 1, then we probably found the end of the motion.
      $motion[] = array(
        'start' => ($frames['transitions'][$i - $cntr]),
        'stop'  => ($frames['transitions'][$i]),
      );
      $debug->log($logdir, $logfile, "Motion picture section detected between ". ($frames['transitions'][$i - $cntr]) ." - ". $frames['transitions'][$i], false);
      $cntr = 0;
    } else {
      // set counter back to default if counter < 4 and difference > 1.
      $cntr = 0;
    }
  }
  
  $debug->log($logdir, $logfile, " > ". count($motion) ." motion sections have been detected.");


  // HASZNOS FRAME-EK KIGYUJTESE //////////////////////////
  $result['phase'] = "Collecting useful frames";
  $frames2remove = array();
  if (!empty($motion)) {
    $debug->log($logdir, $logfile, "Removing motion scenes...", false);

    foreach($motion as $movie_scene) {
      $frames2remove += array_intersect($frames['transitions'], range(($movie_scene['start'] + 1), $movie_scene['stop']));

      // get frames from the middle of a scene (igoring poor quality frames right after keyframes/scene cuts)
      $frames['sorted'][] = ( int ) floor(abs($movie_scene['start'] - $movie_scene['stop']) / 2) + $movie_scene['start'];
    }
  }
  
  $frames['sorted'] = array_diff($frames['transitions'], $frames2remove);
  $frames['sorted'] = array_values($frames['sorted']);
  unset($motion, $frames2remove);
  $app->watchdog();
  
  if (empty($frames['sorted'])) {
    $result['result'] = true;
    $result['message'] = "[INFO] No frames to be processed, ending OCR process.";
    $result['warnings'] = $numocrwarns;
    return $result;
  }

  // SZOVEG KINYERESE FRAMEKBOL ///////////////////////////
  $result['phase'] = "Extracting text";
  $debug->log($logdir, $logfile, "Preparing frames for OCR.", false);
  
  $log_ocr_progress = '';
  $ocr_proc_errors = 0;
  
  foreach ($frames['sorted'] as $ptr) {
    $image = $wdir . $frames['frames'][$ptr]['file'];
    // FRAME ELOKESZITESE OCR-HEZ /////////////////////////
    $prepare = prepareImage4OCR($image, $tempdir);
    if ($prepare['result'] === false) {
      $log_ocr_progress .= "[WARN] ". $prepare['message'] ." at frame#". ($ptr + 1) . PHP_EOL;
      $numocrwarns++;
      $ocr_proc_errors++;
      continue;
    }

    // OCR-ENGINE FUTTATASA ///////////////////////////////
    $ocr = getOCRtext($prepare['output'], $tempdir, $langcode, "ocrdata_$ptr.txt");
    if ($ocr['result'] === false) {
      $log_ocr_progress .= "[WARN] ". $ocr['message'] ." at frame#". ($ptr + 1) . PHP_EOL;
      $numocrwarns++;
      $ocr_proc_errors++;
      continue;
    }
    
    // SZOVEGES ADATOK TISZTITASA /////////////////////////
    $text = trim($ocr['output']);
    $text = sanitizeOCRtext($text);
    $text = addslashes($text);
    
    if (!empty($text)) {
      $frames['frames'][$ptr]['text'] = $text;
      $frames['processed'][] = $ptr;
    }
  }
  
  $app->watchdog();
  $log_ocr_progress .= count($frames['processed']) . " frames has been processed out of ". count($frames['sorted']) .".\nTotal number of warnings: " . $ocr_proc_errors .".";
  $debug->log($logdir, $logfile, $log_ocr_progress, false);
  
  if (empty($frames['processed'])) {
    $result['result'] = true;
    $result['message'] = "[INFO] No frames to be updated to database, ending OCR process.";
    
    if ($ocr_proc_errors >= count($frames['sorted'])) {
      $result['result']  = false; // if all frames failed, raise error
      $result['message'] = "[ERROR] Failed to extract data from any frames!";
    }
    unset($log_ocr_progress, $ocr_proc_errors);
    
    $result['warnings'] = $numocrwarns;
    return $result;
  }
  unset($log_ocr_progress, $ocr_proc_errors);
  
  // SZOVEG VISSZATOLTESE AZ ADATBAZISBA //////////////////
  $result['phase'] = "Updating database";
  $debug->log($logdir, $logfile, "Updating database.", false);
  foreach ($frames['processed'] as $f) {
    $updateocr = insertOCRdata($rec['id'], $f + 1, $jconf['dbstatus_conv'], $frames['frames'][$f]['text'], $app->config['ocr_frame_distance']);
    if ($updateocr['result'] === false) {
      $msg = "[ERROR] " . $updateocr['message'];
      $debug->log($logdir, $logfile, $msg, $sendmail = false);
      $result['message'] = $msg;
      return $result;
    }
    $frames['frames'][$f]['dbid'] = $updateocr['output'];
  }
  
  // THUMBNAIL-EK GENERALASA //////////////////////////////
  $result['phase'] = "Generating snapshots";
  $debug->log($logdir, $logfile, "Generating snapshots.", false);

  if (!file_exists($snapdir)) {
    $result['message'] = "[ERROR] Thumbnails cannot be generated, missing directory! (". $snapdir .")";
  } else {
    foreach($snapshotparams['folders'] as $s) { 
      if (file_exists($s) === false) {
        $result['message'] = "[ERROR] Thumbnails cannot be generated, missing directory! (". $s .")";
        break;
      }
    }
  }
  $app->watchdog();
  
  if ($result['message']) {
    $debug->log($logdir, $logfile, $result['message'], false);
    return $result;
  }
  
  $snapshots = createOCRsnapshots($rec['id'], $frames, $snapshotparams, $wdir, $snapdir);
  if ($snapshots['result'] === false) {
    $msg = "[ERROR] ". $snapshots['message'];
    $result['message'] = $msg;
    $debug->log($logdir, $logfile, $msg, $sendmail = false);
    return $result;
  }
  
  foreach($frames['processed'] as $f) {
    $src = $wdir . $frames['frames'][$f]['file'];
    $dst = $snapshotparams['folders']['original'] . DIRECTORY_SEPARATOR . $rec['id'] ."_". $frames['frames'][$f]['dbid'] .".jpg";
    if (!copy($src, $dst)) {
      $msg = "[ERROR] Failed to copy \"". $src ."\" to \"". $dst ."\"!";
      $result['message'] = $msg;
      return $result;
    }
  }

  $result['result']    = true;
  $result['output']    = $frames['frames'];
  $result['message']   = "[OK] OCR process finished successfully!";
  $result['phase']     = "Complete";
  $result['warnings']  = $numocrwarns;
  $result['processed'] = count($frames['processed']);
  $debug->log($logdir, $logfile, $result['message'], $sendmail = false);
  
  return $result;
}

///////////////////////////////////////////////////////////////////////////////////////////////////
function prepareImage4OCR($image, $destpath) {
///////////////////////////////////////////////////////////////////////////////////////////////////
//
// Description goes here...
//
// image: image path
// destpath: directory 
//
// + Need some better image preparation method.
// 
///////////////////////////////////////////////////////////////////////////////////////////////////
global $onice;

  $return_array = array(
    'code'    => 0,
    'result'  => false,
    'message' => null,
    'output'  => null, // full path to output image
  );
  $strmethoderr = "Function ". __FUNCTION__ ." failed!";
  
  $imagepath = realpath($image);

  if ($imagepath === false || !file_exists($imagepath) || filesize($imagepath) == 0) {
    $return_array['code'] = 1;
    $return_array['message'] = $strmethoderr ." Image file ('". $imagepath ."') is not accessible!";
    return $return_array;
  }

  if ($destpath === false || !file_exists($destpath) || !is_dir($destpath)) {
    $return_array['code'] = 2;
    $return_array['message'] = $strmethoderr ." Destination path ('". $destpath ."') does not exists!";
    return $return_array;
  }
  $out_img = $destpath . pathinfo($image, PATHINFO_FILENAME) .".png";
  
  $cmd_identify = $onice ." identify -verbose -format %[fx:mean] ". $imagepath;
	$identify = new runExt($cmd_identify, 10.0);
  $identify->run();
  
  if ($identify->getCode() !== 0) {
    $return_array['message'] = $strmethoderr ." Imagemagick command failed. Messge: '". trim($identify->getOutput()) ."'";
    $return_array['code'] = 3;
    return $return_array;
  }
  
  $mean = (float) $identify->getOutput();
	unset($identify);
    
  $invert = null;
  if ($mean < .5) $invert = " -negate"; // ha a kep tobbsegeben sotet, akkor invert
  $cmd_convert = $onice ." convert ". $imagepath . $invert ." -colorspace gray +repage -auto-level -resize 200% -threshold 35% -type bilevel -trim PNG:". $out_img;
  $imginvert = new runExt($cmd_convert, 10.0);
  
  if (!$imginvert->run()) {
    $return_array['message'] = $strmethoderr ." Imagemagick command failed. Messge: '". trim($imginvert->getOutput()) ."'";
    $return_array['code'] = 4;
  } else {
    $return_array['result'] = true;
    $return_array['output'] = $out_img;
  }
	unset($imginvert);
  return $return_array;
}

///////////////////////////////////////////////////////////////////////////////////////////////////
function getOCRtext($image, $workdir, $lang, $textfile) {
///////////////////////////////////////////////////////////////////////////////////////////////////
//
// Description goes here...
//
///////////////////////////////////////////////////////////////////////////////////////////////////
global $app, $jconf, $onice;
  $return_array = array(
    'code'    => -1,
    'result'  => false,
    'message' => null,
    'output'  => null,
  );
  $imagepath = realpath($image);
  $textpath = $workdir . $textfile;
  $OCRtext = '';

  if (!is_dir($workdir) || !is_writeable($workdir)) {
    $return_array['message'] = "'". $workdir . "' is not accessible!";
    return $return_array;
  }
  if ($imagepath === false || !file_exists($imagepath)) {
    $return_array['message'] = "File ('". $imagepath ."') does not exists!";
    return $return_array;
  }
  
  switch ($app->config['ocr_engine']) {
    case 'tesseract':
      // TESSERACT
      $cmd_ocr = $onice ." ". $app->config['ocr_alt'] ." ". $imagepath ." \"". pathinfo($textpath, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR . pathinfo($textpath, PATHINFO_FILENAME) . "\" -l ". $lang;
      break;
      
    case 'cuneiform':
    default:
      // CUNEIFORM
      $cmd_ocr = $onice ." ". $app->config['ocr_alt']." --fax -l ". $lang ." -f text -o ". $textpath ." ". $imagepath;
      break;
  }
	
  $job_ocr = new runExt($cmd_ocr, 10.0);
	$job_ocr->run();
  $return_array['code'] = $job_ocr->getCode();
  if ($job_ocr->getCode() !== 0) {
    $return_array['message'] = "Ocr conversion failed! Message:\n". $job_ocr->getOutput();
    return $return_array;
  } elseif (!file_exists($textpath)) {
    $return_array['message'] = "Ocr result file ('". $textpath ."') cannot be found.\n";
    return $return_array;
  }

  try {
    $handle = fopen($textpath, 'r');
    if ($handle === false) throw new Exception("File cannot be read ('". $textpath ."')!");
    while (!feof($handle)) {
      $OCRtext .= fread($handle, 1024);
    }
    fclose($handle);
    unlink($textpath);
  } catch (Exception $ex) {
    $return_array['message'] = $ex->getMessage();
    $return_array['output'] = $OCRtext;
    return $return_array;
  }
  
  $return_array['result'] = true;
  $return_array['output'] = $OCRtext;
  $return_array['message'] = "OK!";
  return $return_array;
}

///////////////////////////////////////////////////////////////////////////////////////////////////
function sanitizeOCRtext($text = null) {
///////////////////////////////////////////////////////////////////////////////////////////////////
//
// A megadott szovegbol eltavolitja az ismetlodo szekvenciakat, es a specialis karaktereket.
//
// Visszateresi ertekek: string, hiba eseten NULL.
//
///////////////////////////////////////////////////////////////////////////////////////////////////
  if ($text === null || empty($text)) return null;
  
  $patterns = array(
    // 'whitespaces' => array('p' => "/([\\n\\t\\b])+/u",       's' => '\1'),
    'specials'    => array('p' => "/([^[:punct:]\\w\\d\\s]|[^\\w\\d\\s\\.\\'\"@&#\\-\\(\\)\\[\\]\\{\\}\\/\\\\])+/u", 's' => null),
    'gibberish'   => array('p' => "/([^\\s\\w\\@\\']){2,}|(\\s[^\\w\\s\\@\\']\\s)/u", 's' => null), // ne bantsuk az egy-karakteres csoportokat
    // 'gibberish'   => array('p' => "/([^\\s\\w\\@\\']){2,}|(\\s.\\s)/u", 's' => null),            // minden egykarakteres karaktercsoportot eltavolitunk
    'duplicates'  => array('p' => "/\\b(.+?)(\\1{1,})\\b/u", 's' => null),
    'whitespaces' => array('p' => "/(\\s+)/u", 's' => ' '),
  );
  
  reset($patterns);
  do {
    $ptrn = current($patterns);
    $text = preg_replace($ptrn['p'], $ptrn['s'], $text);
    if ($text === null) break;
  } while(next($patterns));
  
  return trim($text);   
}

///////////////////////////////////////////////////////////////////////////////////////////////////
function createOCRsnapshots($recordingid, $images, $snapshotparams, $source) {
///////////////////////////////////////////////////////////////////////////////////////////////////
//
// Az 'images[]' tombben atadott frameket atmeretezi a 'snapshotparams[]'-ban atadott ertekek
// szerint.
// Sikeres futas eseten a 'result' erteke true, az 'output'-ban pedig a lekonvertalt faljlok
// szamat kapjuk vissza.
//
// - recordingid(int): recordings.id
// - images(arr): convertOCR alal eloallitott tomb fajl eleresi utvonalakkal, db_id-kkel.
// - snapshotparams(arr): convertOCR altal eloallitott tomb, a celmappak eleresi utvonalat illete a
//     mereteket tartalmazza.
// - source(str): a forrasmappa eleresi utvonala
// 
// Fajlnev konvencio:
// <RECORDINGS.ID>_<OCR_FRAMES.ID>.jpg
// 
///////////////////////////////////////////////////////////////////////////////////////////////////
  global $onice;
  $return = array(
    'result'  => false,
    'message' => null,
    'output'  => null,
  );
  
  $rid = $recordingid;
  $snapshotsdone = 0;
	
	$job = new runExt();
  
  foreach($images['processed'] as $frameid) {
    $img2resize = $images['frames'][$frameid];
    $cmdresize = $onice ." convert \"". $source . $img2resize['file'] ."\"";
    
		$cmdparts = array();
		$cmdparts[] = "{$onice} convert \"{$source}{$img2resize['file']}\"";
		
		for ( $i = 0; $i < count($snapshotparams['resize']); $i++ ) {
			$size   = $snapshotparams['resize' ][$i];
			$folder = $snapshotparams['folders'][$i];
			$output = $folder . $rid ."_". $img2resize['dbid'] .".jpg";
			$cmdparts[] = "\( +clone -background black -resize {$size}^ -gravity center -extent {$size} -write \"{$output}\" +delete \)";
		}
		
		$cmdparts[] = "null:";
    $cmdresize = implode(' ', $cmdparts);
    $job->run($cmdresize, 10.0);
    
    if ($job->getCode() !== 0) {
      $return['message'] = "Function ". __FUNCTION__ ." failed!". PHP_EOL . $job->getOutput() ."\nCommand: ". $cmdresize;
      $return['output'] = $snapshotsdone;
			unset($job);
      return $return;
    }
    $snapshotsdone++;
  }
  
  $return['output'] = $snapshotsdone;
  $return['result'] = true;
	unset($job);
  return $return;
}

///////////////////////////////////////////////////////////////////////////////////////////////////
function insertOCRdata($recordingid, $framepos, $status, $text = '', $timebase = 1.0) {
///////////////////////////////////////////////////////////////////////////////////////////////////
//
// Beszurja az OCR-rel nyert szoveget az 'ocr_frames' tablaba, amit a 'text' valtozoban
// adtunk at. A 'positionsec' erteket a tombmutato es a 'timebase' alapjan szamitja ki.
//
// Argumentumok:
// - recording = felveltel ID-je
// - framepos  = frame sorszama
// - text      = feltoltendo szoveg
// - timebase  = framek kozotti tavolsag
//
///////////////////////////////////////////////////////////////////////////////////////////////////
  global $db;
  $result = array(
    'result'  => false, // sikerult/nem sikerult
    'message' => null,  // hibauzenet
    'output'  => null,  // a beszurt ocr_frames utolso ID-je
    'query'   => null,
  );
  
  try {
    // szamitsd ki a frame poziciojat masodpercre kerekitve
    $position = (int) round($framepos * $timebase, 0, PHP_ROUND_HALF_UP);
    
    // NOTE::
    // HA A 'TIMEBASE' < 1s AKKOR A SZOMSZEDOS FRAME-EK UGYAN AZT A POSITION-T FOGJAK FELÜLIRNI!!!!!!!!!!!!!!!
    // MODOSITANI KELL!!!!!
    $updateparams = array($recordingid, $position, $text, $status);
    $updatequery = trim("
      INSERT INTO 
        ocr_frames(recordingid, positionsec, ocrtext, status)
      VALUES (
        ". $db->Param('recid') .",
        ". $db->Param('position') .",
        ". $db->Param('text') .",
        ". $db->Param('status') .")");

    $rs = $db->Prepare($updatequery);
    $rs = $db->Execute($updatequery, array($recordingid, $position, $text, $status));
    
    if (isset($rs) && isset($rs->sql)) $result['query'] = $rs->sql;
    else $result['query'] = $updatequery . PHP_EOL . "Params: ". implode(", ", $updateparams);
    
  } catch (Exception $ex) {
    $result['message'] = __FUNCTION__ ." failed! Errormessage: ". $ex->getMessage();
    return $result;
  }
  $result['result'] = true;
  $result['output'] = ( int ) $db->Insert_ID();
  return $result;
}

///////////////////////////////////////////////////////////////////////////////////////////////////
function getLangCode($recordingid, $ocrengine) {
///////////////////////////////////////////////////////////////////////////////////////////////////
//
// Kikeresi az adatbazisbol a 'recordings.languageid' alapjan a felvetel nyelvenek harombetus
// ISO kodjat, majd ellenorzi, hogy az adott OCR engine-ben hasznalhato-e az a nyelv.
//
// Visszateresi ertek:
//  - (string)     - 3-betus nyelvi kod
//  - (bool) false - adatbazis hiba eseten
//  - (null) null  - ha a lekerdezes eredmenyhalmaza ures
//
///////////////////////////////////////////////////////////////////////////////////////////////////
  global $db;
  $ISO_639_2 = null;
  
  $query_langcode = "
    SELECT
      l.id,
      l.shortname,
      l.originalname
    FROM
      recordings AS r,
      languages AS l
    WHERE
      r.id = ". $db->Param('recordingid') ." AND
      r.languageid = l.id";
      
      
  try {
    $langcodes = $db->Prepare($query_langcode);
    $langcodes = $db->Execute($query_langcode, array($recordingid));
    if ($langcodes->EOF) return null;
    $ISO_639_2 = $langcodes->GetArray();
    $ISO_639_2 = $ISO_639_2[0]["shortname"];
    return $ISO_639_2;
  } catch (Exception $ex) {
    return false;
  }
}

///////////////////////////////////////////////////////////////////////////////////////////////////
function getOCRtasks() {
///////////////////////////////////////////////////////////////////////////////////////////////////
//
// Visszaadja az OCR konverziora varo recording-okat.
// 
// Visszateresi ertek:
// - result(bool): sikeresen lefutott a kereses?
// - message: hibauzenet
// - output(array): a konverziora varo felvetelek tombje. (hiba eseten FALSE)
//
///////////////////////////////////////////////////////////////////////////////////////////////////
  global $db, $jconf;
  
  $result = array(
    'result'  => false,
    'message' => null,
    'output'  => null,
    'query'   => null,
  );
  
  try {
    $query = "
      SELECT
        `id`,
        `title`,
        `subtitle`,
        `status`,
        `ocrstatus`,
        `contentmastervideofilename`,
        `contentmastervideoextension`,
        `contentmasterstatus`,
        `contentmastersourceip`,
        `languageid`
      FROM
        `recordings`
      WHERE
        `status` = ". $db->Param('sta') ." AND `contentmasterstatus` REGEXP ". $db->Param('cms') ." AND `ocrstatus` REGEXP ". $db->Param('os');
    
    $recordset = $db->Prepare($query);
    $recordset = $db->execute($query, array(
      $jconf['dbstatus_copystorage_ok'],
      '^('. $jconf['dbstatus_copystorage_ok'] ."|". $jconf['dbstatus_uploaded'] .')$',
      '^('. $jconf['dbstatus_reconvert'] ."|". $jconf['dbstatus_convert'] ."|". $jconf['dbstatus_conv'] .')$')
    );
    if (isset($recordset) && isset($recordset->sql)) $result['query'] = $recordset->sql;
    
    if ($recordset->EOF) {
      $result['message'] = "No recordings to be processed.";
      $result['result']  = true;
    } else {
      $result['message'] = "Ok!";
      $result['result']  = true;
      $result['output']  = $recordset->getArray();
    }
  } catch(Exception $e) {
    $result['message'] = __FUNCTION__ ." failed! ". $e->getMessage();
    $result['output']  = false;
  }
  
  return $result;
}

?>
