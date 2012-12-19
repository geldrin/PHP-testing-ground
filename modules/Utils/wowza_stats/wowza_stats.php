#!/usr/bin/php
<?php

define('BASE_PATH',	realpath( __DIR__ . '/../../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');
include_once( BASE_PATH . 'modules/Jobs/job_utils_base.php' );

set_time_limit(0);

date_default_timezone_set("Europe/Budapest");

echo "Wowza log analizer v0.1 - STARTING...\n";

// User settings
$live_channelid = 29;

// **********************************

// Init
$app = new Springboard\Application\Cli(BASE_PATH, PRODUCTION);

// Load jobs configuration file
$app->loadConfig('modules/Jobs/config_jobs.php');
$jconf = $app->config['config_jobs'];

// Establish database connection
try {
	$db = $app->bootstrap->getAdoDB();
} catch (exception $err) {
	echo "[ERROR] No connection to DB (getAdoDB() failed). Error message:\n" . $err . "\n";
	exit -1;
}

// Query channel information
$query = "
	SELECT
		id,
		title,
		starttimestamp,
		endtimestamp,
		userid,
		organizationid
	FROM
		channels
	WHERE
		id = " . $live_channelid;

echo $query . "\n";

try {
	$event = $db->Execute($query);
} catch (exception $err) {
	echo "[ERROR] Cannot query live channel. SQL query failed.\n\n" . trim($query) . "\n";
	exit -1;
}

// Check if one record found
if ( $event->RecordCount() < 1 ) {
	echo "[ERROR] Cannot find live channel. ID = " . $live_channelid . "\n";
	exit -1;
}

$event_info = array();
$event_info = $event->fields;

var_dump($event_info);

exit;

$live_streams = array();

if ( !query_livefeeds($live_channelid, $live_feeds) ) {
	echo "[ERROR] Cannot find live event.\n";
	exit -1;
}

$stream = array();
$stream = $live_feeds->fields;


$stats_intro  = "Videosquare live statistics report\n\n";
$stats_intro .= "Event: " . $live_streams;

exit;

// feedid, contentid, app, description for logging
$feeds = array(
	1 => array(
			'videoid'	=> '148820',
			'contentid'	=> '133253',
			'app'		=> 'vsqlive',
			'desc'		=> 'Normal stream'
		),
	2 => array(
			'videoid'	=> '668018',
			'contentid'	=> '890250',
			'app'		=> 'vsqlive',
			'desc'		=> 'HD stream'
		)
);

// Get current directory
$directory = realpath('.') . "/";

$log_files = dirList($directory, ".log");
sort($log_files, SORT_STRING);

$viewers = array();

// Actual feed to be analyzed
$feed = 1;

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

		// Math log entries: YYYY-MM-DD HH:MM:SS
		if ( preg_match('/^[\s]*[0-9]{4}-[0-1][0-9]-[0-3][0-9][\s]+[0-2][0-9]:[0-5][0-9]:[0-5][0-9][\s]+[A-Z]+[\s]+play/', $oneline) ) {

			$log_line = preg_split('/\t+/', $oneline);

			$log_feedid = trim($log_line[27]);

			//10: x-app = wowza application and feedid match
			if ( ( trim($log_line[10]) == $feeds[$feed]['app'] ) && ( $feeds[$feed]['videoid'] == $log_feedid ) ) {

				$cip = trim($log_line[16]);

echo $log_line[37] . "\n";

exit;

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

function query_livefeeds($live_channelid, &$live_streams) {
global $db, $app;

	$query = "
		SELECT
			a.channelid,
			c.title,
			c.starttimestamp,
			c.endtimestamp,
			a.id as locationid,
			a.name as locationname,
			b.id as streamid,
			b.name as streamname,
			b.keycode,
			b.contentkeycode,
			a.userid,
			a.organizationid
		FROM
			livefeeds as a,
			livefeed_streams as b,
			channels as c
		WHERE
			a.channelid = " . $live_channelid . " AND
			a.id = b.livefeedid AND
			a.channelid = c.id
		ORDER BY
			a.id
	";

echo $query . "\n";

exit;

	try {
		$live_streams = $db->Execute($query);
	} catch (exception $err) {
		echo "[ERROR] Cannot query live feeds and streams. SQL query failed.\n\n" . trim($query) . "\n";
		return FALSE;
	}

	// Check if pending job exsits
	if ( $live_streams->RecordCount() < 1 ) {
		return FALSE;
	}

	return TRUE;
}


?>
