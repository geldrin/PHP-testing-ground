#!/usr/bin/php
<?php

// Problems: play vs. start stream ID. Workaround: add duration to stream ID what is started
/*
2013-02-26      08:38:00        CET     connect-pending 		session INFO    100     62.165.193.94   		-       _defaultVHost_  vsqlive _definst_       0.01    91.120.59.230   1935    rtmp://stream.videosquare.eu:1935/vsqlive?sessionid=conforg.videosquare.eu_37t4778t3eocjmbvpq96vt57bb7690tp_18&uid=684  62.165.193.94   rtmp    http://conforg.videosquare.eu/flash/TCPlayer.swf?v=_v201302211230       WIN 11,6,602,167        1674790352      3680    3073    -       -       -       -       -       -       -       -       -       -       -       -       -       rtmp://stream.videosquare.eu:1935/vsqlive       sessionid=conforg.videosquare.eu_37t4778t3eocjmbvpq96vt57bb7690tp_18&uid=684
2013-02-26      08:38:00        CET     connect                 session INFO    200     62.165.193.94   		-       _defaultVHost_  vsqlive _definst_       0.011   91.120.59.230   1935    rtmp://stream.videosquare.eu:1935/vsqlive?sessionid=conforg.videosquare.eu_37t4778t3eocjmbvpq96vt57bb7690tp_18&uid=684  62.165.193.94   rtmp    http://conforg.videosquare.eu/flash/TCPlayer.swf?v=_v201302211230       WIN 11,6,602,167        1674790352      3680    3073    -       -       -       -       -       -       -       -       -       -       -       -       -       rtmp://stream.videosquare.eu:1935/vsqlive       sessionid=conforg.videosquare.eu_37t4778t3eocjmbvpq96vt57bb7690tp_18&uid=684
2013-02-26      08:38:00        CET     create                  stream  INFO    200     -                       -       _defaultVHost_  vsqlive _definst_       0.0020  91.120.59.230   1935    rtmp://stream.videosquare.eu:1935/vsqlive?sessionid=conforg.videosquare.eu_37t4778t3eocjmbvpq96vt57bb7690tp_18&uid=684  62.165.193.94   rtmp    http://conforg.videosquare.eu/flash/TCPlayer.swf?v=_v201302211230       WIN 11,6,602,167        1674790352      3752    3413    1       0       0       0       -       -       -       -       -       -       rtmp://stream.videosquare.eu:1935/vsqlive       rtmp://stream.videosquare.eu:1935/vsqlive       -       rtmp://stream.videosquare.eu:1935/vsqlive       sessionid=conforg.videosquare.eu_37t4778t3eocjmbvpq96vt57bb7690tp_18&uid=684
2013-02-26      08:38:01        CET     play                    stream  INFO    200     584368                  -       _defaultVHost_  vsqlive _definst_       0.314   91.120.59.230   1935    rtmp://stream.videosquare.eu:1935/vsqlive?sessionid=conforg.videosquare.eu_37t4778t3eocjmbvpq96vt57bb7690tp_18&uid=684  62.165.193.94   rtmp    http://conforg.videosquare.eu/flash/TCPlayer.swf?v=_v201302211230       WIN 11,6,602,167        1674790352      3802    3455    1       0       0       0       584368  -       -       -       -       -       rtmp://stream.videosquare.eu:1935/vsqlive/584368        rtmp://stream.videosquare.eu:1935/vsqlive/584368        -       rtmp://stream.videosquare.eu:1935/vsqlive       sessionid=conforg.videosquare.eu_37t4778t3eocjmbvpq96vt57bb7690tp_18&uid=684
2013-02-26      13:26:41        CET     stop                    stream  INFO    200     942418                  -       _defaultVHost_  vsqlive _definst_       17321.127       91.120.59.230   1935    rtmp://stream.videosquare.eu:1935/vsqlive?sessionid=conforg.videosquare.eu_37t4778t3eocjmbvpq96vt57bb7690tp_18&uid=684  62.165.193.94   rtmp    http://conforg.videosquare.eu/flash/TCPlayer.swf?v=_v201302211230       WIN 11,6,602,167        1674790352      13243   2306549537      1       17323727        0       2296831904      942418  -       -       -       -       -       rtmp://stream.videosquare.eu:1935/vsqlive/942418        rtmp://stream.videosquare.eu:1935/vsqlive/942418        -       rtmp://stream.videosquare.eu:1935/vsqlive       sessionid=conforg.videosquare.eu_37t4778t3eocjmbvpq96vt57bb7690tp_18&uid=684
2013-02-26      13:26:41        CET     destroy                 stream  INFO    200     942418                  -       _defaultVHost_  vsqlive _definst_       17321.128       91.120.59.230   1935    rtmp://stream.videosquare.eu:1935/vsqlive?sessionid=conforg.videosquare.eu_37t4778t3eocjmbvpq96vt57bb7690tp_18&uid=684  62.165.193.94   rtmp    http://conforg.videosquare.eu/flash/TCPlayer.swf?v=_v201302211230       WIN 11,6,602,167        1674790352      13243   2306549537      1       -       0       2296831904      942418  -       -       -       -       -       rtmp://stream.videosquare.eu:1935/vsqlive/942418        rtmp://stream.videosquare.eu:1935/vsqlive/942418        -       rtmp://stream.videosquare.eu:1935/vsqlive       sessionid=conforg.videosquare.eu_37t4778t3eocjmbvpq96vt57bb7690tp_18&uid=684
2013-02-26      13:26:41        CET     disconnect              session INFO    200     1674790352              -       _defaultVHost_  vsqlive _definst_       17321.183       91.120.59.230   1935    rtmp://stream.videosquare.eu:1935/vsqlive?sessionid=conforg.videosquare.eu_37t4778t3eocjmbvpq96vt57bb7690tp_18&uid=684  62.165.193.94   rtmp    http://conforg.videosquare.eu/flash/TCPlayer.swf?v=_v201302211230       WIN 11,6,602,167        1674790352      13243   2306549537      -       -       -       -       -       -       -       -       -       -       -       -       -       rtmp://stream.videosquare.eu:1935/vsqlive       sessionid=conforg.videosquare.eu_37t4778t3eocjmbvpq96vt57bb7690tp_18&uid=684
*/

