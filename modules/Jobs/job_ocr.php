<?php
define('BASE_PATH',	realpath( __DIR__ . '/../..' ) . '/' );
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
$onice = $jconf['nice'];
$nicelevel = $tmp = null;
$tmp = preg_match('/[-]?[\d]+$/', $jconf['encoding_nice'], $nicelevel);
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
//////////////////////////////////////////// TEST BLOCK ///////////////////////////////////////////
// set recording to OCR convert
// updateRecordingStatus($recordingid = 9, $status = 'reconvert', $type = 'ocr'); // DEBUG

// TEST SSH CONNECTION
/*$address = 'stream.videosquare.eu';
$port = '22';

$fp = fsockopen($address, $port, $errno, $errstr, 300);
if(! $fp) {
     echo "No connection.\n";
} else {   
     echo "SSH Available.\n";
} die;*/

/*
// TEST SOME FOLDER CREATING SCRIPT
$convpath = $app->config['convpath'] ."master/9/";
if (!is_dir($convpath)) {
	restore_error_handler();
	$msg = "[INFO] Preparing work directory.\n";
	echo $msg;
	// $debug->log($logdir, $logfile, $msg, false);
	
	$createdir = mkdir($convpath, $mode = 0775, $recursive = true);
	var_dump($createdir);
	if (!$createdir) {
		$tmp = error_get_last();
		var_dump($tmp);
		$msg = "[WARNING] Failed to create directory - ". $tmp['message'] ."\n";
		echo $msg;
		// $debug->log($logpath, $logfile, $msg, true);
	}
}
die;*/
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
	
	while(!is_file( $app->config['datapath'] .'jobs/'. $myjobid .'.stop') && !is_file($app->config['datapath'] .'jobs/all.stop')) {
		$db = db_maintain();
		$app->watchdog();
		
		$action = null;
		$OCRduration = 0;
		$sleep_duration = $jconf['sleep_long'];
		
		try {
			$action = 'PREPARING';
			$tsk = getOCRtasks();

			if( $tsk['result'] === true && !empty($tsk['output']) ) {
				$recording = $tsk['output'][0];
				$msg = "[INFO] Recording #". $recording['id'];
				$msg .= " (". $recording['title'] . (!empty($recording['subtitle']) ? " / ". $recording['subtitle'] : null) .")";
				$msg .= " has been selected for OCR process.";
				$debug->log($logdir, $logfile, $msg, false);
				echo $msg ."\n";
			} else {
				if ($tsk['output'] === null) {
					echo $tsk['message'] . PHP_EOL;
					// no OCR task found, go to sleep
					throw new OCRException('Nothing to do...', null, null, -1);
				} else
					// some error happened (whine about it then go to sleep)
					$sleep_duration *= 10;
					throw new OCRException($tsk['message'], var_export($tsk['output'], 1), null, -1);
			}
			
			if ($recording['ocrstatus'] == $jconf['dbstatus_reconvert']) {
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
				$debug->log($logpath, $logfile, $msg, false);
				
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
			$ssh_template  = "ssh -i " . $jconf['ssh_key'] . " " . $jconf['ssh_user'] . "@" . $recording['contentmastersourceip'] . " ";
			
			/*var_dump(runExt4($ssh_template . "find /srv/vsq/dev.videosquare.eu/recordings/9/9/ -maxdepth 0 -not -empty")); // DEBUG
			var_dump(runExt4($ssh_template . "find /srv/vsq/dev.videosquare.eu/recordings/9/9/empty/ -maxdepth 0 -not -empty")); // DEBUG
			var_dump(runExt4($ssh_template . "find /srv/vsq/dev.videosquare.eu/recordings/9/9/not_empty/ -maxdepth 0 -not -empty")); // DEBUG
			var_dump(runExt4($ssh_template . "find /srv/vsq/dev.videosquare.eu/recordings/9/9/non_existing/ -maxdepth 0 -not -empty")); // DEBUG
			exit; // DEBUG
			*/
			
			// Tavoli konyvtar elokeszitese
			$cmd_check_dst = $ssh_template ."find ". $ocr_dst_dir ." -maxdepth 0 -not -empty";
			$msg = "[INFO] Moving files.\n > Checking remote directory '". $ocr_dst_dir ."'\n COMMAND: '". $cmd_check_dst ."'";
			$debug->log($logdir, $logfile, $msg, false);
			echo $msg;
			$check_dst = runExt4($cmd_check_dst);
			
			if (!empty($check_dst['cmd_output'])) {
			// not empty or error
				if ($check_dst['code'] !== 0) {
					// directory does not exist / inaccessible
					$cmd_create_dir =  $ssh_template . "mkdir -m ". $jconf['directory_access'] ." -p ". $ocr_dst_dir;
					$msg = "Creating remote directory '". $ocr_dst_dir ."'\n COMMAND: '". $cmd_create_dir ."'\n";
					$debug->log($logdir, $logfile, $msg, false);
					echo $msg;
					
					$create_dir = runExt4($cmd_create_dir);
					
					if ($create_dir['code'] !== 0) {
						$msg = "[ERROR] Remote directory cannot be created!\nCOMMAND OUTPUT:\n". $create_dir['cmd_output'];
						echo $msg;
						
						throw new OCRException($msg, $create_dir['cmd_output'], $cmd_create_dir, OCR_UPLOAD_FAILED);
					}
					
					echo " > OK.\n";
				} elseif ($check_dst['code'] === 0) {
					// directory is not empty
				}
					
				/*} elseif ($check_dst['code'] === 0 && $recording['ocrstatus'] == $jconf['dbstatus_reconvert']) {
					// directory is not empty
					$cmd_rmdir = $ssh_template . "rm -R ". $ocr_dst_dir ."*";
					
					$msg = "Cleaning remote directory: '". $ocr_dst_dir ."'\n COMMAND: '". $cmd_rmdir ."'\n";
					$debug->log($logdir, $logfile, $msg, false);
					echo $msg;
					
					$rmdir = runExt4($cmd_rmdir);
					
					if ($rmdir['code'] !== 0) {
						$msg = "[ERROR] Failed to delete the contents of remote directory!\nCOMMAND OUTPUT:\n". $rmdir['cmd_output'] ."\n";
						echo $msg;
						
						throw new OCRException($msg, $rmdir['cmd_output'], $cmd_rmdir, OCR_UPLOAD_FAILED);
					} else {
						$msg = "Result:\n". $rmdir['cmd_output'] ."\n";
						$debug->log($logdir, $logfile, $msg, false);
						echo $msg;
					}
				}*/
			}
			// we have the directory nice and clean
			unset($msg);
			
			// Konyvtarak feltoltese
			$snapdir = $jconf['ocr_dir'] . $recording['id'] ."/ocr". DIRECTORY_SEPARATOR;
			$files = array_slice(scandir($snapdir), 2);
			
			$msg = "Uploading OCR frames from '". $snapdir ."' to '". $ocr_dst_dir ."'.\n";

			$copy_err = null;
			foreach ($files as $f) {
				$copy_err = ssh_filecopy2($server, $snapdir . $f, $ocr_dst_dir, $upload = false);
				
				if ($copy_err['code'] === false) {
					$msg .= "[ERROR] Copying OCR snapshots to frontend has been failed!\nMESSAGE:\n". $copy_err['message'] ."\nRESULT:". $copy_err['result'];
					
					$sleep_duration *= 10;
					throw new OCRException($msg, $copy_err['result'], $copy_err['command'], OCR_UPLOAD_FAILED);
				}
			}
			
			if ($copy_err['code'] === true) {
				$msg = "OCR copying to frontend has been completed.\nMESSAGE: ". $copy_err['message'];
				$debug->log($logdir, $logfile, $msg, false);
				echo $msg;
				updateOCRstatus($recording['id'], null, $jconf['dbstatus_copystorage_ok']);
			} else {
				throw new OCRException($msg, $copy_err['result'], $copy_err['command'], OCR_UPLOAD_FAILED);
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
						$status = null;
						break;
				}
				
				$report = $action ." FAILED.\n";
				if (isset($OCRresult)) $report .= " > Phase:". $OCRresult['phase'] ."\n";
				if ($ox->getCommand()) $report .= " > Command: ". $ox->getCommand() ."\n";
				if ($ox->getMessage()) $report .= " > Message: ". $ox->getMessage() ."\n";
				if ($ox->getData())    $report .= " > Info: ". $ox->getData() ."\n";
				
				updateRecordingStatus($recording['id'], $status, $type = 'ocr');
				log_recording_conversion($recording['id'], $myjobid, $action, $status, $ox->getCommand(), $report, $OCRduration, true);
				$debug->log($logdir, $logfile, str_pad("[ CONVERSION END ]", 100, '-', STR_PAD_BOTH), false);
			}
		}
		unset($msg, $OCRresult, $tsk, $recording); // cleanup junk
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
// HIBAKEZELES:
// - Legyen szigoru vagy a kisebb hibakat hagyja figyelmen kivul?
// - Milyen hibauzeneteket adjon vissza a fuggveny? (Irassuk ki az osszes hibas frame-et, vagy
//   vagy inkabb mindig az utolso esemenyt adjuk vissza?
// - Legyen egy max. hibahatar, ami utan kilep a feldolgozasbol? ('numocrwarns')
//
// OPTIMALIZACIO:
// - torles: ne egyenkent torolgesse a fajlokat, hanem egyszerre                              -> OK
// - IM - parancsok osszelinkelese, ahol lehet                                                -> OK
// - Redundancia ellenorzes pici kepeken??
//       |`- 300px:  22.1 sec   !!!
//        `- 1280px: 206.4 sec
// - Monokrom (bilevel) kepek mentese OCRprep-nel (-10%!)                                     -> OK
// - Atmerezezes csökkentese: 300% -> 200%
//    -> ~35%-al gyorsabb, le kell tesztelni, hogy tetszik az OCR-nek!!!!
// - Bulk DB update (mi a maximalis query length?) => SHOW VARIABLES LIKE 'max_allowed_packet';
//
///////////////////////////////////////////////////////////////////////////////////////////////////
	global $jconf, $debug, $onice, $app, $db, $logdir, $logfile;
	
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
	
	$snapshotparams = array('resize' => array(), 'folders' => array());              // resize values and destination folders for ocr-snashots
	foreach ($app->config['videothumbnailresolutions'] as $tres) {                   // foldernames are derived from thumbnailresolutions + original
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

	$errstr = "Function convertOCR(rec#". $rec['id'] .") failed!"; //// Megtarthato?????
	
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
	$cmd_test_ocr = "type \"". $jconf['ocr_alt'] ."\"";
	$imtest = runExt4($cmd_test_imagick);
	$ocrtest = runExt4($cmd_test_ocr);
	if ($imtest['code'] !== 0 ) {
		$result['message'] = $errstr ." Imagick utility missing!";
		$debug->log($logdir, $logfile, $result['message'], $sendmail = false);
		return $result;
	} elseif ($ocrtest['code'] !== 0) {
		$result['message'] = $errstr ." OCR engine cannot be found!";
		$debug->log($logdir, $logfile, $result['message'], $sendmail = false);
		return $result;
	}
	
	// NYELVI KOD ELLENORZESE ///////////////////////////////
	$result['phase'] = "Checking language code";
	$langcode = getLangCode($rec['id'], $jconf['ocr_engine']);
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
	
	// KEPKOCKAK KINYERESE //////////////////////////////////
	$result['phase'] = "Extracting frames from video";
	$cmd_explode = escapeshellcmd($onice ." ". $jconf['ffmpeg_alt'] ." -v ". $jconf['ffmpeg_loglevel'] ." -i ". $rec['contentmasterfile'] ." -filter_complex  'scale=w=320:h=180:force_original_aspect_ratio=decrease' -r ". $jconf['ocr_frame_distance'] ." -q:v 1 -f image2 ". $cmpdir ."%06d.jpg -r ". $jconf['ocr_frame_distance'] ." -q:v 1 -f image2 ". $wdir ."%06d.jpg");
	
	$debug->log($logdir, $logfile, "Extracting frames from video. Command line:". PHP_EOL . $cmd_explode);
	
	$err = runExt4($cmd_explode);

	// $err['code'] = 0; //// DEBUG
	if ($err['code'] !== 0) {
		$msg = "[ERROR] Can't extract frames from video! Message:\n". $err['cmd_output'] ."Command:\n". $cmd_explode;
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
		
		$img1 = $cmpdir . $frames['frames'][$p1]['file'];
		$img2 = $cmpdir . $frames['frames'][$p2]['file'];

		$cmdIMdiff = $onice ." convert \"". $img1 ."\" \"". $img2 ."\" -compose difference -colorspace gray -composite png:- | identify -verbose -format %[fx:mean] png:-";
		$IMdiff = runExt4($cmdIMdiff);

		if ($IMdiff['code'] !== 0) {
			// $debug->log($logdir, $logfile, "[WARN] Comparing frames (". ($p2 - 1) ."/". $p2 .") failed! Message:". $IMdiff['cmd_output'] ."\n", false);
			$numocrwarns++;
			continue;
		}	else {
			$mean = floatval($IMdiff['cmd_output']);
			if ($mean > $jconf['ocr_threshold']) {		// kulonbozik
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
				'start'	=> ($frames['transitions'][$i - $cntr]),
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
	if (!empty($motion)) {
		$debug->log($logdir, $logfile, "Removing motion scenes...", false);
		$frames2remove = array();

		foreach($motion as $movie_scene) {
			$frames2remove += array_intersect($frames['transitions'], range(($movie_scene['start'] + 1), $movie_scene['stop']));

// print_r("picking movie scene sample at ". (floor(abs($movie_scene['start'] - $movie_scene['stop']) / 2) + $movie_scene['start']) ." (between ". $movie_scene['start'] ."-". $movie_scene['stop'] .")". PHP_EOL);

			// get frames from the middle of a scene (igoring poor quality frames right after keyframes/scene cuts)
			$frames['sorted'][] = ( int ) floor(abs($movie_scene['start'] - $movie_scene['stop']) / 2) + $movie_scene['start'];
		}

		$frames['sorted'] = array_diff($frames['transitions'], $frames2remove);
		$frames['sorted'] = array_values($frames['sorted']);
		unset($motion, $frames2remove);
	}
	
	if (empty($frames['sorted'])) {
		$result['result'] = true;
		$result['message'] = "[INFO] No frames to be processed, ending OCR process.";
		$result['warnings'] = $numocrwarns;
		return $result;
	}

	// SZOVEG KINYERESE FRAMEKBOL ///////////////////////////
	$result['frames'] = "Extracting text";
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
		if ($ocr['result'] === false)	{
			$log_ocr_progress .= "[WARN] ". $ocr['message'] ." at frame#". ($ptr + 1) . PHP_EOL;
			$numocrwarns++;
			$ocr_proc_errors++;
			continue;
		}
		$text = addslashes(trim($ocr['output']));

		if (!empty($text)) {
			$frames['frames'][$ptr]['text'] = $text;
			$frames['processed'][] = $ptr;
		}
		// Ellenorzes?? Pl.:
		//  - van-e benne egyaltalan szoveges karakter?
		//  - mekkora az aranya a specialis es szoveges karaktereknek?
		//  - mekkora az aranya azoknak a szavaknak, amelyekben specialis karakterek is vannak?
		//  - mekkora aranyban vannak a massalhangzok/maganhangzok?
		// Charecter encoding?? (iconv)
	}
	
	$log_ocr_progress .= count($frames['processed']) . " frames has been processed out of ". count($frames['sorted']) .".\nTotal number of warnings: " . $ocr_proc_errors .".";
	$debug->log($logdir, $logfile, $log_ocr_progress, false);
	unset($log_ocr_progress, $ocr_proc_errors);
	
	if (empty($frames['processed'])) {
		$result['result'] = true;
		$result['message'] = "[INFO] No frames to be updated to database, ending OCR process.";
		$result['warnings'] = $numocrwarns;
		return $result;
	}
	
	// SZOVEG VISSZATOLTESE AZ ADATBAZISBA //////////////////
	$result['phase'] = "Updating database";
	$debug->log($logdir, $logfile, "Updating database.", false);
	foreach ($frames['processed'] as $f) {
		$updateocr = insertOCRdata($rec['id'], $f + 1, $frames['frames'][$f]['text'], $jconf['ocr_frame_distance'], $jconf['dbstatus_conv']);
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
	
	foreach($frames['sorted'] as $f) {
		$src = $wdir . $frames['frames'][$f]['file'];
		$dst = $snapshotparams['folders']['original'] . DIRECTORY_SEPARATOR . $rec['id'] ."_". $frames['frames'][$f]['dbid'] .".jpg";
		if (!copy($src, $dst)) {
			$msg = "[ERROR] Failed to copy \"". $src ."\" to \"". $dst ."\"!";
			$result['message'] = $msg;
			return $result;
		}
	}
	
	////// UPDATE OCR_FRAMES.STATUS - SZUKSEGES?
	////// -> dbstatus_copystorage(copyingtostorage)
	
	$result['result']    = true;
	$result['output']    = $frames['frames'];
	$result['message']   = "[OK] OCR process finished successfully!";
	// if ($numocrwarns)
		// $result['message'] .= PHP_EOL ."Total number of warnings: ". $numocrwarns;
	// $result['message'] .= PHP_EOL ."Total number of processed frames: ". count($frames['processed']) .".";
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
	$strmethoderr = "Function prepareImage4OCR failed!";
	
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
	$err = runExt4($cmd_identify);
	
	if ($err['code'] !== 0) {
		$return_array['message'] = $strmethoderr ." Imagemagick command failed. Messge: '". trim($err['cmd_output']) ."'";
		$return_array['code'] = 3;
		return $return_array;
	}
	
	$mean = (float) $err['cmd_output'];
	unset ($err);
		
	$invert = null;
	if ($mean < .5) $invert = " -negate"; // ha a kep tobbsegeben sotet, akkor invert
	$cmd_convert = $onice ." convert ". $imagepath . $invert ." -colorspace gray +repage -auto-level -resize 200% -threshold 35% -type bilevel -trim PNG:". $out_img;
	$err = runExt4($cmd_convert);
	
	if ($err['code'] !== 0) {
		$return_array['message'] = $strmethoderr ." Imagemagick command failed. Messge: '". trim($err['cmd_output']) ."'";
		$return_array['code'] = 4;
	} else {
		$return_array['result'] = true;
		$return_array['output'] = $out_img;
	}
	return $return_array;
}

///////////////////////////////////////////////////////////////////////////////////////////////////
function getOCRtext($image, $workdir, $lang, $textfile) {
///////////////////////////////////////////////////////////////////////////////////////////////////
//
// Description goes here...
//
///////////////////////////////////////////////////////////////////////////////////////////////////
global $jconf, $onice;
	$return_array = array(
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
	
	switch ($jconf['ocr_engine']) {
		case 'tesseract':
			// TESSERACT
			$cmd_ocr = $onice ." ". $jconf['ocr_alt'] ." ". $imagepath ." \"". pathinfo($textpath, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR . pathinfo($textpath, PATHINFO_FILENAME) . "\" -l ". $lang;
			break;
			
		case 'cuneiform':
		default:
			// CUNEIFORM
			$cmd_ocr = $onice ." ". $jconf['ocr_alt']." --fax -l ". $lang ." -f text -o ". $textpath ." ". $imagepath;
			break;
	}

	$err = runExt4($cmd_ocr);
	if ($err['code'] !== 0) {
		$return_array['message'] = "Ocr conversion failed! Message:\n". $err['cmd_output'];
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
	global $app, $onice;
	$return = array(
		'result'  => false,
		'message' => null,
		'output'  => null,
	);
	
	$rid = $recordingid;
	$snapshotsdone = 0;
	
	foreach($images['processed'] as $frameid) {
		$img2resize = $images['frames'][$frameid];
		$cmdresize = $onice ." convert \"". $source . $img2resize['file'] ."\"";
		$i = 0;
		do {
			$size = $snapshotparams['resize'][$i];
			$folder = $snapshotparams['folders'][$i] . DIRECTORY_SEPARATOR;
			$cmdresize .= " \( +clone -resize ". $size ."^ -gravity center -extent ". $size ." -write \"". $folder . $rid ."_". $img2resize['dbid'] .".jpg\" +delete \)";
			if ($i >= (count($snapshotparams['resize']) - 1))	{
				$cmdresize .= " -resize ". $size ."^ -gravity center -extent ". $size ." \"". $folder . $rid ."_". $img2resize['dbid'] .".jpg\"";
				break;
			}
			$i++;
		} while (1);
		
		$cmdresize = trim($cmdresize);
		$resize = runExt4($cmdresize);
		
		if ($resize['code'] !== 0) {
			$return['message'] = "Function createOCRsnapshots failed!". PHP_EOL . $resize['cmd_output'] ."\nCommand: ". $cmdresize;
			$return['output'] = $snapshotsdone;
			return $return;
		}
		$snapshotsdone++;
	}
	
	$return['output'] = $snapshotsdone;
	$return['result'] = true;
	return $return;
}

///////////////////////////////////////////////////////////////////////////////////////////////////
function insertOCRdata($recordingid, $framepos, $text = '', $timebase = 1.0, $status) {
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
	);
	
	try {
		// szamitsd ki a frame poziciojat masodpercre kerekitve
		$position = (int) round($framepos * $timebase, 0, PHP_ROUND_HALF_UP);
		
		// NOTE::
		// HA A 'TIMEBASE' < 1s AKKOR A SZOMSZEDOS FRAME-EK UGYAN AZT A POSITION-T FOGJAK FELÜLIRNI!!!!!!!!!!!!!!!
		// MODOSITANI KELL!!!!!
		
		$updatequery = trim("
			INSERT INTO 
				ocr_frames(recordingid, positionsec, ocrtext, status)
			VALUES (
				". $recordingid .",
				". $position .",
				'". $text ."',
				'". $status ."')");

		$db->Execute($updatequery);
	} catch (Exception $ex) {
		$result['message'] = "insertOCRdata failed! Errormessage: ". $ex->getMessage();
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
			r.id = ". $recordingid ." AND
			r.languageid = l.id";
	try {
		$langcodes = $db->Execute($query_langcode);
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
	);
	
	try {
		$query = "
			SELECT
				id,
				title,
				subtitle,
				status,
				ocrstatus,
				contentmastervideofilename,
				contentmastervideoextension,
				contentmasterstatus,
				contentmastersourceip,
				languageid
			FROM
				recordings
			WHERE
				(status = '". $jconf['dbstatus_copystorage_ok'] ."' AND contentmasterstatus REGEXP '". $jconf['dbstatus_copystorage_ok'] ."|". $jconf['dbstatus_uploaded'] ."') AND
				ocrstatus REGEXP '". $jconf['dbstatus_reconvert'] ."|". $jconf['dbstatus_convert'] ."'";
				
		$recordset = $db->execute($query);

		if ($recordset->EOF) {
			$result['message'] = "No recordings to be processed.";
			$result['result']  = true;
		} else {
			$result['message'] = "Ok!";
			$result['result']  = true;
			$result['output']  = $recordset->getArray(); 
		}
	} catch(Exception $e) {
		$result['message'] = "GetOCRtask failed! ". $e->getMessage();
		$result['output']  = false;
	}
	
	return $result;
} 
?>