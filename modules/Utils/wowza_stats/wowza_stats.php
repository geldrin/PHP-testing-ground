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
$ip_encoder = "89.133.214.122";

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
		a.id,
		a.title,
		a.starttimestamp,
		a.endtimestamp,
		a.userid,
		a.organizationid,
		b.name,
		b.url,
		b.domain
	FROM
		channels as a,
		organizations as b
	WHERE
		a.id = " . $live_channelid . " AND
		b.id = a.organizationid";

//echo $query . "\n";

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

//var_dump($event_info);

// Wowza app: vsqlive or devvsqlive if dev site is used
$isdev = FALSE;
$wowza_app = "vsqlive";
if ( $app->config['baseuri'] != "videosquare.eu/" ) {
	$isdev = TRUE;
	$wowza_app = "devvsqlive";
}

// Streams: query locations and streams for live channels
$live_streams = array();
if ( !query_livefeeds($event_info['id'], $live_streams) ) {
	echo "[ERROR] Cannot find live event.\n";
	exit -1;
}

// Stream information transformation to array
$location_info = array();
$locationid_prev = -1;
while ( !$live_streams->EOF ) {
	$stream = array();
	$stream = $live_streams->fields;

	$locationid = $stream['locationid'];
	if ( empty($location_info[$locationid]) ) $location_info[$locationid] = array();

	$tmp = array(
		'streamid'			=> $stream['streamid'],
		'streamname'		=> $stream['streamname'],
		'keycode'			=> $stream['keycode'],
		'contentkeycode'	=> $stream['contentkeycode'],
		'locationname'		=> $stream['locationname']
	);
	array_push($location_info[$locationid], $tmp);

	$live_streams->MoveNext();
}

//var_dump($location_info);

// Start log analyzing

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
		if ( preg_match('/^[\s]*[0-9]{4}-[0-1][0-9]-[0-3][0-9][\s]+[0-2][0-9]:[0-5][0-9]:[0-5][0-9][\s]+[A-Z]+[\s]+(play|destroy)/', $oneline) ) {

			$log_line = preg_split('/\t+/', $oneline);

			$log_feedid = trim($log_line[27]);
			if ( empty($log_feedid) ) continue;
//echo "logfeedid: " . $log_feedid . "\n";

			//10: x-app = match wowza application
			if ( trim($log_line[10]) != $wowza_app ) continue;

//var_dump($log_line);

			// Find Wowza stream ID in location information array
			foreach ($location_info as $key => $value) {
				$locationid = $key;
				foreach ($location_info[$locationid] as $skey => $svalue) {
					$streamid = $skey;
//var_dump($location_info[$locationid][$streamid]);
					// Check if this stream record matches Wowza stream ID
					$tmp = array_search($log_feedid, $location_info[$locationid][$streamid]);
					if ( $tmp !== FALSE ) {
						// We have a match, add this data to overall statistics
						if ( $tmp == "keycode" ) {
//echo "found!\n";

							// x-event: play or destroy (calculate duration when destroy occurs)
							$isdestroy = FALSE;
							if ( trim($log_line[3]) == "destroy" ) $isdestroy = TRUE;

							// Client IP
							$cip = trim($log_line[16]);
							// Client session and parameters
							$csession = trim($log_line[37]);

							$tmp = explode("&", $csession);

							// Find Vsq user ID
							$uid_found = FALSE;
							$uid = 0;
							for ( $i = 0; $i < count($tmp); $i++) {
								if ( stripos($tmp[$i], "uid=") !== FALSE ) {
									$tmp2 = explode("=", $tmp[$i], 2);
									$uid = $tmp2[1];
									$uid_found = TRUE;
								}
							}

/*if ( !$uid_found ) {
	echo $cip . " : " . $log_line[3] . " : " . $uid . " : " . $log_line[12] . "\n";
} */

							// User ID: store Vsq user ID and add data. If no user ID is given, then use ID = 0 for storing all client IPs
							if ( empty($viewers[$cip]) ) {
								$viewers[$cip]['connections'] = 1;
								// Encoder: flag if matches encoder IP
								$viewers[$cip]['encoder'] = 0;
								if ( $cip == $ip_encoder ) $viewers[$cip]['encoder'] = 1;

								// User ID: update if exists
								if ( empty($viewers[$cip]['uid']) ) $viewers[$cip]['uid'] = 0;
								if ( $uid > 0 ) $viewers[$cip]['uid'] = $uid;

								$viewers[$cip]['hostname'] = gethostbyaddr($cip);
								$viewers[$cip]['protocol'] = trim($log_line[17]);
								$viewers[$cip]['streams'] = array();
								$keycode = $location_info[$locationid][$streamid]['keycode'];
								$viewers[$cip]['streams'][$keycode]['locationid'] = $locationid;
								$viewers[$cip]['streams'][$keycode]['streamid'] = $streamid;
								if ( $isdestroy) $viewers[$cip]['streams'][$keycode]['duration'] = trim($log_line[12]);

/*								$hostname = $viewers[$cip]['hostname'];
								if ( $cip == $hostname ) {
									echo $cip . "\n";
								} else {
									echo $cip . " (" . $hostname . ")\n";
								} */
							} else {
								$viewers[$cip]['connections']++;

								// User ID: update if exists
								if ( empty($viewers[$cip]['uid']) ) $viewers[$cip]['uid'] = 0;
								if ( $uid > 0 ) $viewers[$cip]['uid'] = $uid;

								$keycode = $location_info[$locationid][$streamid]['keycode'];
								if ( empty($viewers[$cip]['streams'][$keycode]) ) {
									$viewers[$cip]['streams'][$keycode]['duration'] = 0;
									$viewers[$cip]['streams'][$keycode]['locationid'] = $locationid;
									$viewers[$cip]['streams'][$keycode]['streamid'] = $streamid;
								}
								if ( $isdestroy) {
									if ( empty($viewers[$cip]['streams'][$keycode]['duration']) ) $viewers[$cip]['streams'][$keycode]['duration'] = 0;
									$viewers[$cip]['streams'][$keycode]['duration'] += trim($log_line[12]);
								}
							}

						} // keycode
					}
				} // foreach
			} // foreach
		}