/*
On demand reports:

1. Query recordings in given channel, build helper array from recording IDs
2. 

*/

define('BASE_PATH',	realpath( __DIR__ . '/../../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');
include_once( BASE_PATH . 'modules/Jobs/job_utils_base.php' );

set_time_limit(0);

date_default_timezone_set("Europe/Budapest");

echo "Wowza log analizer v0.3 - STARTING...\n";

//// User settings
// Channel ID: calculate statistics for this channel (live or on demand)
$channelid = 50;
// Is stats for live or on demand?
$islivestats = TRUE;
// Ondemand stats analyze start and end dates
$ondemand_startdate = "2013-02-28";
$ondemand_enddate = "2013-02-28";
// Analyze per connection: TRUE = track all connections | FALSE = give a summary only
$analyze_perconnection = TRUE;
// Minimal duration to include a connection (seconds)
$min_duration = 3;

// DEBUG: set IP and/or client ID to filter for the specific client
$debug_client = array(
	'do'		=> FALSE,
	'ip'		=> "",
	'clientid'	=> "",
	'streamid'	=> "209/209/209_video_lq.mp4"
);

// **********************************

// Check input data

if ( !$islivestats and ( ( !check_date_validity($ondemand_startdate) ) or ( !check_date_validity($ondemand_enddate) ) ) ) {
	echo "ERROR: invalid start/end dates " . $ondemand_startdate . "/" . $ondemand_enddate . "\n";
	exit -1;
}

// Init
$app = new Springboard\Application\Cli(BASE_PATH, PRODUCTION);

// Load jobs configuration file
$app->loadConfig('modules/Jobs/config_jobs.php');
$jconf = $app->config['config_jobs'];

// Wowza specific init
$wowza_log_dir = $jconf['wowza_log_dir'];
// Wowza app: vsq or devvsq for on demand, vsqlive or devvsqlive for live analysis
$isdev = FALSE;
$wowza_app = "vsq";
if ( $app->config['baseuri'] != "videosquare.eu/" ) {
	$isdev = TRUE;
	$wowza_app = "devvsq";
}
if ( $islivestats ) $wowza_app .= "live";

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
		a.id = " . $channelid . " AND
		b.id = a.organizationid";

try {
	$event = $db->Execute($query);
} catch (exception $err) {
	echo "[ERROR] Cannot query live channel. SQL query failed.\n\n" . trim($query) . "\n";
	exit -1;
}

// Check if one record found
if ( $event->RecordCount() < 1 ) {
	echo "[ERROR] Cannot find live channel. ID = " . $channelid . "\n";
	exit -1;
}

// Event: get start and end dates. On demand: user config. Live: channel start and end dates.
$event_info = array();
$event_info = $event->fields;
if ( $islivestats ) {
	$tmp = explode(" ", $event_info['starttimestamp'], 2);
	$event_startdate['date'] = trim($tmp[0]);
	$event_startdate['timestamp'] = strtotime($event_startdate['date']);
	$tmp = explode(" ", $event_info['endtimestamp'], 2);
	$event_enddate['date'] = trim($tmp[0]);
	$event_enddate['timestamp'] = strtotime($event_enddate['date']);
} else {
	$event_startdate['date'] = $ondemand_startdate;
	$event_startdate['timestamp'] = strtotime($event_startdate['date']);
	$event_enddate['date'] = $ondemand_enddate;
	$event_enddate['timestamp'] = strtotime($event_enddate['date']);
}

//var_dump($event_startdate);
//var_dump($event_enddate);
//var_dump($event_info);

// Log files: prepare the list of log files to be checked (one Wowza log file per day)
$log_files = array();
$sec_oneday = 3600 * 24;

for ( $timestamp = $event_startdate['timestamp']; $timestamp <= $event_enddate['timestamp']; $timestamp += $sec_oneday ) {

	$log_file = $wowza_log_dir . "access.log." . date("Y-m-d", $timestamp);
	if ( file_exists($log_file) ) {
		array_push($log_files, $log_file);
	} else {
		echo "ERROR: Wowza log file " . $log_file . " does not exist.\n";
	}
}

//var_dump($log_files);

if ( count($log_files) < 1 ) {
	echo "[ERROR] Cannot find any related .log files\n";
	exit -1;
}

// Streams: Live: query locations and streams for live channels. On demand: recording list.
if ( $islivestats ) {
	$live_streams = array();
	if ( !query_livefeeds($event_info['id'], $live_streams) ) {
		echo "[ERROR] Cannot find live event ID = " . $event_info['id'] .".\n";
		exit -1;
	}
} else {
	$recordings = array();
	if ( !query_recordings($event_info['id'], $recordings) ) {
		echo "[ERROR] Cannot find recordings for channel ID = " . $event_info['id'] .".\n";
		exit -1;
	}
}

// Live feeds: runs through all live feeds
if ( $islivestats ) {
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
			'locationname'		=> $stream['locationname'],
			'publishedfirst'	=> ""							// First time during that day the stream was published
		);
		array_push($location_info[$locationid], $tmp);

		$live_streams->MoveNext();
	}
} else {
	$recording_info = array();
	while ( !$recordings->EOF ) {

		$rec = array();
		$rec = $recordings->fields;

		// Recording path relative to media directory
		$rec_path = ( $rec['id'] % 1000 ) . "/" . $rec['id'] . "/" . $rec['id'] . "_";

		// Stream IDs: build array from selected surrogates
		$streamids = array();
		array_push($streamids, $rec_path . "video_lq.mp4");
		array_push($streamids, $rec_path . "video_hq.mp4");
		array_push($streamids, $rec_path . "mobile_hq.mp4");
		array_push($streamids, $rec_path . "mobile_hq.mp4");

		$tmp = array(
			'recordingid'		=> $rec['id'],
			'title'				=> $rec['title'],
			'recordedtime'		=> $rec['recordedtimestamp'],
			'streamids'			=> $streamids,
		);
		array_push($recording_info, $tmp);

		$recordings->MoveNext();
	}
}

