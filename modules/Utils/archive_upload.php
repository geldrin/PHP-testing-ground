<?php

define('BASE_PATH',	realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

$ischeckonly = TRUE;

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');
include_once( BASE_PATH . 'modules/Jobs/job_utils_base.php');
include_once( BASE_PATH . 'modules/Jobs/job_utils_media.php');

// Check operating system - exit if Windows
if ( iswindows() ) {
	echo "ERROR: Non-Windows process started on Windows platform\n";
	exit;
}

// Some basic settings
date_default_timezone_set("Europe/Budapest");
set_time_limit(0);
clearstatcache();

// Load jobs configuration file
$myjobid = "archive_upload";

// Welcome
echo "Videosquare archive uploader v0 - STARTING...\n";

// Init
$app = new Springboard\Application\Cli(BASE_PATH, PRODUCTION);
$app->loadConfig('modules/Jobs/config_jobs.php');
$jconf = $app->config['config_jobs'];

// Log related init
$debug = Springboard\Debug::getInstance();
$debug->log($jconf['log_dir'], $myjobid . ".log", "Archive upload job started", $sendmail = false);

//echo "argc: " . $argc . "\n";
//print_r($_SERVER['argv']);

// Handle command line arguments
if ( $argc >= 2 ) {
	$desc_filename = realpath(trim($argv[1]));
} else {
	echo "ERROR: parameters are missing\nUSAGE: php -f ./archive_upload.php [descriptor file]\n";
	exit -1;
}

if ( !is_readable($desc_filename) ) {
	echo "ERROR: descriptor file \"" . $desc_filename . "\" does not exist\n";
	exit -1;
}

unset($media_filename);
unset($media_starttime);
unset($content_filename);
unset($content_starttime);
unset($media_cuts);
unset($content_cuts);
$media_cuts = array();
$content_cuts = array();
$desc_line = 0;
$warnings = 0;
$issrcpath = FALSE;
$isdstpath = FALSE;

echo "Descriptor file: " . $desc_filename . "\n";

echo "Parsing descriptor file:\n";

$rec_id = 1;
$suffix = "";

$fh = fopen($desc_filename, "r");
while( !feof($fh) ) {

	// Read one line from descriptor file
	$oneline = fgets($fh);
	$oneline = trim($oneline);
	$desc_line++;

	// Skip empty and comment lines
	if ( empty($oneline) ) {
		continue;
	}
	if ( preg_match('/^[\s]*#/', $oneline) ) {
		continue;
	}

	// Source path
	if ( preg_match('/^[\s]*srcpath[\s]*=/', $oneline) ) {
		$tmp = explode("=", $oneline, 2);
		$srcpath = $tmp[1];
		$issrcpath = TRUE;
		continue;
	}

	// Destination path
	if ( preg_match('/^[\s]*dstpath[\s]*=/', $oneline) ) {
		$tmp = explode("=", $oneline, 2);
		$dstpath = $tmp[1];
		$isdstpath = TRUE;
		continue;
	}

	// media_starttime variable found
	if ( preg_match('/^[\s]*media_starttime[\s]*=/', $oneline) ) {
		$tmp = explode("=", $oneline, 2);
		$media_startdatetime = trim($tmp[1]);
		if ( !preg_match('/^[0-9]{4}-[0-1][0-9]-[0-3][0-9][\s]+[0-2][0-9]:[0-5][0-9]:[0-5][0-9]/', $media_startdatetime) ) {
			echo "ERROR (line " . $desc_line . "): media starttime \"" . $media_startdatetime . "\" is not a YYYY-MM-DD HH:MM:SS time value\n";
			exit -1;
		} else {
			echo "Media file start time: " . $media_startdatetime . "\n";
			$tmp = explode(" ", $media_startdatetime, 2);
			$media_startdate = trim($tmp[0]);
			$media_starttime = trim($tmp[1]);
			$media_starttimesec = hms2secs($media_starttime);
		}
		continue;
	} // End of media start date/time check

	// content_starttime variable found
	if ( preg_match('/^[\s]*content_starttime[\s]*=/', $oneline) ) {
		$tmp = explode("=", $oneline, 2);
		$content_startdatetime = trim($tmp[1]);
		if ( !preg_match('/^[0-9]{4}-[0-1][0-9]-[0-3][0-9][\s]+[0-2][0-9]:[0-5][0-9]:[0-5][0-9]/', $content_startdatetime) ) {
			echo "ERROR (line " . $desc_line . "): content starttime \"" . $content_startdatetime . "\" is not a YYYY-MM-DD HH:MM:SS time value\n";
			exit -1;
		} else {
			echo "Content file start time: " . $content_startdatetime . "\n";
			$tmp = explode(" ", $content_startdatetime, 2);
			$content_startdate = trim($tmp[0]);
			$content_starttime = trim($tmp[1]);
			$content_starttimesec = hms2secs($content_starttime);
		}

		continue;
	} // End of media start date/time check

	// Suffix
	if ( preg_match('/^[\s]*suffix[\s]*=/', $oneline) ) {
		$tmp = explode("=", $oneline, 2);
		$suffix = $tmp[1];
		continue;
	}

	// media_filename variable found
	if ( preg_match('/^[\s]*media_filename[\s]*=/', $oneline) ) {

		$tmp = explode("=", $oneline, 2);
		$media_dir = realpath(trim($tmp[1]));
echo "media dir: " . $media_dir . "\n";
		if ( !file_exists($media_dir) ) {
			echo "ERROR: cannot find media directory\n";
			exit -1;
		}
	}

	// Read cut start and end times
	if ( preg_match('/^[\s]*cut:media[\s]*[,][\s]*[0-1][0-9]:[0-5][0-9]:[0-5][0-9][\s]*[,][\s]*[0-1][0-9]:[0-5][0-9]:[0-5][0-9][\s]*/', $oneline) ) {
		echo "OK: " . $oneline . "\n";

		$iscontent = TRUE;
		$tmp = explode(",", $oneline, 5);

		// Event date and time
		$cut_start = trim($tmp[1]);
		$cut_end = trim($tmp[2]);

		// Check if content is present
		if ( !isset($tmp[3]) ) {
			echo "ERROR: nop/pres not defined\n";
			exit -1;
		}

		if ( trim($tmp[3]) == "nop" ) {
			$iscontent = FALSE;
		}

		if ( trim($tmp[3]) == "pres" ) {
			$iscontent = TRUE;
		}

		// Read filename ID
		if ( !isset($tmp[4]) ) {
			echo "ERROR: filename ID not defined\n";
			exit -1;
		}

		unset($video_filename);
		unset($content_filename);

		$fname_id = trim($tmp[4]);
		$video_filename = $media_dir . "/" . $fname_id . "_" . $suffix . ".mp4";
		if ( !file_exists($video_filename) ) {
			echo "ERROR: cannot find video file " . $video_filename . "\n";
			exit -1;
		}

		echo "Media file found: " . $video_filename . "\n";

		// Calculate recording time
		$rec_time = $media_startdate . " " . secs2hms($media_starttimesec + hms2secs($cut_start));
		echo "Recording time: " . $rec_time . "\n";

		if ( $iscontent ) {
			$content_filename = $media_dir . "/" . $fname_id . "_" . $suffix . "_content.mp4";
			if ( !file_exists($content_filename) ) {
				echo "ERROR: cannot find content file " . $content_filename . "\n";
				exit -1;
			}
			echo "Content file found: " . $content_filename . "\n";
		}

	}

}

fclose($fh);


exit;

?>