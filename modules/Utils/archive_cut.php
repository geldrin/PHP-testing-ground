<?php

define('BASE_PATH',	realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');

// Utils
include_once( BASE_PATH . 'modules/Jobs/job_utils_base.php');

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
$myjobid = "archive_cut";

// Welcome
echo "Videosquare archive creator v0 - STARTING...\n";

// Init
$app = new Springboard\Application\Cli(BASE_PATH, PRODUCTION);
$app->loadConfig('modules/Jobs/config_jobs.php');
$jconf = $app->config['config_jobs'];

// Log related init
$debug = Springboard\Debug::getInstance();
$debug->log($jconf['log_dir'], $myjobid . ".log", "Archive cut job started", $sendmail = false);

//echo BASE_PATH . 'Jobs/config_jobs.php' . "\n";

echo "argc: " . $argc . "\n";
print_r($_SERVER['argv']);

// Handle command line arguments
if ( $argc >= 2 ) {
	$desc_filename = trim($argv[1]);
//	$media_dir = trim($argv[2]);
} else {
	echo "ERROR: parameters are missing\nUSAGE: php -f ./archive_cut.php [descriptor file] [media files directory]\n";
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

echo "Descriptor file: " . $desc_filename . "\n";

echo "Parsing descriptor file:\n";

$rec_id = 1;

$fh = fopen($desc_filename, "r");
while( !feof($fh) ) {

	// Read one line from descriptor file
	$oneline = fgets($fh);
	$desc_line++;

	$oneline = trim($oneline);

	// Skip empty and comment lines
	if ( empty($oneline) ) continue;
	if ( preg_match('/^[\s]*#/', $oneline) ) continue;

	// media_filename variable found
	if ( preg_match('/^[\s]*media_filename[\s]*=/', $oneline) ) {
		$tmp = explode("=", $oneline, 2);
		$media_filename = trim($tmp[1]);

		// Invalid input filename
		if ( !preg_match('/^[a-zA-Z0-9\-\_\.]*\Z/', $media_filename) ) {
			echo "ERROR (line " . $descriptor_line . "): invalid media filename \"" . $media_filename . "\"\n";
			exit -1;
		}

		// Check if media file exists
		if ( !is_readable($media_filename) ) {
			echo "ERROR (line " . $desc_line . "): media file \"" . $media_filename . "\" does not exist\n";
		} else {
			$path_parts = pathinfo($media_filename);
			$media_type = $path_parts['extension'];
			if ( $media_type == "mp4" || $media_type == "m4v" ) {
				$media_type = "mp4";
			} else {
				if ( $media_type == "flv" || $media_type == "f4v" ) {
					$media_type = "f4v";
				} else {
					echo "ERROR: unknown media file type (" . $media_type . ")\n";
					exit -1;
				}
			}
			echo "Media file found: " . $media_filename . "\n";
		}
		continue;

	} // End of media filename check

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

	// content_filename variable found
	if ( preg_match('/^[\s]*content_filename[\s]*=/', $oneline) ) {
		$tmp = explode("=", $oneline, 2);
		$content_filename = trim($tmp[1]);

		// Invalid input filename
		if ( !preg_match('/^[a-zA-Z0-9\-\_\.]*\Z/', $content_filename) ) {
			echo "ERROR (line " . $desc_line . "): invalid content filename \"" . $content_filename . "\"\n";
			exit -1;
		}

		// Check if media file exists
		if ( !is_readable($content_filename) ) {
			echo "ERROR (line " . $desc_line . "): content file \"" . $content_filename . "\" does not exist\n";
		} else {
			$path_parts = pathinfo($content_filename);
			$content_type = $path_parts['extension'];
			if ( $content_type == "mp4" || $content_type == "m4v" ) {
				$content_type = "mp4";
			} else {
				if ( $content_type == "flv" || $content_type == "f4v" ) {
					$content_type = "f4v";
				} else {
					echo "ERROR: unknown content file type (" . $content_type . ")\n";
					exit -1;
				}
			}
			echo "Content file found: " . $content_filename . "\n";
		}
		continue;

	} // End of media filename check

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

	// Read cut start and end times
	if ( preg_match('/^[\s]*cut:media[\s]*[,][\s]*[0-1][0-9]:[0-5][0-9]:[0-5][0-9][\s]*[,][\s]*[0-1][0-9]:[0-5][0-9]:[0-5][0-9][\s]*/', $oneline) ) {
		echo "OK: " . $oneline . "\n";

		$tmp = explode(",", $oneline,3);

		// Event date and time
		$cut_start = trim($tmp[1]);
		$cut_end = trim($tmp[2]);

		// Process media cut times
		if ( empty($media_cuts[$rec_id]) ) {

			// Does cut values exists?
			if ( empty($cut_start) or empty ($cut_end) ) {
				echo "ERROR (line " . $desc_line . "): media cut start and/or end time not specified\n";
				exit -1;
			}

			// Check cut time formatting
			if ( !preg_match('/^[0-9]{2}:[0-5][0-9]:[0-5][0-9]/', $cut_start) or !preg_match('/^[0-9]{2}:[0-5][0-9]:[0-5][0-9]/', $cut_end) ) {
				echo "ERROR (line " . $desc_line . "): invalid media cut start and/or end time\n";
				echo "\t" . $oneline . "\n\n";
				exit -1;
			}

			$cut_startsec = hms2secs($cut_start);
			$cut_endsec = hms2secs($cut_end);

			if ( ( $cut_endsec - $cut_startsec ) < 5 ) {
				echo "ERROR (line " . $desc_line . "): media duration is invalid (" . ( $cut_endsec - $cut_startsec ) . " sec)\n";
				echo "\t" . $oneline . "\n\n";
				exit -1;
			}

			$media_cuts[$rec_id]['cut_media_start'] = $cut_start;
			$media_cuts[$rec_id]['cut_media_end'] = $cut_end;
			$media_cuts[$rec_id]['cut_media_startsec'] = $cut_startsec;
			$media_cuts[$rec_id]['cut_media_endsec'] = $cut_endsec;

			$rec_id++;
		} else {
			echo "ERROR (line " . $desc_line . "): duplicate recording ID in descriptor file (id = " . $rec_id . ")\n";
			exit -1;
		}

	}

}

fclose($fh);

echo "Descriptor file check finished" . (($warnings > 0)?(" (WARNINGS: " . $warnings . ")\n\n"):"\n\n");

// Are media and content file and start date/time specified?
if ( empty($media_filename) ) {
	echo "ERROR: no media file defined\n";
	exit -1;
}
if ( empty($media_startdatetime) ) {
	echo "ERROR: no media file start date/time defined\n";
	exit -1;
}
if ( empty($content_filename) ) {
	echo "ERROR: no content file defined\n";
	exit -1;
}
if ( empty($content_startdatetime) ) {
	echo "ERROR: no content file start date/time defined\n";
	exit -1;
}

// Calculate media and content offset
$media_content_offset = $content_starttimesec - $media_starttimesec;
echo "Media-content offset: " . $media_content_offset . "\n";

// content: 4 sec-cel később indult
// media: vágjuk (manuális értékekkel): 01:00:00 - 01:05:05
// content: 4 sec-cel korábban vágunk

//var_dump($media_cuts);

// Cut media slices
$cut_path = $media_filename . "_" . date("Y-m-d_His");
echo "Media directory: " . $cut_path . "\n";

if ( file_exists($cut_path) ) {
	echo "ERROR: path " . $cut_path . " exists\n";
	exit -1;
}

if ( !mkdir($cut_path) ) {
	echo "ERROR: cannot create directory " . $cut_path . "\n";
	exit -1;
}

$temp_filename = $cut_path . "/temp.mp4";

// Go through list of cuts and call ffmpeg to do the job
$command_template = "ffmpeg -y -i " . $media_filename . " -v 0 ";
foreach($media_cuts as $key => $value) {

	// Remove temp file if exists
	if ( file_exists($temp_filename) ) {
	  unlink($temp_filename);
	}

	$media_cuts[$key]['cut_content_start'] = secs2hms($media_cuts[$key]['cut_media_startsec'] - $media_content_offset);
	$media_cuts[$key]['cut_content_end'] = secs2hms($media_cuts[$key]['cut_media_endsec'] - $media_content_offset);
	$media_cuts[$key]['cut_content_startsec'] = $media_cuts[$key]['cut_media_startsec'] - $media_content_offset;
	$media_cuts[$key]['cut_content_endsec'] = $media_cuts[$key]['cut_media_endsec'] - $media_content_offset;

//	echo "key = " . $key . "\n";
//	echo "s: " . $media_cuts[$key]['cut_starttime'] . "\n";
//	echo "e: " . $media_cuts[$key]['cut_endtime'] . "\n";

var_dump($media_cuts[$key]);

	// Cutting media segment
	$cut_duration = $media_cuts[$key]['cut_media_endsec'] - $media_cuts[$key]['cut_media_startsec'];
	$command_cut = "ffmpeg -y -v 0 -i " . $media_filename . " -ss " . secs2hms($media_cuts[$key]['cut_media_startsec']) . ".0 -t " . $cut_duration . " ";
	$target_filename = $cut_path . "/" . $key . "." . $media_type;
	if ( $media_type == "mp4" ) {
		$command = $command_cut . "-acodec copy -vcodec copy -async 25 -f mp4 " . $target_filename;
	} else {
		$command = $command_cut . "-acodec copy -vcodec copy -async 25 -f flv " . $temp_filename;
	}

echo $command . "\n";

	$time_start = time();
	$output = runExternal($command);
	$output_string = $output['cmd_output'];
	$result = $output['code'];
//	exec($command, $output, $result);
	$duration = time() - $time_start;
	$mins_taken = round( $duration / 60, 2);
//	$output_string = implode("\n", $output);
	if ( $result != 0 ) {
		echo "ERROR: ffmpeg returned an error\n";
		exit -1;
	}

	echo "Successful media cut in " . $mins_taken . " mins. File saved to: " . $temp_filename . "\n";

	if ( $media_type == "f4v" ) {
		// Yamdi: index f4v file
		echo "Indexing " . $temp_filename . " to " . $target_filename . "\n";
		$command = "yamdi -i " . $temp_filename . " -o " . $target_filename;
		$time_start = time();
		exec($command, $output, $result);
		$duration = time() - $time_start;
		$mins_taken = round( $duration / 60, 2);
		$output_string = implode("\n", $output);
		if ( $result != 0 ) {
			echo "ERROR: yamdi returned an error\n";
			exit -1;
		}
	}

	// Remove remaining temp file
	if ( file_exists($temp_filename) ) {
	  unlink($temp_filename);
	}

	// Cutting content segment
	$cut_duration = $media_cuts[$key]['cut_content_endsec'] - $media_cuts[$key]['cut_content_startsec'];
	$command_cut = "ffmpeg -y -v 0 -i " . $content_filename . " -ss " . secs2hms($media_cuts[$key]['cut_content_startsec']) . ".0 -t " . $cut_duration . " ";
	$target_filename = $cut_path . "/" . $key . "_content." . $content_type;
	if ( $content_type == "mp4" ) {
		$command = $command_cut . "-acodec copy -vcodec copy -async 25 -f mp4 " . $target_filename;
	} else {
		$command = $command_cut . "-acodec copy -vcodec copy -async 25 -f flv " . $temp_filename;
	}

echo $command . "\n";

/*	$time_start = time();
	exec($command, $output, $result);
	$duration = time() - $time_start;
	$mins_taken = round( $duration / 60, 2);
	$output_string = implode("\n", $output);
	if ( $result != 0 ) {
		echo "ERROR: ffmpeg returned an error\n";
		exit -1;
	}

	echo "Successful content cut in " . $mins_taken . " mins. File saved to: " . $temp_filename . "\n";

	if ( $content_type == "f4v" ) {
		// Yamdi: index f4v file
		echo "Indexing " . $temp_filename . " to " . $target_filename . "\n";
		$command = "yamdi -i " . $temp_filename . " -o " . $target_filename;
		$time_start = time();
		exec($command, $output, $result);
		$duration = time() - $time_start;
		$mins_taken = round( $duration / 60, 2);
		$output_string = implode("\n", $output);
		if ( $result != 0 ) {
			echo "ERROR: yamdi returned an error\n";
			exit -1;
		}
	}
*/

}

exit;

?>