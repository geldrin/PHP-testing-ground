<?php

define('BASE_PATH',	realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

$ischeckonly = FALSE;

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');
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

//echo "argc: " . $argc . "\n";
//print_r($_SERVER['argv']);

// Handle command line arguments
if ( $argc >= 2 ) {
	$desc_filename = realpath(trim($argv[1]));
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
$suffix = "";

$path_parts = pathinfo($desc_filename);
$desc_filename_new = $path_parts['filename'] . "_updated." . $path_parts['extension'];
echo "new desc fname: " . $desc_filename_new . "\n";

$fh = fopen($desc_filename, "r");
$fhn = fopen($desc_filename_new, "w");
while( !feof($fh) ) {

	// Read one line from descriptor file
	$oneline = fgets($fh);
	$oneline = trim($oneline);
	$newline = $oneline;
	$desc_line++;

	// Skip empty and comment lines
	if ( empty($oneline) ) {
		fwrite($fhn, $newline . "\n");
		continue;
	}
	if ( preg_match('/^[\s]*#/', $oneline) ) {
		fwrite($fhn, $newline . "\n");
		continue;
	}

	// Source path
	if ( preg_match('/^[\s]*srcpath[\s]*=/', $oneline) ) {
		$tmp = explode("=", $oneline, 2);
		$srcpath = $tmp[1];
		fwrite($fhn, $newline . "\n");
		continue;
	}

	// Destination path
	if ( preg_match('/^[\s]*dstpath[\s]*=/', $oneline) ) {
		$tmp = explode("=", $oneline, 2);
		$dstpath = $tmp[1];
		fwrite($fhn, $newline . "\n");
		continue;
	}

	// media_filename variable found
	if ( preg_match('/^[\s]*media_filename[\s]*=/', $oneline) ) {
		$tmp = explode("=", $oneline, 2);
		$media_filename = realpath($srcpath . trim($tmp[1]));

		// Invalid input filename
		if ( $media_filename === FALSE ) {
			echo "ERROR (line " . $desc_line . "): media does not exist \"" . $srcpath . trim($tmp[1]) . "\"\n";
			exit -1;
		}

		// Check if media file exists and readable
		if ( !is_readable($media_filename) ) {
			echo "ERROR (line " . $desc_line . "): media file \"" . $media_filename . "\" does not exist or not readable\n";
			exit -1;
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

		fwrite($fhn, $newline . "\n");
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
		fwrite($fhn, $newline . "\n");
		continue;
	} // End of media start date/time check

	// content_filename variable found
	if ( preg_match('/^[\s]*content_filename[\s]*=/', $oneline) ) {
		$tmp = explode("=", $oneline, 2);
		$content_filename = realpath($srcpath . trim($tmp[1]));

		// Invalid input filename
		if ( $content_filename === FALSE ) {
			echo "ERROR (line " . $desc_line . "): invalid content filename \"" . $srcpath . trim($tmp[1]) . "\"\n";
			exit -1;
		}

		// Check if media file exists
		if ( !is_readable($content_filename) ) {
			echo "ERROR (line " . $desc_line . "): content file \"" . $content_filename . "\" does not exist\n";
			exit -1;
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

		fwrite($fhn, $newline . "\n");
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

		fwrite($fhn, $newline . "\n");
		continue;
	} // End of media start date/time check

	// Suffix
	if ( preg_match('/^[\s]*suffix[\s]*=/', $oneline) ) {
		$tmp = explode("=", $oneline, 2);
		$suffix = $tmp[1];
		fwrite($fhn, $newline . "\n");
		continue;
	}

	// Read cut start and end times
	if ( preg_match('/^[\s]*cut:media[\s]*[,][\s]*[0-1][0-9]:[0-5][0-9]:[0-5][0-9][\s]*[,][\s]*[0-1][0-9]:[0-5][0-9]:[0-5][0-9][\s]*/', $oneline) ) {
		echo "OK: " . $oneline . "\n";

		$iscontent = TRUE;
		$tmp = explode(",", $oneline, 5);

		// Event date and time
		$cut_start = trim($tmp[1]);
		$cut_end = trim($tmp[2]);

		// Check no content flag
		if ( isset($tmp[3]) ) {
			if ( trim($tmp[3]) == "nop" ) {
				$iscontent = FALSE;
			}
			if ( trim($tmp[3]) == "pres" ) {
				$iscontent = TRUE;
			}
		} else {
			$newline .= ",pres";
		}

		if ( !isset($tmp[4]) ) {
			$newline .= "," . $rec_id;
			$alloc_id = $rec_id;
		} else {
			$alloc_id = trim($tmp[4]);
		}

		// Process media cut times
		if ( empty($media_cuts[$alloc_id]) ) {

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

			$media_cuts[$alloc_id]['cut_media_start'] = $cut_start;
			$media_cuts[$alloc_id]['cut_media_end'] = $cut_end;
			$media_cuts[$alloc_id]['cut_media_startsec'] = $cut_startsec;
			$media_cuts[$alloc_id]['cut_media_endsec'] = $cut_endsec;

			$media_cuts[$alloc_id]['iscontent'] = $iscontent;

			if ( $alloc_id == $rec_id ) $rec_id++;

			fwrite($fhn, $newline . "\n");
			continue;
		} else {
			echo "ERROR (line " . $desc_line . "): duplicate recording ID in descriptor file (id = " . $rec_id . ")\n";
			exit -1;
		}

	}

}

fclose($fh);
fclose($fhn);

if ( !rename($desc_filename_new, $dstpath . $desc_filename_new) ) {
	echo "ERROR: cannot move descriptor file " . $desc_filename_new . ".temp to " . $dstpath . $desc_filename_new . "\n";
	exit -1;
}

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

// Cut media slices
$path = pathinfo($media_filename);
$cut_path = $dstpath . $path['filename'] . "." . $path['extension'] . "_" . date("Y-m-d_His");
echo "Media directory: " . $cut_path . "\n";
$temp_filename = $cut_path . "/temp.mp4";

if ( file_exists($cut_path) ) {
	echo "ERROR: path " . $cut_path . " exists\n";
	exit -1;
}

if ( !$ischeckonly ) {
	if ( !mkdir($cut_path) ) {
		echo "ERROR: cannot create directory " . $cut_path . "\n";
		exit -1;
	}
}

// Go through list of cuts and call ffmpeg to do the job
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

//var_dump($media_cuts[$key]);

	// Cutting media segment
	$cut_duration = $media_cuts[$key]['cut_media_endsec'] - $media_cuts[$key]['cut_media_startsec'];
//	$command_cut = "ffmpeg -y -v 0 -i " . $media_filename . " -ss " . secs2hms($media_cuts[$key]['cut_media_startsec']) . ".0 -t " . $cut_duration . " ";
//	$command_cut = "ffmpeg -y -i " . $media_filename . " -ss " . secs2hms($media_cuts[$key]['cut_media_startsec']) . ".0 -t " . $cut_duration . " ";
	$target_filename = $cut_path . "/" . $key . "_" . $suffix . "." . $media_type;
$command_cut = "HandBrakeCLI -v 0 --no-dvdnav --optimize -i " . $media_filename . " -o " . $target_filename . " --vb 2000 --start-at duration:" . $media_cuts[$key]['cut_media_startsec'] . " --stop-at duration:" . $cut_duration . " -f mp4";
/*	if ( $media_type == "mp4" ) {
		$command = $command_cut . "-acodec copy -vcodec copy -async 25 -f mp4 " . $target_filename;
	} else {
		$command = $command_cut . "-acodec copy -vcodec copy -async 25 -f flv " . $temp_filename;
	} */

echo $command_cut . "\n";

	if ( !$ischeckonly ) {

		$time_start = time();
		$output = runExternal($command_cut);
		$output_string = $output['cmd_output'];
		$result = $output['code'];
		$duration = time() - $time_start;
		$mins_taken = round( $duration / 60, 2);
		if ( $result == 0 ) {
			echo "ERROR: HandBrake returned an error (" . $result . ")\n";
			echo "OUTPUT:\n" . $output_string . "\n";
			exit -1;
		}

		echo "Successful media cut in " . $mins_taken . " mins.\n";

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

		// Remove temp file
		if ( file_exists($temp_filename) ) {
		  unlink($temp_filename);
		}

	}

// Media:   h264 (Main), yuv420p, 1280x720 [SAR 1:1 DAR 16:9], 1993 kb/s, 24.92 fps, 25 tbr, 2500 tbn, 50 tbc
// Content: h264 (Main), yuv420p, 1280x720 [SAR 1:1 DAR 16:9], 794 kb/s, 29.83 fps, 30 tbr, 3k tbn, 60 tbc

	if ( $media_cuts[$key]['iscontent'] ) {

		// Cutting content segment
		$cut_duration = $media_cuts[$key]['cut_content_endsec'] - $media_cuts[$key]['cut_content_startsec'];
//		$command_cut = "ffmpeg -y -v 0 -i " . $content_filename . " -ss " . secs2hms($media_cuts[$key]['cut_content_startsec']) . ".0 -t " . $cut_duration . " ";
//		$command_cut = "ffmpeg -y -i " . $content_filename . " -ss " . secs2hms($media_cuts[$key]['cut_content_startsec']) . ".0 -t " . $cut_duration . " ";
		$target_filename = $cut_path . "/" . $key . "_" . $suffix . "_content." . $content_type;
$command_cut = "HandBrakeCLI -v 0 --no-dvdnav --optimize -i " . $content_filename . " -o " . $target_filename . " --vb 800 --start-at duration:" . $media_cuts[$key]['cut_content_startsec'] . " --stop-at duration:" . $cut_duration . " -f mp4";
/*		if ( $content_type == "mp4" ) {
			$command = $command_cut . "-acodec copy -vcodec copy -async 25 -f mp4 " . $target_filename;
		} else {
			$command = $command_cut . "-acodec copy -vcodec copy -async 25 -f flv " . $temp_filename;
		} */

	echo $command_cut . "\n";

		if ( !$ischeckonly ) {

			$time_start = time();
			$output = runExternal($command_cut);
//			exec($command_cut, $output, $result);
			$duration = time() - $time_start;
			$mins_taken = round( $duration / 60, 2);
			$output_string = implode("\n", $output);
			if ( $result == 0 ) {
				echo "ERROR: HandBrake returned an error (" . $result . ")\n";
				echo "OUTPUT:\n" . $output_string . "\n";
				exit -1;
			}

			echo "Successful content cut in " . $mins_taken . " mins.\n";

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

			// Remove temp file
			if ( file_exists($temp_filename) ) {
			  unlink($temp_filename);
			}
		}

exit;

	}

}

exit;

?>