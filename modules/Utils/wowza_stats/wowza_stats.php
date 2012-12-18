#!/usr/bin/php
<?php

include( '/home/conv/dev.videotorium/job_utils.php' );

date_default_timezone_set("Europe/Budapest");

echo "Videotorium Wowza live log analizer v0.01 - STARTING...\n";

//echo "argc: " . $argc . "\n";
//print_r($_SERVER['argv']);

// Handle command line arguments
if ( $argc >= 2 ) {
// 943614
	$feedid = trim($argv[1]);
} else {
	echo "ERROR: parameters are missing\nUSAGE: wowza_feedid\n";
	exit -1;
}

// Get current directory
$directory = realpath('.') . "/";

$log_files = dirList($directory, ".log");
sort($log_files, SORT_STRING);

$viewers = array();

for ( $i = 0; $i < count($log_files); $i++ ) {

	$file = $log_files[$i];

	echo "Processing: " . $file . "\n";

	if ( !is_readable($file) ) {
		echo "WARNING: log file \"" . $file . "\" not readable or does not exist\n";
		continue;
	}

	$line = 0;
	$is_wowzalog = false;
	$fh = fopen($file, "r");
	while( !feof($fh) ) {

		// Read one line from descriptor file
		$oneline = fgets($fh);
		$line++;

		$oneline = trim($oneline);

		// Skip empty and comment lines
		if ( empty($oneline) ) continue;
		if ( preg_match('/^[\s]*#/', $oneline) ) {
			if ( preg_match('/^[\s]*#Software: Wowza Media Server/', $oneline) ) {
				$is_wowzalog = true;
			}
			continue;
		}

		// If Wowza tag is not found in first 5 lines, then skip
		if ( ( $is_wowzalog == false ) && ( $line > 5 ) ) {
			echo "ERROR: not a Wowza log file, skipping\n";
			break;
		}
/*
#Fields:
#  0: date
#  1: time
#  2: tz
#  3: x-event
#  4: x-category
#  5: x-severity
2012-12-14 09:29:39 CET play stream INFO
#  6: x-status
#  7: x-ctx
#  8: x-comment
#  9: x-vhost
# 10: x-app
# 11: x-appinst
200 148820 - _defaultVHost_ vsqlive _definst_
# 12: x-duration
# 13: s-ip
# 14: s-port
# 15: s-uri
# 16: c-ip
# 17: c-proto
0.762  91.120.59.230 1935 rtmp://stream.videosquare.eu:1935/vsqlive/?sessionid=conforg.videosquare.eu_k0nu829dc8viv2q57n5iskvmiqeqhh9c_4 89.132.168.224 rtmp
# 18: c-referrer
# 19: c-user-agent
# 20: c-client-id
# 21: cs-bytes
# 22: sc-bytes
http://conforg.videosquare.eu/flash/TCPlayer.swf?v=_v20121211       WIN 11,5,31,5 1010351969 3760
# 23: x-stream-id
# 24: x-spos
# 25: cs-stream-bytes
# 26: sc-stream-bytes
# 27: x-sname
# 28: x-sname-query
3455    1   0   0   0 148820 -
# 29: x-file-name
# 30: x-file-ext
# 31: x-file-size
# 32: x-file-length
# 33: x-suri
# 34: x-suri-stem
# 35: x-suri-query
# 36: cs-uri-stem
# 37: cs-uri-query
- - - - rtmp://stream.videosquare.eu:1935/vsqlive//148820 rtmp://stream.videosquare.eu:1935/vsqlive//148820 - rtmp://stream.videosquare.eu:1935/vsqlive/ sessionid=conforg.videosquare.eu_k0nu829dc8viv2q57n5iskvmiqeqhh9c_4
*/

		// Math log entries
		if ( preg_match('/^[\s]*[0-9]{4}-[0-1][0-9]-[0-3][0-9][\s]+[0-2][0-9]:[0-5][0-9]:[0-5][0-9][\s]+[A-Z]{4}[\s]+play/', $oneline) ) {

			$log_line = preg_split('/\t+/', $oneline);

//echo $oneline . "\n";

			$log_feedid = trim($log_line[27]);

			//10: x-app = "live" and feedid match
			if ( ( trim($log_line[10]) == "live" ) && ( $feedid == $log_feedid ) ) {

				$cip = trim($log_line[16]);
				if ( empty($viewers[$cip]) ) {
					$viewers[$cip]['hostname'] = gethostbyaddr($cip);
//					$viewers[$cip]['hostname'] = $cip;
					$viewers[$cip]['protocol'] = trim($log_line[17]);

					$hostname = $viewers[$cip]['hostname'];
					if ( $cip == $hostname ) {
						echo $cip . "\n";
					} else {
						echo $cip . " (" . $hostname . ")\n";
					}

				}

				$wowza_url = trim($log_line[33]);

			}

		}

//if ( $line > 200 ) exit;

	}

	fclose($fh);

}

$msg  = "\n\nLog analization started: " . date("Y-m-d H:i:s") . "\n";
$msg .= "Log files processed:\n";

for ( $i = 0; $i < count($log_files); $i++ ) {
	$msg .= " " . $log_files[$i] . "\n";
}

$msg .= "\nViewers:\n";

$number_of_viewers = 0;
foreach($viewers as $key => $value) {

	$cip = $key;
	$hostname = $viewers[$cip]['hostname'];

	if ( $cip == $hostname ) {
		$msg .= " " . $cip . "\n";
	} else {
		$msg .= " " . $cip . " (" . $hostname . ")\n";
	}

	$number_of_viewers++;
}

$msg .= "\nViewers: " . $number_of_viewers . "\n";

// Open log file
$result_file = "log_anal_results.txt";
$fh = fopen($result_file, "w");

if (fwrite($fh, $msg) === FALSE) {
	echo "Cannot write to file (" . $result_file . "\n";
	exit;
}

fclose($fh);

echo "Number of viewers: " . $number_of_viewers . "\n";

//print_r($viewers);

exit;

?>
