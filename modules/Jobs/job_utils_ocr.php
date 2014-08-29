<?php
define('BASE_PATH',	realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

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
$db = $app->bootstrap->getAdoDB();
$db = db_maintain();
$jconf = $app->config['config_jobs'];

// Log related init
$debug = Springboard\Debug::getInstance();

//////////////////////////////////////////// TEST BLOCK ///////////////////////////////////////////	
// make some fake variables
$testrec = array(
	'id'                     => 321,
	'contentmaster_filename' => '/srv/vsq_temp/dev.videosquare.eu/converter/master/321/testvideo4ocr.mp4',
	'languageid'             => 1, 
);
$jconf['ocr_engine'] = 'cuneiform';
// $jconf['ocr_engine'] = 'tesseract';
$jconf['ocr_alt'] = "/home/gergo/cf";
// $jconf['ocr_alt'] = "tesseract";
$jconf['ocr_frame_distance'] = 1;
$jconf['threshold'] =  0.004;

$time_start = time();
$ocr_result = convertOCR($testrec);
var_dump($ocr_result);
print_r("Total duration: ". date("H:i:s", time() - $time_start)) .".". PHP_EOL;
exit;
//////////////////////////////////////////// TEST BLOCK ///////////////////////////////////////////	


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
// - Bulk DB update (mi a maximalis query length?)
//
///////////////////////////////////////////////////////////////////////////////////////////////////	
	global $jconf, $debug, $app, $db;
	$logdir = $jconf['log_dir'];
	$logfile = $jconf['jobid_media_convert'] .'.log';
	
	$result = array(
		'result'         => false,
		'output'         => null,
		'message'        => null,
	);
	$numocrwarns = 0;
	/////////////////////////////////////////////////////////
	$wdir    = $jconf['ocr_dir'] . $rec['id'] ."/wdir". DIRECTORY_SEPARATOR;         // source directory
	$cmpdir  = $jconf['ocr_dir'] . $rec['id'] ."/cmp". DIRECTORY_SEPARATOR;          // folder for comparing downsized frames
	$tempdir = $jconf['ocr_dir'] . $rec['id'] ."/temp". DIRECTORY_SEPARATOR;         // folder for prepared frames
	$snapdir = $jconf['ocr_dir'] . $rec['id'] ."/ocrsnapshots". DIRECTORY_SEPARATOR; // output directory
	
	$snapshotparams = array('resize' => array(), 'folders' => array());              // resize values and destination folders for ocr-snashots
	foreach ($app->config['videothumbnailresolutions'] as $tres) {                   // foldernames are derived from thumbnailresolutions + original
		$tmp = explode("x", $tres);
		$snapshotparams['resize'][] = $tres;
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
	
	$debug->log($logdir, $logfile, str_pad("[INFO] Starting ocr process on recording #". $rec['id'] .".", 100, '=', STR_PAD_BOTH), false);

	// IDEIGLENES KONYVTARAK ELOKESZITESE ///////////////////
	$prepOCRdirs = array();
	$folders2create = array_merge(array($wdir, $cmpdir, $tempdir, $snapdir), $snapshotparams['folders']);
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
	$cmd_test_imagick = "convert -version";
	$cmd_test_ocr = "type \"". $jconf['ocr_alt'] ."\"";
	if (runExt($cmd_test_imagick)['code'] !== 0 ) {
		$result['message'] = $errstr ." Imagick utility missing!";
		$debug->log($logdir, $logfile, $result['message'], $sendmail = false);
		return $result;
	} elseif (runExt($cmd_test_ocr)['code'] !== 0) {
		$result['message'] = $errstr ." OCR engine cannot be found!";
		$debug->log($logdir, $logfile, $result['message'], $sendmail = false);
		return $result;
	}
	
	// NYELVI KOD ELLENORZESE ///////////////////////////////
	$langcode = getLangCode($rec, $jconf['ocr_engine']);
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
	$cmd_explode = escapeshellcmd($jconf['ffmpeg_alt'] ." -v ". $jconf['ffmpeg_loglevel'] ." -i ". $rec['contentmaster_filename'] ." -filter_complex  'scale=w=320:h=180:force_original_aspect_ratio=decrease' -r ". $jconf['ocr_frame_distance'] ." -q:v 1 -f image2 ". $cmpdir ."%06d.jpg -r ". $jconf['ocr_frame_distance'] ." -q:v 1 -f image2 ". $wdir ."%06d.jpg");
	
	$debug->log($logdir, $logfile, "Extracting frames from video. Command line:". PHP_EOL . $cmd_explode);
	
	$err = runExt($cmd_explode);

	// $err['code'] = 0; //// DEBUG
	if ($err['code'] !== 0) {
		$msg = "[ERROR] Can't extract frames from video! Message:\n". $err['message'] ."Command:\n". $cmd_explode;
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
		$debug->log($logdir, $logfile, "Frame extraction finished. Total number of frames: ". count($frames['frames']), false);
	}

	// REDUNDANS FRAME-EK KISZURESE /////////////////////////

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

		$cmdIMdiff = "convert \"". $img1 ."\" \"". $img2 ."\" -compose difference -colorspace gray -composite png:- | identify -verbose -format %[fx:mean] png:-";
		$IMdiff = runExt($cmdIMdiff);

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

	// SZOVEG KINYERESE FRAMEKBOL ///////////////////////////
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
		$debug->log($logdir, $logfile, $result['message'], false);
		return $result;
	}
	
	// SZOVEG VISSZATOLTESE AZ ADATBAZISBA //////////////////
	$debug->log($logdir, $logfile, "Updating database.", false);
	foreach ($frames['processed'] as $f) {
		$updateocr = updateOCRdata($rec['id'], $f + 1, $frames['frames'][$f]['text'], $jconf['ocr_frame_distance'], $jconf['dbstatus_conv']);
		if ($updateocr['result'] === false) {
			$msg = "[ERROR] " . $updateocr['message'];
			$debug->log($logdir, $logfile, $msg, $sendmail = false);
			$result['message'] = $msg;
			return $result;
		}
		$frames['frames'][$f]['dbid'] = $updateocr['output'];
	}
	
	// THUMBNAIL-EK GENERALASA //////////////////////////////
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
	
	$result['result']  = true;
	$result['output']  = $frames['frames'];
	$result['message'] = "[OK] OCR process finished successfully!";
	if ($numocrwarns)
		$result['message'] .= PHP_EOL ."Total number of warnings: ". $numocrwarns . PHP_EOL;
	$result['message'] .= "Total number of processed frames: ". count($frames['processed']) .".";
	$debug->log($logdir, $logfile, $result['message'], $sendmail = false);
	
	return $result;
}
///////////////////////////////////////////////////////////////////////////////////////////////////
	function prepareImage4OCR($image, $destpath) {
///////////////////////////////////////////////////////////////////////////////////////////////////
//
// Description goes here...
//
// image:image path
// destpath: directory 
//
// + Need some better image preparation method.
// 
///////////////////////////////////////////////////////////////////////////////////////////////////
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
	
	$cmd_identify = "identify -verbose -format %[fx:mean] ". $imagepath;
	$err = runExt($cmd_identify);
	
	if ($err['code'] !== 0) {
		$return_array['message'] = $strmethoderr ." Imagemagick command failed. Messge: '". trim($err['cmd_output']) ."'";
		$return_array['code'] = 3;
		return $return_array;
	}
	
	$mean = (float) $err['cmd_output'];
	unset ($err);
		
	$invert = null;
	if ($mean < .5) $invert = " -negate"; // ha a kep tobbsegeben sotet, akkor invert
	$cmd_convert = "convert ". $imagepath . $invert ." -colorspace gray +repage -auto-level -resize 200% -threshold 35% -type bilevel -trim PNG:". $out_img;
	$err = runExt($cmd_convert);
	
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
global $jconf;
	$return_array = array(
		// 'code'    => 0,
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
	// CUNEIFORM
	$cmd_ocr = $jconf['ocr_alt']." --fax -l ". $lang ." -f text -o ". $textpath ." ". $imagepath;
	
	// TESSERACT
	// $cmd_ocr = $jconf['ocr_alt'] ." ". $imagepath ." \"". pathinfo($textpath, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR . pathinfo($textpath, PATHINFO_FILENAME) . "\" -l ". $lang;

	$err = runExt($cmd_ocr);
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
// Description...
//
// images(arr) full frames array (with processed frames) 
// 
// Thumbnail naming convention:
// <RECORDINGS.ID>_<OCR_FRAMES.ID>.jpg
//
// Directory stucture:
// ocrframes/
//  |`- original
//  |`- <app->config['videothumbnailresolutions']['4:3']>
//  |`- <app->config['videothumbnailresolutions']['wide']>
//   `- <app->config['videothumbnailresolutions']['player']>
///////////////////////////////////////////////////////////////////////////////////////////////////
	global $app;
	$return = array(
		'result'  => false,
		'message' => null,
		'output'  => null,
	);
	
	$rid = $recordingid;
	$snapshotsdone = 0;
	
	foreach($images['processed'] as $frameid) {
		$img2resize = $images['frames'][$frameid];
		$cmdresize = "convert \"". $source . $img2resize['file'] ."\"";
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
		$resize = runExt($cmdresize);
		
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
  function updateOCRdata($recordingid, $framepos, $text = '', $timebase = 1.0, $status) {
///////////////////////////////////////////////////////////////////////////////////////////////////
//
// Need some descriptions here.
//
// recording = felveltel
// framepos  = frame sorszama
// text      = feltoltendo szoveg
// timebase  = framek kozotti tavolsag
//
// Minden OCR-rel nyert szoveget feltolt az 'ocr_frames' tablaba, amit a 'textarray' tombben
// adtunk at. A 'positionsec' erteket a tombmutato es a 'timebase' alapjan szamitja ki.
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
		$result['message'] = "updateOCRdata failed! Errormessage: ". $ex->getMessage();
		return $result;
	}
	$result['result'] = true;
	$result['output'] = ( int ) $db->Insert_ID();
	return $result;
}

///////////////////////////////////////////////////////////////////////////////////////////////////
	function getLangCode($recording, $ocrengine) {
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
			r.id = ". $recording['id'] ." AND
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
	function runExt($cmd) {
///////////////////////////////////////////////////////////////////////////////////////////////////
//
// Description...
//
// => A Job_utils_base run_external() fuggvenye ROSSZ, LE KELL CSERELNI ERRE A VERZIORA!!!!
//  TODO: ELLENORIZNI KELL FMPEG THUMBNAILER-REL!!
//
///////////////////////////////////////////////////////////////////////////////////////////////////
	$cmd .= "; echo $? 1>&3";	// Echo previous command's exit code to file descriptor #3.

	$return_array = array();
	$return_array['pid'] = 1;
	$return_array['code'] = -1;
	$return_array['cmd_output'] = "";

	$descriptorspec = array(
		0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
		1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
		2 => array("pipe", "w"),  // stderr is a file to write to
		3 => array("pipe", "w"),   // pipe for child process (used to capture exit code)
		// 3 => array("file", "/home/gergo/pipe_exitcode.log", "a"), // dump exitcode to file
	);

	$pipes = array();
	$process = proc_open($cmd, $descriptorspec, $pipes);

	// Terminate if process cannot be initated.
	if ( !is_resource($process) ) return $return_array;

	// close child's input imidiately
	fclose($pipes[0]);
	
	for ($i = 1; $i < 2; $i++) {
		stream_set_blocking($pipes[$i], false);
	}
	
	$exitcode = -1;
	$output = "";
	while( !feof($pipes[1]) || !feof($pipes[2]) || !feof($pipes[3])) {
		$read = array();
		if( !feof($pipes[1]) ) $read[1]= $pipes[1];
		if( !feof($pipes[2]) ) $read[2]= $pipes[2];
		if (!feof($pipes[3])) {
			$readcode = rtrim(fgets($pipes[3]), "\n");
			$read[3] = $pipes[3];
			if (strlen($readcode)) $exitcode = intval($readcode);
		}

		$write = NULL;
		$ex = NULL;
		// Check pipelines in array 'read', and wait until somthing apperars on them, and put them back to 'read'
		$ready = stream_select($read, $write, $ex, 1);
		if ( $ready === FALSE ) {
			break; // should never happen - something died
		}
		// Copy data from the previously selected piplines
		foreach ($read as $k => $r) {
			$s = fgets($r, 1024);
			$output .= $s;
		}
	}
	// Close all handle objects
	fclose($pipes[1]);
	fclose($pipes[2]);
	fclose($pipes[3]);
	
	// Get process PID
	$tmp = proc_get_status($process);
	proc_close($process);

	$return_array = array();
	$return_array['pid'] = $tmp['pid'];
	$return_array['code'] = $exitcode;
	$return_array['cmd_output'] = $output;

	return $return_array;
}

?>
