<?php

define('BASE_PATH',	realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

$ischeckonly = FALSE;

// Set manually
// Ringier: 1
// Richter: 1
// Simple: 0
// Metropol: ?
$slideonright = 1;

/*
4,Reklámfeszt 2012
5,Piackutatók a fogyasztási trendekről (PPT)
6,"Made in Hungary" (MRSZ)
7,Hot Marketing Club Meetup (MRSZ)
8,Reklám, vagy amit nem akartok (MRSZ)
9,Zenei csatornák
10,Hír-név-más konferencia (MPRSZ)
11,Egyéb
12,Elég a sopánkodásból!
13,Reklámaréna (MRSZ)
14,Reklámpszichológia (MRSZ)
15,Az egészség reklámja
16,Sport és média szekció (MRSZ)
17,E-kereskedelem és kommunikáció
18,HR konferencia (MRSZ, Smartstuff)
19,Digital Signage délelőtt (MRSZ)
20,IAB digitális délután
21,Vigyázunk a gyerekekre?
22,NEW Business Konferencia
23,Cannes Lions
24,Védett ötletek
25,A visszatérő vásárló a jó vásárló
26,Reklámipar workshop
*/

$channel = 23;

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');
include_once( BASE_PATH . 'modules/Jobs/job_utils_base.php');
include_once( BASE_PATH . 'modules/Jobs/job_utils_media.php');
include_once( BASE_PATH . 'resources/apitest/httpapi.php');

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

// Open API
$api = new Api('support@videosquare.eu', 'MekkElek123');

$last_comment = "";

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
		$last_comment = $oneline;
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

		if ( $iscontent ) {
			$content_filename = $media_dir . "/" . $fname_id . "_" . $suffix . "_content.mp4";
			if ( !file_exists($content_filename) ) {
				echo "ERROR: cannot find content file " . $content_filename . "\n";
				exit -1;
			}
			echo "Content file found: " . $content_filename . "\n";
		}

		// Assemble metadata
		$language = "hun";
		// Titles? Subtitles?
		$title = $last_comment;
		$metadata = array(
			'title'					=> $title,
//			'subtitle'				=> $subtitle,
			'recordedtimestamp'		=> $media_startdate . " " . secs2hms($media_starttimesec + hms2secs($cut_start)),
//			'description'			=> 'Leírás',
			'copyright'				=> 'Minden jog fenntartva. A felvétel egészének vagy bármely részének újrafelhasználása kizárólag a szerző(k) engedélyével lehetséges.',
			'slideonright'			=> $slideonright,
			'accesstype'			=> 'public',
			'ispublished'			=> 0,
			'isdownloadable'		=> 0,
			'isaudiodownloadable'	=> 0,
			'isembedable'			=> 1,
			'conversionpriority'	=> 200
		);

var_dump($metadata);

//$api->removeRecordingFromChannel( 61, 1 );
//$api->addRecordingToChannel( 61, 1 );

		if ( !$ischeckonly ) {

//$api->modifyRecording(30, $metadata);

			echo "Uploading media: " . $video_filename . " ...\n";

			$recording = $api->uploadRecording($video_filename, $language);

			if ( $recording and isset( $recording['data']['id'] ) ) {
			  
				$recordingid = $recording['data']['id'];
				$api->modifyRecording( $recordingid, $metadata);

				$api->addRecordingToChannel( $recordingid, $channel );

				if ( $iscontent ) {

					echo "Uploading content: " . $content_filename . " ...\n";

					$api->uploadContent( $recordingid, $content_filename);

				}

			}

		}

	}

}

fclose($fh);

exit;

?>