//var_dump($location_info);
//var_dump($recording_info);

// Start log analyzing
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

		// DEBUG: debug client by IP or client ID
		$tmp = preg_split('/\t+/', $oneline);
		if ( $debug_client['do'] ) {

			// Skip log line if not matching
			if ( ( trim($tmp[16]) != $debug_client['ip'] ) and ( trim($tmp[20]) != $debug_client['clientid'] ) and ( trim($tmp[27]) != $debug_client['streamid'] ) ) continue;

			// Date and time
			$date = trim($tmp[0]) . " " . trim($tmp[1]);

			if ( trim($tmp[16]) == $debug_client['ip'] ) {
				echo trim($tmp[16]) . "," . $date . ",x-event = " . trim($tmp[3]) . ",status = " . trim($tmp[6]) . ",x-duration = " . trim($tmp[12]) . ",x-spos = " . round((trim($tmp[24]) + 0) / 1000, 3) . ",c-client-id = " . trim($tmp[20]) . ",feedid = " . trim($tmp[27]) . "\n";
			}

			if ( trim($tmp[20]) == $debug_client['clientid'] ) {
				echo trim($tmp[20]) . "," . $date . ",x-event = " . trim($tmp[3]) . ",status = " . trim($tmp[6]) . ",x-duration = " . trim($tmp[12]) . ",x-spos = " . round((trim($tmp[24]) + 0) / 1000, 3) . ",c-client-id = " . trim($tmp[20]) . ",feedid = " . trim($tmp[27]) . "\n";
			}

			if ( trim($tmp[27]) == $debug_client['streamid'] ) {
				echo trim($tmp[27]) . "," . $date . ",x-event = " . trim($tmp[3]) . ",status = " . trim($tmp[6]) . ",x-duration = " . trim($tmp[12]) . ",x-spos = " . round((trim($tmp[24]) + 0) / 1000, 3) . ",c-client-id = " . trim($tmp[20]) . ",feedid = " . trim($tmp[27]) . "\n";
			}
		}

		// Math log entries: YYYY-MM-DD HH:MM:SS
		if ( preg_match('/^[\s]*[0-9]{4}-[0-1][0-9]-[0-3][0-9][\s]+[0-2][0-9]:[0-5][0-9]:[0-5][0-9][\s]+[A-Z]+[\s]+(play|publish|stop|unpause|seek|destroy)/', $oneline) ) {

			$log_line = preg_split('/\t+/', $oneline);

			$log_feedid = trim($log_line[27]);
			if ( empty($log_feedid) ) continue;

			//10: x-app = match wowza application
			if ( trim($log_line[10]) != $wowza_app ) continue;

			// x-event: play/publish or destroy (calculate duration when destroy occurs)
			$x_event = trim($log_line[3]);

//if ( $x_event == "seek" ) var_dump($log_line);
//# 24: x-spos | 0 in msecs

			if ( $islivestats ) {
				// Find Wowza stream ID in location information array
				$retval = location_info_search_keycode($location_info, $log_feedid);
				// Not found: not relevant stream information was found
				if ( $retval === FALSE ) continue;
			} else {
				// Find Wowza stream ID in recording information array
				$retval = recording_info_search_streamid($recording_info, $log_feedid);
				// Not found: not relevant stream information was found
				if ( $retval === FALSE ) continue;
				// !!!!!!!!!!!!!!!!!!!!
				continue;
			}

			// Stream ID match: add this data to overall statistics
			$locationid = $retval['locationid'];
			$streamid = $retval['streamid'];

			// Client IP
			$cip = trim($log_line[16]);
			// Client session and parameters
			$csession = trim($log_line[37]);
			// Client ID
			$clientid = trim($log_line[20]);
			// Wowza stream ID
			$keycode = $location_info[$locationid][$streamid]['keycode'];
			// Date and time
			$date = trim($log_line[0]) . " " . trim($log_line[1]);
			$date_timestamp = strtotime($date);

			// Date of first publish
			if ( $x_event == "publish" and empty($location_info[$locationid][$streamid]['publishedfirst']) ) {
				$location_info[$locationid][$streamid]['publishedfirst'] = $date_timestamp;
			}

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

			// Duration
			$duration = trim($log_line[12]);
			// Skip this entry if too short
			if ( $duration < 0 ) continue;

			// User ID: store Vsq user ID and add data. If no user ID is given, then use ID = 0 for storing all client IPs
			if ( empty($viewers[$cip]) ) $viewers[$cip] = array();


			// No entry yet: add user ID under the specific IP
			if ( empty($viewers[$cip][$uid]) ) {
				$viewers[$cip][$uid] = array();

				// Host name
				$viewers[$cip][$uid]['hostname'] = gethostbyaddr($cip);
				// Connections
				$viewers[$cip][$uid]['connections'] = 1;
				// User agent
				$viewers[$cip][$uid]['user_agent'] = trim($log_line[19]);
				// Protocol
				$viewers[$cip][$uid]['protocol'] = trim($log_line[17]);

				// Encoder: flag IP/UID if event "publish" occured
				$viewers[$cip][$uid]['encoder'] = 0;
				if ( $x_event == "publish" ) $viewers[$cip][$uid]['encoder'] = 1;

				// Client ID: track a stream between events "play"/"publish" and "destroy"
				$viewers[$cip][$uid]['clients'] = array();
			}

			// New stream: nothing recorded yet
			if ( empty($viewers[$cip][$uid]['streams']) ) $viewers[$cip][$uid]['streams'] = array();

			if ( empty($viewers[$cip][$uid]['streams'][$keycode]) ) {
				$viewers[$cip][$uid]['streams'][$keycode] = array();
				$viewers[$cip][$uid]['streams'][$keycode]['duration'] = 0;
				$viewers[$cip][$uid]['streams'][$keycode]['locationid'] = $locationid;
				$viewers[$cip][$uid]['streams'][$keycode]['streamid'] = $streamid;
			}

			// New client ID: nothing recorded yet, init data structures
			if ( empty($viewers[$cip][$uid]['clients'][$clientid]) ) {
				$viewers[$cip][$uid]['clients'][$clientid] = array();
				$viewers[$cip][$uid]['clients'][$clientid]['play'] = FALSE;
				$viewers[$cip][$uid]['clients'][$clientid]['stop'] = FALSE;
				$viewers[$cip][$uid]['clients'][$clientid]['destroy'] = FALSE;
				$viewers[$cip][$uid]['clients'][$clientid]['duration'] = 0;
				$viewers[$cip][$uid]['clients'][$clientid]['playnum'] = 0;		// Playback session counter (play-stop)
				$viewers[$cip][$uid]['clients'][$clientid]['prevduration'] = 0;	// Previous command duration
				$viewers[$cip][$uid]['clients'][$clientid]['playsessions'] = array();

			}

			// PLAY: if play, then record start time and start track this session
			if ( ( $x_event == "play" ) or ( $x_event == "publish" ) ) {

				// PLAY -> PLAY: nothing happened (only warning)
				if ( $viewers[$cip][$uid]['clients'][$clientid]['play'] ) {
					echo "WARNING: PLAY -> PLAY? (clientid = " . $clientid . ")\n";
					continue;
				}

				if ( $debug_client['do'] ) {
					if ( ( !$viewers[$cip][$uid]['clients'][$clientid]['stop'] ) and ( !$viewers[$cip][$uid]['clients'][$clientid]['destroy'] ) ) echo $clientid . " PLAY started, dur = " . $duration . "\n";
					if ( $viewers[$cip][$uid]['clients'][$clientid]['stop'] ) echo $clientid . " STOP -> PLAY, dur = " . $duration . "\n";
				}

				// Count connections
				$viewers[$cip][$uid]['connections']++;

				// Update location and stream ID
				$viewers[$cip][$uid]['clients'][$clientid]['locationid'] = $locationid;
				$viewers[$cip][$uid]['clients'][$clientid]['streamid'] = $streamid;

				// Set PLAY status, reset the rest
				$viewers[$cip][$uid]['clients'][$clientid]['play'] = TRUE;
				$viewers[$cip][$uid]['clients'][$clientid]['stop'] = FALSE;
				$viewers[$cip][$uid]['clients'][$clientid]['destroy'] = FALSE;

				// Start date and time
				$playnum = $viewers[$cip][$uid]['clients'][$clientid]['playnum'];
				$viewers[$cip][$uid]['clients'][$clientid]['playsessions'][$playnum]['startedas'] = $keycode;
				$viewers[$cip][$uid]['clients'][$clientid]['playsessions'][$playnum]['started_timestamp'] = $date_timestamp;
				$viewers[$cip][$uid]['clients'][$clientid]['playsessions'][$playnum]['started_datetime'] = $date;

				$viewers[$cip][$uid]['clients'][$clientid]['prevduration'] = $duration;		// Record PLAY command relative time
			}

			// STOP: 
			if ( $x_event == "stop" ) {

				// STOP -> STOP: ?
				if ( $viewers[$cip][$uid]['clients'][$clientid]['stop'] ) {
					$viewers[$cip][$uid]['clients'][$clientid]['prevduration'] = $duration;
					continue;
				}

				// DESTROY -> STOP: ?
				if ( $viewers[$cip][$uid]['clients'][$clientid]['destroy'] ) {
					// PLAY -> STOP: set STOP status, reset PLAY and DESTROY
					$viewers[$cip][$uid]['clients'][$clientid]['play'] = FALSE;
					$viewers[$cip][$uid]['clients'][$clientid]['stop'] = TRUE;
					$viewers[$cip][$uid]['clients'][$clientid]['destroy'] = FALSE;

					$viewers[$cip][$uid]['clients'][$clientid]['prevduration'] = $duration;
					continue;
				}

				if ( $debug_client['do'] ) {
					if ( $viewers[$cip][$uid]['clients'][$clientid]['play'] ) echo $clientid . " PLAY -> STOP, dur = " . $duration . "\n";
					if ( $viewers[$cip][$uid]['clients'][$clientid]['destroy'] ) echo $clientid . " DESTROY -> STOP, dur = " . $duration . "\n";
				}

				// PLAY -> STOP: set STOP status, reset PLAY and DESTROY
				$viewers[$cip][$uid]['clients'][$clientid]['play'] = FALSE;
				$viewers[$cip][$uid]['clients'][$clientid]['stop'] = TRUE;
				$viewers[$cip][$uid]['clients'][$clientid]['destroy'] = FALSE;

				// End date and time
				$playnum = $viewers[$cip][$uid]['clients'][$clientid]['playnum'];
				$viewers[$cip][$uid]['clients'][$clientid]['playsessions'][$playnum]['ended_timestamp'] = $date_timestamp;
				$viewers[$cip][$uid]['clients'][$clientid]['playsessions'][$playnum]['ended_datetime'] = $date;

				$viewers[$cip][$uid]['clients'][$clientid]['playnum']++;

				// Duration: calculate duration between PLAY and STOP
				$prevduration = $viewers[$cip][$uid]['clients'][$clientid]['prevduration'];
				$realduration = $duration - $prevduration;
				$viewers[$cip][$uid]['clients'][$clientid]['prevduration'] = $duration;		// Record STOP command relative time

				// Duration: add to stream summary
				$startedas = $viewers[$cip][$uid]['clients'][$clientid]['playsessions'][$playnum]['startedas'];	// Workaround
				$viewers[$cip][$uid]['streams'][$startedas]['duration'] += $realduration;
				$viewers[$cip][$uid]['clients'][$clientid]['duration'] += $realduration;

				if ( $debug_client['do'] ) echo "Real duration counted: " . $realduration . "\n";

			} // STOP

			// DESTROY: finishing connection
			if ( $x_event == "destroy" ) {

				// DESTROY -> DESTROY: ?
				if ( $viewers[$cip][$uid]['clients'][$clientid]['destroy'] ) {
					echo "ERROR: DESTROY -> DESTROY (clientid = " . $clientid . ")\n";
					exit -1;
				}

				if ( $debug_client['do'] ) {
					if ( $viewers[$cip][$uid]['clients'][$clientid]['play'] ) echo $clientid . " PLAY -> DESTROY, dur = " . $duration . "\n";
					if ( $viewers[$cip][$uid]['clients'][$clientid]['stop'] ) echo $clientid . " STOP -> DESTROY, dur = " . $duration . "\n";
				}

				// PLAY -> DESTROY: end of playback, no STOP issued finishing connection
				if ( $viewers[$cip][$uid]['clients'][$clientid]['play'] ) {

					// End date and time
					$playnum = $viewers[$cip][$uid]['clients'][$clientid]['playnum'];
					$viewers[$cip][$uid]['clients'][$clientid]['playsessions'][$playnum]['ended_timestamp'] = $date_timestamp;
					$viewers[$cip][$uid]['clients'][$clientid]['playsessions'][$playnum]['ended_datetime'] = $date;

					$viewers[$cip][$uid]['clients'][$clientid]['playnum']++;

					// Duration: calculate duration between PLAY and DESTROY
					$prevduration = $viewers[$cip][$uid]['clients'][$clientid]['prevduration'];
					$realduration = $duration - $prevduration;
					$viewers[$cip][$uid]['clients'][$clientid]['prevduration'] = $duration;		// Record STOP command relative time

					// Duration: add to stream summary
					$startedas = $viewers[$cip][$uid]['clients'][$clientid]['playsessions'][$playnum]['startedas'];	// Workaround
					$viewers[$cip][$uid]['streams'][$startedas]['duration'] += $realduration;
					$viewers[$cip][$uid]['clients'][$clientid]['duration'] += $realduration;

					if ( $debug_client['do'] ) echo "Real duration counted: " . $realduration . "\n";
				}

				// STOP -> DESTROY: end of a stopped connection - DO NOTHING

				// Set DESTROY status, reset PLAY and STOP
				$viewers[$cip][$uid]['clients'][$clientid]['play'] = FALSE;
				$viewers[$cip][$uid]['clients'][$clientid]['stop'] = FALSE;
				$viewers[$cip][$uid]['clients'][$clientid]['destroy'] = TRUE;

			} // DESTROY

		} // Log line section

	}

	fclose($fh);
}

