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
$live_channelid = 32;

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
while ( !$live_streams->EOF ) {
	$stream = array();
	$stream = $live_streams->fields;

	$locationid = $stream['locationid'];
	if ( empty($location_info[$locationid]) ) $location_info[$locationid] = array();

	$tmp = array(
		'locationid'		=> $stream['locationid'],
		'streamid'			=> $stream['streamid'],
		'streamname'		=> $stream['streamname'],
		'keycode'			=> $stream['keycode'],
		'contentkeycode'	=> $stream['contentkeycode'],
		'locationname'		=> $stream['locationname']
	);
	array_push($location_info[$locationid], $tmp);

	$live_streams->MoveNext();
}

// Start log analyzing

// Get current directory
$directory = realpath('.') . "/";

$log_files = dirList($directory, ".log");
sort($log_files, SORT_STRING);

if ( count($log_files) < 1 ) {
	echo "[ERROR] Cannot find any .log files\n";
	exit;
}

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
#Wowza log example:
#  0: date | 2012-12-14
#  1: time | 09:29:39
#  2: tz | CET
#  3: x-event | play
#  4: x-category | stream
#  5: x-severity | INFO
#  6: x-status | 200
#  7: x-ctx | 148820
#  8: x-comment | - 
#  9: x-vhost | _defaultVHost_
# 10: x-app | vsqlive
# 11: x-appinst | _definst
# 12: x-duration | 0.762
# 13: s-ip | 91.120.59.230
# 14: s-port | 1935
# 15: s-uri | rtmp://stream.videosquare.eu:1935/vsqlive/?sessionid=conforg.videosquare.eu_k0nu829dc8viv2q57n5iskvmiqeqhh9c_4
# 16: c-ip | 89.132.168.224
# 17: c-proto | rtmp
# 18: c-referrer | http://conforg.videosquare.eu/flash/TCPlayer.swf?v=_v20121211
# 19: c-user-agent | WIN 11,5,31,5
# 20: c-client-id | 1010351969
# 21: cs-bytes | 3760
# 22: sc-bytes | 3455
# 23: x-stream-id | 1
# 24: x-spos | 0
# 25: cs-stream-bytes | 0
# 26: sc-stream-bytes | 0
# 27: x-sname | 148820
# 28: x-sname-query | -
# 29: x-file-name | -
# 30: x-file-ext | -
# 31: x-file-size | - 
# 32: x-file-length | -
# 33: x-suri | rtmp://stream.videosquare.eu:1935/vsqlive//148820
# 34: x-suri-stem | rtmp://stream.videosquare.eu:1935/vsqlive//148820
# 35: x-suri-query | -
# 36: cs-uri-stem | rtmp://stream.videosquare.eu:1935/vsqlive/
# 37: cs-uri-query | sessionid=conforg.videosquare.eu_k0nu829dc8viv2q57n5iskvmiqeqhh9c_4
*/