//if ( $line > 1000 ) break;

	}

	fclose($fh);
}

//var_dump($viewers);

$msg  = "# Videosquare live statistics report\n\n";
$msg .= "# Log analization started: " . date("Y-m-d H:i:s") . "\n";
$msg .= "# Log files processed:\n";

for ( $i = 0; $i < count($log_files); $i++ ) {
	$msg .= "#\t" . $log_files[$i] . "\n";
}

$msg .= "# Event: " . $event_info['title'] . "\n";
$msg .= "# Start date: " . $event_info['starttimestamp'] . "\n";
$msg .= "# End date: " . $event_info['endtimestamp'] . "\n";
$msg .= "# Customer: " . $event_info['name'] . " - " . $event_info['url'] . "\n";
$msg .= "# Domain: " . $event_info['domain'] . "\n\n";

$msg .= "\nViewers:\n\n";

$msg .= "userID,username,IP address,hostname,Number of connections,Stream1,Stream1 time,Stream2,Stream2 time\n";

$number_of_viewers = 0;
foreach($viewers as $cip => $client) {

//echo $cip . "\n";

	$user = array();
	$uid = $viewers[$cip]['uid'];
	if ( $uid > 0 ) {
		if ( !query_user($uid, $user) ) {
			echo "[ERROR] Cannot find user. UID = " . $uid . "\n";
			exit -1;
		}
	}

	$encoder_str = "";
	if ( $viewers[$cip]['encoder'] ) $encoder_str = "(*)";


	$tmp = $uid . "," . (empty($user['email'])?"-":$user['email']) . $encoder_str . "," . $cip . "," . $client['hostname'] . "," . $client['connections'];

	// Stream statistics: get per stream statistics
	foreach ($client['streams'] as $keycode => $keycode_data ) {
		$loc_id = $keycode_data['locationid'];
		$streamid = $keycode_data['streamid'];
		$loc_name = $location_info[$loc_id][$streamid]['locationname'];
		$stream_name = $location_info[$loc_id][$streamid]['streamname'];
		$tmp .= "," . $stream_name . "," . secs2hms($keycode_data['duration']);
	}

	$tmp .= "\n";

	echo $tmp;

	$msg .= $tmp;

	$number_of_viewers++;
}

/*
	$cip = $key;
	$hostname = $viewers[$cip]['hostname'];

	if ( $cip == $hostname ) {
		$msg .= " " . $cip . "\n";
	} else {
		$msg .= " " . $cip . " (" . $hostname . ")\n";
	}
*/

$msg .= "\nViewers: " . $number_of_viewers . "\n";

// Open log file
$result_file = "log_anal_results.txt";
$fh = fopen($result_file, "w");

if ( fwrite($fh, $msg) === FALSE ) {
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
			a.userid,
			a.id as locationid,
			a.name as locationname,
			b.id as streamid,
			b.name as streamname,
			b.keycode,
			b.contentkeycode
		FROM
			livefeeds as a,
			livefeed_streams as b
		WHERE
			a.channelid = " . $live_channelid . " AND
			a.id = b.livefeedid
		ORDER BY
			a.id,
			b.id
	";

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

function query_user($uid, &$user) {
global $db, $app;

	$query = "
		SELECT
			id,
			nickname,
			email
		FROM
			users
		WHERE
			id = " . $uid;

	try {
		$tmp = $db->Execute($query);
	} catch (exception $err) {
		echo "[ERROR] Cannot query users. SQL query failed.\n\n" . trim($query) . "\n";
		return FALSE;
	}

	// Check if user returned
	if ( $tmp->RecordCount() < 1 ) {
		return FALSE;
	}

	$user = $tmp->fields;

	return TRUE;
}

?>