//exit;

//var_dump($viewers);

//var_dump($location_info);

// Debug: print relevant viewer collected data
if ( $debug_client['do'] ) {
	$ip = $debug_client['ip'];
	echo "DEBUG: printing data for requested client\n";
	var_dump($viewers);
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

$tmp = "UserID,Order,Username,IP address,Hostname,Connections";

// Columns: build location/stream name order guide array
$columns_num = 0;
$column_guide = array();
foreach ($location_info as $loc_id => $streams ) {
	foreach ($streams as $str_id => $stream ) {
		$tmp .= "," . $location_info[$loc_id][$str_id]['locationname'] . " / " . $location_info[$loc_id][$str_id]['streamname'];
		$tmp_content = "";
		$published_first = "-";
		if ( !empty($location_info[$loc_id][$str_id]['publishedfirst']) ) $published_first = date("Y-m-d H:i:s", $location_info[$loc_id][$str_id]['publishedfirst']);
		if ( !empty($location_info[$loc_id][$str_id]['contentkeycode']) ) $tmp_content = " / " . $location_info[$loc_id][$str_id]['contentkeycode'];
		$msg .= "#\t" . $location_info[$loc_id][$str_id]['locationname'] . " / " . $location_info[$loc_id][$str_id]['streamname'] . ": " . $location_info[$loc_id][$str_id]['keycode'] . $tmp_content . " (started: " . $published_first . ")\n";

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

		// Columns: UserID, Order, Username, IP address, Hostname, Connections, Stream1, Stream2, ..., Summary
		$tmp = $uid . ",0," . (empty($user['email'])?"-":$user['email']) . $encoder_str . "," . $cip . "," . $viewers[$cip][$uid]['hostname'] . "," . $viewers[$cip][$uid]['connections'];

		// Stream statistics: get per stream statistics
		$columns = array();
		$duration_full = 0;
		foreach ($viewers[$cip][$uid]['streams'] as $keycode => $keycode_data ) {
			$loc_id = $keycode_data['locationid'];
			$str_id = $keycode_data['streamid'];
			$num = $column_guide[$loc_id][$str_id];
			if ( $keycode_data['duration'] > 1 ) $columns[$num] = secs2hms($keycode_data['duration']);
			$duration_full += $keycode_data['duration'];
		}

		if ( $duration_full < 1 ) continue;

		// Serialize column value
		for ( $i = 0; $i < $columns_num; $i++ ) {
			if ( !empty($columns[$i]) ) {
				$tmp .= "," . $columns[$i];
			} else {
				$tmp .= ",-";
			}
		}

		$tmp .= "," . secs2hms($duration_full) . "\n";

		$msg .= $tmp;

		// CONNECTION: per connection analyzation (if needed)
		if ( $analyze_perconnection ) {

			foreach ($viewers[$cip][$uid]['clients'] as $clientid => $client_data ) {

				if ( empty($client_data['playsessions']) ) continue;

				foreach ($client_data['playsessions'] as $psessionid => $playsession) {

					$duration = $playsession['ended_timestamp'] - $playsession['started_timestamp'];
					if ( $duration < $min_duration ) continue;

					$started_time = date("H:i:s", $playsession['started_timestamp']);
					$ended_time = date("H:i:s", $playsession['ended_timestamp']);
					$loc_id = $client_data['locationid'];
					$str_id = $client_data['streamid'];
					$num_column = $column_guide[$loc_id][$str_id];

					$tmp = $uid . ",1,," . $cip;
					for ( $q = 0; $q < ( 5 + $num_column - 2 ); $q++ ) $tmp .= ",";
					$tmp .= $started_time . "-" . $ended_time . "\n";

					$msg .= $tmp;
				}

			}
		}

		$number_of_viewers++;
	
	} // Users per IP
} // IPs

$msg .= "\nViewers: " . $number_of_viewers . "\n";

echo $msg . "\n";

// Open log file
$result_file = "vsq_stats_ch_" . $channelid . "_" . date("Y-m-d", $event_startdate['timestamp']) . ".txt";
$fh = fopen($result_file, "w");

if ( fwrite($fh, $msg) === FALSE ) {
	echo "Cannot write to file (" . $result_file . "\n";
	exit;
}

fclose($fh);

echo "\nLog written to: " . $result_file . "\n";

exit;

function query_livefeeds($channelid, &$live_streams) {
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
			a.channelid = " . $channelid . " AND
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

function query_recordings($channelid, &$recordings) {
global $db, $app;

	$query = "
		SELECT
			a.id,
			a.userid,
			a.title,
			a.recordedtimestamp
		FROM
			recordings as a,
			channels_recordings as b
		WHERE
			b.channelid = " . $channelid . " AND
			a.id = b.recordingid
		ORDER BY
			a.id
	";

	try {
		$recordings = $db->Execute($query);
	} catch (exception $err) {
		echo "[ERROR] Cannot query recordings for channel. SQL query failed.\n\n" . trim($query) . "\n";
		return FALSE;
	}

	// Check if pending job exsits
	if ( $recordings->RecordCount() < 1 ) {
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

function location_info_search_keycode($location_info, $keycode) {

	$retval = array();

	foreach ($location_info as $key => $value) {
		$locationid = $key;
		foreach ($location_info[$locationid] as $skey => $svalue) {
			$streamid = $skey;
			if ( $location_info[$locationid][$streamid]['keycode'] == $keycode ) {
				$retval['locationid'] = $locationid;
				$retval['streamid'] = $streamid;
				return $retval;
			}
		}
	}

	return FALSE;
}

// Search recording_info array for recording ID based on stream ID from Wowza log
function recording_info_search_streamid($recording_info, $streamid) {

	$retval = array();

	foreach ($recording_info as $key => $value) {
		$recordingidx = $key;
		foreach ($recording_info[$recordingidx]['streamids'] as $skey => $svalue) {
			$streamidx = $skey;
			if ( $recording_info[$recordingidx]['streamids'][$streamidx] == $streamid ) {
				$retval['recordingidx'] = $recordingidx;
				$retval['streamidx'] = $streamidx;
				return $retval;
			}
		}
	}

	return FALSE;
}

function check_date_validity($date) {

	$tmp = explode(" ", $date, 2);
	if ( ( count($tmp) < 1 ) or ( count($tmp) > 2 ) ) return FALSE;

	$tmp2 = explode("-", $tmp[0], 3);
	if ( count($tmp2) != 3 ) return FALSE;

	$err = checkdate($tmp2[1], $tmp2[2], $tmp2[0]);

	return $err;
}

?>