// DEBUG
/*$tmp = preg_split('/\t+/', $oneline);
$cid = "708560470";
//if ( ( trim($tmp[16]) == "89.133.214.122" ) and ( trim($tmp[20]) == $cid ) ) {
if ( ( trim($tmp[16]) == "89.133.214.122" ) ) {
	echo trim($tmp[16]) . " : x-event = " . trim($tmp[3]) . " : status = " . trim($tmp[6]) . " : x-duration = " . trim($tmp[12]) . " : c-client-id = " . trim($tmp[20]) . "\n";
}
*/

		// Math log entries: YYYY-MM-DD HH:MM:SS
		if ( preg_match('/^[\s]*[0-9]{4}-[0-1][0-9]-[0-3][0-9][\s]+[0-2][0-9]:[0-5][0-9]:[0-5][0-9][\s]+[A-Z]+[\s]+(play|destroy|publish)/', $oneline) ) {

			$log_line = preg_split('/\t+/', $oneline);

			$log_feedid = trim($log_line[27]);
			if ( empty($log_feedid) ) continue;

			//10: x-app = match wowza application
			if ( trim($log_line[10]) != $wowza_app ) continue;

			// Find Wowza stream ID in location information array
			foreach ($location_info as $key => $value) {
				$locationid = $key;
				foreach ($location_info[$locationid] as $skey => $svalue) {
					$streamid = $skey;
					// Check if this stream record matches Wowza stream ID
					$tmp = array_search($log_feedid, $location_info[$locationid][$streamid]);
					if ( $tmp !== FALSE ) {
						// We have a match, add this data to overall statistics
						if ( $tmp == "keycode" ) {

							// x-event: play or destroy (calculate duration when destroy occurs)
							$x_event = trim($log_line[3]);
							// Client IP
							$cip = trim($log_line[16]);
							// Client session and parameters
							$csession = trim($log_line[37]);
							// Client ID
							$clientid = trim($log_line[20]);
							// Wowza stream ID
							$keycode = $location_info[$locationid][$streamid]['keycode'];

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

							$duration = trim($log_line[12]);
							// Skip this entry if too short
							if ( $duration < 0 ) continue;

							// User ID: store Vsq user ID and add data. If no user ID is given, then use ID = 0 for storing all client IPs
							if ( empty($viewers[$cip]) ) $viewers[$cip] = array();

							if ( empty($viewers[$cip][$uid]) ) {
								$viewers[$cip][$uid] = array();

								// Host name
								$viewers[$cip][$uid]['hostname'] = gethostbyaddr($cip);

								// Protocol
								$viewers[$cip][$uid]['protocol'] = trim($log_line[17]);

								// Connections
								$viewers[$cip][$uid]['connections'] = 1;

								// Encoder: flag IP/UID if event "publish" occured
								$viewers[$cip][$uid]['encoder'] = 0;
								if ( $x_event == "publish" ) $viewers[$cip][$uid]['encoder'] = 1;

								// Client ID: track a stream between events "play"/"publish" and "destroy"
								$viewers[$cip][$uid]['clients'] = array();
								//$viewers[$cip][$uid]['clients'][$clientid]['uid'] = $uid;
								$viewers[$cip][$uid]['clients'][$clientid]['play'] = FALSE;
								if ( ( $x_event == "play" ) or ( $x_event == "publish" ) ) $viewers[$cip][$uid]['clients'][$clientid]['play'] = TRUE;

								// Streams: log which streams are viewed
								$viewers[$cip][$uid]['streams'] = array();
								$viewers[$cip][$uid]['streams'][$keycode]['locationid'] = $locationid;
								$viewers[$cip][$uid]['streams'][$keycode]['streamid'] = $streamid;
								// Duration: add if event is "destroy"
								$viewers[$cip][$uid]['streams'][$keycode]['duration'] = 0;
								if ( $x_event == "destroy" ) {
									$viewers[$cip][$uid]['streams'][$keycode]['duration'] = $duration;
								}

							} else {

								// Connections
								if ( ( $x_event == "play" ) or ( $x_event == "publish" ) ) $viewers[$cip][$uid]['connections']++;

								// Client ID: track a stream between events "play"/"publish" and "destroy"
								if ( empty($viewers[$cip][$uid]['clients'][$clientid]) ) {
									$viewers[$cip][$uid]['clients'][$clientid]['play'] = FALSE;
									if ( ( $x_event == "play" ) or ( $x_event == "publish" ) ) $viewers[$cip][$uid]['clients'][$clientid]['play'] = TRUE;
								}

								$keycode = $location_info[$locationid][$streamid]['keycode'];
								if ( empty($viewers[$cip][$uid]['streams'][$keycode]) ) {
									$viewers[$cip][$uid]['streams'][$keycode]['duration'] = 0;
									$viewers[$cip][$uid]['streams'][$keycode]['locationid'] = $locationid;
									$viewers[$cip][$uid]['streams'][$keycode]['streamid'] = $streamid;
								}
								if ( $x_event == "destroy" ) {
									if ( empty($viewers[$cip][$uid]['streams'][$keycode]['duration']) ) $viewers[$cip][$uid]['streams'][$keycode]['duration'] = 0;
									// Duration: was there play event? If not, skip duration
									if ( $viewers[$cip][$uid]['clients'][$clientid]['play'] ) {
										$viewers[$cip][$uid]['streams'][$keycode]['duration'] += $duration;
										$viewers[$cip][$uid]['clients'][$clientid]['play'] = FALSE;
									}
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
$msg .= "# Domain: " . $event_info['domain'] . "\n#\n";
$msg .= "# Locations (location / stream name): (*) = encoder\n";

$tmp = "UserID,Username,IP address,Hostname,Connections";

// Columns: build location/stream name order guide array
$columns_num = 0;
$column_guide = array();
foreach ($location_info as $loc_id => $streams ) {
	foreach ($streams as $str_id => $stream ) {
		$tmp .= "," . $location_info[$loc_id][$str_id]['locationname'] . " / " . $location_info[$loc_id][$str_id]['streamname'];
		$tmp_content = "";
		if ( !empty($location_info[$loc_id][$str_id]['contentkeycode']) ) $tmp_content = " / " . $location_info[$loc_id][$str_id]['contentkeycode'];
		$msg .= "#\t" . $location_info[$loc_id][$str_id]['locationname'] . " / " . $location_info[$loc_id][$str_id]['streamname'] . ": " . $location_info[$loc_id][$str_id]['keycode'] . $tmp_content . "\n";
		$column_guide[$loc_id][$str_id] = $columns_num;
		$columns_num++;
	}
}
$tmp .= ",Summary\n";
$msg .= $tmp;

$number_of_viewers = 0;
foreach ($viewers as $cip => $client_ip) {

	foreach ($client_ip as $uid => $user_data) {

		$user = array();
		if ( $uid > 0 ) {
			if ( !query_user($uid, $user) ) {
				echo "[ERROR] Cannot find user. UID = " . $uid . "\n";
				exit -1;
			}
		}

		$encoder_str = "";
		if ( $viewers[$cip][$uid]['encoder'] ) $encoder_str = "(*)";

		$tmp = $uid . "," . (empty($user['email'])?"-":$user['email']) . $encoder_str . "," . $cip . "," . $viewers[$cip][$uid]['hostname'] . "," . $viewers[$cip][$uid]['connections'];

		// Stream statistics: get per stream statistics
		$columns = array();
		$duration_full = 0;
		foreach ($viewers[$cip][$uid]['streams'] as $keycode => $keycode_data ) {
			$loc_id = $keycode_data['locationid'];
			$str_id = $keycode_data['streamid'];
			$num = $column_guide[$loc_id][$str_id];
			$columns[$num] = secs2hms($keycode_data['duration']);
			$duration_full += $keycode_data['duration'];
		}

		// Serialize column value
		for ( $i = 0; $i < $columns_num; $i++ ) {
			if ( !empty($columns[$i]) ) {
				$tmp .= "," . $columns[$i];
			} else {
				$tmp .= ",-";
			}
		}

		$tmp .= "," . secs2hms($duration_full) . "\n";

//		echo $tmp;

		$msg .= $tmp;

		$number_of_viewers++;
	
	} // Users per IP
} // IPs

$msg .= "\nViewers: " . $number_of_viewers . "\n";

echo $msg . "\n";

// Open log file
$result_file = "analytics_" . date("Y-m-d") . ".txt";
$fh = fopen($result_file, "w");

if ( fwrite($fh, $msg) === FALSE ) {
	echo "Cannot write to file (" . $result_file . "\n";
	exit;
}

fclose($fh);

echo "\nLog written to: " . $result_file . "\n";

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
