<?php
// Media conversion job v0 @ 2012/02/??

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
$jconf = $app->config['config_jobs'];
$myjobid = $jconf['jobid_stats_process'];
$debug = Springboard\Debug::getInstance();

// !!!!
$kaka = "";

// Check operating system - exit if Windows
if ( iswindows() ) {
	echo "ERROR: Non-Windows process started on Windows platform\n";
	exit;
}

// Exit if any STOP file appears
if ( is_file( $app->config['datapath'] . 'jobs/' . $myjobid . '.stop' ) or is_file( $app->config['datapath'] . 'jobs/all.stop' ) ) exit;

// Log related init
$debug->log($jconf['log_dir'], $myjobid . ".log", "********************* Job: " . $myjobid . " started *********************", $sendmail = false);

// Already running. Not finished a tough job?
$run_filename = $jconf['temp_dir'] . $myjobid . ".run";
if  ( file_exists($run_filename) ) {
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] I am already running. Not finished a tough job?", $sendmail = true);
	exit;
} else {
	$content = "Running. Started: " . date("Y-m-d H:i:s");
	$err = file_put_contents($run_filename, $content);
	if ( $err === false ) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot write run file " . $run_filename, $sendmail = true);
	}
}

// --- CONFIG ---
$platform_definitions = array(
	//	'string to find'	=> "array index/sql column name"
	'Flash/WIN'		=> "flashwin",		// Examples: Flash/WIN 12,0,0,77
	'Flash/MAC'		=> "flashmac",		// Examples: Flash/MAC 12,0,0,77
	'Flash/Linux'	=> "flashlinux",	// Examples: ?
	'Flash'			=> "flashwin",		// Examples: Flash/Wirecast/FM 1.0 (compatible; FMSc/1.0) | Flash/FMLE/3.0 (compatible; FMSc/1.0) (nem korrekt???)
	'Android'		=> "android",		// Examples: Samsung GT-I9100 stagefright/Beyonce/1.1.9 (Linux;Android 4.1.2)
	'iPhone'		=> "iphone",		// Examples: AppleCoreMedia/1.0.0.11D167 (iPhone; U; CPU OS 7_1 like Mac OS X; en_us)
	'iPad'			=> "ipad"			// Examples: AppleCoreMedia/1.0.0.10B329 (iPad; U; CPU OS 6_1_3 like Mac OS X; hu_hu)
// Egyebek?
// Flash IDE Builder: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_2) AppleWebKit/537.75.14 (KHTML, like Gecko) Version/7.0.3 Safari/537.75.14
);

$stats_config = array(
	0 => array(
		'label'				=> "5min",
		'sqltablename'		=> "statistics_live_5min",
		'interval'			=> 60 * 5,
		'lastprocessedtime'	=> 0
	),
	1 => array(
		'label'				=> "hourly",
		'sqltablename'		=> "statistics_live_hourly",
		'interval'			=> 60 * 60,
		'lastprocessedtime'	=> 0
	),
	2 => array(
		'label'				=> "daily",
		'sqltablename'		=> "statistics_live_daily",
		'interval'			=> 60 * 60 * 24,
		'lastprocessedtime'	=> 0
	)
);

// Wowza application to filter
$wowza_app = $jconf['streaming_live_app'];
if ( $app->config['baseuri'] == "dev.videosquare.eu/" ) $wowza_app = "dev" . $wowza_app;

// --- END OF CONFIG ---

clearstatcache();

// Watchdog
$app->watchdog();

// Establish database connection
$db = db_maintain();

// Empty array for each record initialization
$platforms_null = returnStreamingClientPlatformEmptyArray($platform_definitions);

// Loop through defined statistics (5min, hourly, daily). See config.
for ( $statsidx = 0; $statsidx < count($stats_config); $statsidx++ ) {

	// Load last processed record time
	$status_filename = $jconf['temp_dir'] . $myjobid . "." . $stats_config[$statsidx]['label'] . ".status";

	// Status file: read last processed record time
	if ( file_exists($status_filename) ) {

		// Is readable?
		if ( !is_readable($status_filename) ) {

			$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Stats status file: " . $status_filename . ".", $sendmail = true);

		} else {

			$fh = fopen($status_filename, "r");

			while( !feof($fh) ) {

				// Read one line from descriptor file
				$line = fgets($fh);
				$line = trim($line);

				// Skip empty lines
				if ( empty($line) ) continue;

				$line_split = explode("=", $line);
				$key = explode("_", $line_split[0]);

				$idx = recursive_array_search($key[1], $stats_config);
				$timestamp = strtotime($line_split[1]);
				if ( $timestamp === false ) {
					$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Stats status file: not a datetime value ('". $line . "')", $sendmail = true);
					exit;
				}
				$stats_config[$idx]['lastprocessedtime'] = $timestamp;
				$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Last processed timestamp from status file (" . $stats_config[$idx]['label'] . "): " . $line_split[1], $sendmail = false);
			}

			fclose($fh);
		}

	}

	// Get last processed record to determine last processing time
	$ltime = getLastStatsRecordFrom($stats_config[$statsidx]['sqltablename']);
	if ( $ltime === false ) {
		// Debug information for logging
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Last processed time from db: no record found. Falling back to " . date("Y-m-d H:i:s", 0), $sendmail = false);
		$stats_config[$statsidx]['lastprocessedtime'] = 0;
	} else {
		$ltime = $ltime + $stats_config[$statsidx]['interval'] - 1;
		// Debug information for logging
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Last processed time from db (" . $stats_config[$statsidx]['sqltablename'] . "): " . date("Y-m-d H:i:s", $ltime), $sendmail = false);
		// Is the two last processed time values are equal?
		if ( $stats_config[$statsidx]['lastprocessedtime'] != $ltime ) {
			// Debug information for logging
			$debug->log($jconf['log_dir'], $myjobid . ".log", "[WARN] Last processed time from status file (" . date("Y-m-d H:i:s" , $stats_config[$statsidx]['lastprocessedtime']) . ") and db (" . date("Y-m-d H:i:s", $ltime) . ") are NOT EQUAL! Falling back to db time.", $sendmail = false);
			$stats_config[$statsidx]['lastprocessedtime'] = $ltime;
		}
	}

	$start_interval = $stats_config[$statsidx]['lastprocessedtime'] + 1;

// !!! DEBUG
//$start_interval = strtotime("2014-07-04 14:50:00");

	$records_committed_all = 0;
	$processing_started = time();
	//  Loop through the full time period until. Exit when right end is in future.
	while ( ( $start_interval + $stats_config[$statsidx]['interval'] ) < time() ) {

// !!! DEBUG
//if ( $start_interval > strtotime("2014-07-04 15:00:00") ) break;

		// Set end of interval
		$end_interval = $start_interval + $stats_config[$statsidx]['interval'] - 1;

		// Log processing cycle (5min, hourly, daily)
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Processing statistics: " . $stats_config[$statsidx]['label'], $sendmail = false);

		// Debug information for logging
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Last processed interval end: " . date("Y-m-d H:i:s", $stats_config[$statsidx]['lastprocessedtime']), $sendmail = false);

		// Query active stream records for this interval
		$stats = queryStatsForInterval($start_interval, $end_interval, $wowza_app);
		if ( $stats === false ) {
			// No active records found in database. A gap is supposed.
			$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] No active record(s) found for the next interval: " . date("Y-m-d H:i:s", $start_interval) . " - " . date("Y-m-d H:i:s", $end_interval), $sendmail = false);

			// Next record breaking the inactive period that also occured before the end of the last whole interval
			$now_left = getTimelineGridSeconds(time(), "left", $stats_config[$statsidx]['interval']);
			$next_active_record = getFirstWowzaRecordFromInterval($end_interval, $now_left, $wowza_app);
			if ( $next_active_record === false ) {
				// Nothing to process, cdn_streaming_stats DB is empty from end of interval. Exit processing.

				// Calculate last finished interval
				$start_interval = $now_left - $stats_config[$statsidx]['interval'];
				$end_interval = $now_left - 1;

				// Update last processed record
				$stats_config[$statsidx]['lastprocessedtime'] = $end_interval;

				// Log
				$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Inactive period skipped. No more records to process. Last processed time is " . date("Y-m-d H:i:s", $end_interval) . ". Exiting.", $sendmail = false);

				break;
			}
			// Debug information for logging
			$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Inactive period skipped. Next stream record: " . date("Y-m-d H:i:s", $next_active_record), $sendmail = false);

			// Timeline grid: first left from next active record time
			$next_active_left = getTimelineGridSeconds($next_active_record, "left", $stats_config[$statsidx]['interval']);
//			$start_interval = $next_active_left - $stats_config[$statsidx]['interval'];
			$start_interval = $next_active_left;
			$end_interval = $next_active_left + $stats_config[$statsidx]['interval'] - 1;

			// Query finally selected interval with record(s)
			$stats = queryStatsForInterval($start_interval, $end_interval, $wowza_app);
			if ( $stats === false ) {
echo "KAKA!!\n";
$debug->log($jconf['log_dir'], $myjobid . ".log", "[WARN] Unexpected. No record(s) to process. Interval: " . date("Y-m-d H:i:s", $start_interval) . " - " . date("Y-m-d H:i:s", $end_interval), $sendmail = false);
echo $kaka . "\n";
			// Update last processed interval. Next active record will be found in next round.
$stats_config[$statsidx]['lastprocessedtime'] = $end_interval;
$start_interval = $start_interval + $stats_config[$statsidx]['interval'];
continue;
			}

		}

		// Debug information for logging
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Stats processing for interval: " . date("Y-m-d H:i:s", $start_interval) . " - " . date("Y-m-d H:i:s", $end_interval), $sendmail = false);

		$records_processed = 0;
		$records_committed = 0;
		$stats_f = array();

		while ( !$stats->EOF ) {

			$stat = $stats->fields;
/*echo "DEBUG: Wowza record\n";
var_dump($stat); */

			// Live feed ID

			// Normal client: livefeedid comes from Wowza URL (logged in DB)
			if ( is_numeric($stat['vsqrecordingfeed']) ) {
				$feedid = $stat['vsqrecordingfeed'];
			} else {
				// If null, then get it from livefeed_streams
				$feedid = $stat['livefeedid'];
			}

			$streamid = $stat['streamid'];		// livefeeds_stream.id
			$qualitytag = $stat['qualitytag'];	// SD, HD, etc.
			$streamname = $stat['keycode'];		// Wowza stream name (e.g. 123456)
			$contentstreamname = $stat['contentkeycode'];

			$platform = findStreamingClientPlatform($stat['clientplayer']);

			// Country (geo IP)
			$country = geoip_country_code_by_name($stat['clientip']);
			if ( $country === false ) {
				$country = "notdef";
			}

			// Is content?
			$iscontent = 0;
			if ( $stat['wowzalocalstreamname'] == $stat['contentkeycode'] ) {
				$iscontent = 1;
			} elseif ( $stat['wowzalocalstreamname'] != $stat['keycode'] ) {
				$debug->log($jconf['log_dir'], $myjobid . ".log", "[WARN] No video/content match. Wowza says: " . $stat['wowzalocalstreamname'] . " / db keycode: " . $stat['keycode'] . "(record id: " . $stat['id'] . ")", $sendmail = false);
			}

			//// Statistics records filtered
			// Build array index
			$idx = $streamid . "_" . $iscontent . "_" . $country; 
			// Initialize (if not yet record is open)
			if ( !isset($stats_f[$feedid][$idx]) ) {
				$stats_f[$feedid][$idx] = $platforms_null;
				$stats_f[$feedid][$idx]['timestamp'] = date("Y-m-d H:i:s", $start_interval);
				$stats_f[$feedid][$idx]['iscontent'] = $iscontent;
			}
			// Add (repeat) livefeedid
			if ( !isset($stats_f[$feedid][$idx]['livefeedid']) ) {
				$stats_f[$feedid][$idx]['livefeedid'] = $feedid;
			}
			// Add livefeedstreamid
			if ( !isset($stats_f[$feedid][$idx]['livefeedstreamid']) ) {
				$stats_f[$feedid][$idx]['livefeedstreamid'] = $streamid;
			}
			// Add country
			if ( !isset($stats_f[$feedid][$idx]['country']) ) {
				$stats_f[$feedid][$idx]['country'] = $country;
			}
			// Increase platform counter
			if ( isset($stats_f[$feedid][$idx][$platform]) ) {
				$stats_f[$feedid][$idx][$platform]++;
			}

/*
echo "DEBUG: processed record\n";
var_dump($stats_f);
*/

			$records_processed++;
			$stats->MoveNext();
		} // End of stats while loop

		// Update results to DB

		$records_committed = 0;
		if ( count($stats_f) > 0 ) {

			$records_committed = 0;
//echo "DB:\n";
			foreach ( $stats_f as $feedid => $feed_array ) {
				foreach ( $feed_array as $idx => $stat_record ) {
					insertStatRecord($stat_record, $stats_config[$statsidx]['sqltablename']);
//var_dump($stat_record);
					$records_committed++;
				}
			}

		}

		// Shift start interval
		$start_interval += $stats_config[$statsidx]['interval'];

		// Update last processed record
		$stats_config[$statsidx]['lastprocessedtime'] = $end_interval;

		// Debug information for logging
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Records processed/committed: " . $records_processed . " / " . $records_committed, $sendmail = false);
		$records_committed_all += $records_committed;

		// Watchdog
		$app->watchdog();

	} // End of interval processing while

	// Time of processing
	$processing_time = time() - $processing_started;

	// Log number of committed records to DB
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] " . $stats_config[$statsidx]['label'] . " processing finished in " . secs2hms($processing_time) . ". Number of records inserted to DB: " . $records_committed_all, $sendmail = false);

	// Write last processed record times to disk (for recovery)
	$content = "lastprocessedtime_" . $stats_config[$statsidx]['label'] . "=" . date("Y-m-d H:i:s", $stats_config[$statsidx]['lastprocessedtime']) . "\n";
	$err = file_put_contents($status_filename, $content);
	if ( $err === false ) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot write status file " . $status_filename, $sendmail = false);
	} else {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] Last processed record time written to " . $status_filename, $sendmail = false);
	}

} // End of 5min, hourly, daily cycles

// Close DB connection if open
if ( is_resource($db->_connectionID) ) $db->close();

// Remove run file
unlink($run_filename);

// Watchdog
$app->watchdog();

exit;

// Query active stream records from database in a time interval
function queryStatsForInterval($start_interval, $end_interval, $streaming_server_app) {
global $db, $debug, $myjobid, $app, $jconf, $kaka;

	$start_interval_datetime = date("Y-m-d H:i:s", $start_interval);
	$end_interval_datetime = date("Y-m-d H:i:s", $end_interval);

	$query = "
		SELECT
			css.id,
			css.vsqsessionid,
			css.vsqdomain,
			css.vsqrecordingfeed,
			css.wowzasessionid,
			css.starttime,
			css.endtime,
			css.vsquserid,
			css.httpsessionid,
			css.wowzaappid,
			css.sessiontype,
			css.wowzalocalstreamname,
			css.wowzaremotestreamname,
			css.wowzaclientid,
			css.rtspsessionid,
			css.streamingtype,
			css.serverip,
			css.clientip,
			css.encoder,
			css.url,
			css.referrer,
			css.clientplayer,
			lfs.id AS streamid,
			lfs.livefeedid,
			lfs.name AS qualitytag,
			lfs.keycode,
			lfs.contentkeycode
		FROM
			cdn_streaming_stats AS css,
			livefeed_streams AS lfs
		WHERE
			css.wowzaappid = '" . $streaming_server_app . "'  AND (
			( css.starttime >= '" . $start_interval_datetime . "' AND css.starttime <= '" . $end_interval_datetime . "' ) OR	# START in the interval
			( css.endtime >= '" . $start_interval_datetime . "'   AND css.endtime <= '" . $end_interval_datetime . "' ) OR		# END in the interval
			( css.starttime <= '" . $end_interval_datetime . "' AND css.endtime IS NULL ) OR									# Open record
			( css.starttime < '" . $start_interval_datetime . "' AND css.endtime > '" . $end_interval_datetime . "' ) ) AND 	# Record covering the whole interval
			( ( css.vsqrecordingfeed IS NOT NULL AND css.vsqrecordingfeed = lfs.livefeedid ) OR ( css.vsqrecordingfeed IS NULL AND css.vsqsessionid IS NULL) )
AND	( css.wowzalocalstreamname = lfs.keycode OR css.wowzalocalstreamname = lfs.contentkeycode )
		GROUP BY
			css.vsqsessionid, css.wowzalocalstreamname, css.vsqrecordingfeed";

// Ez a VCR-es izeket kicsin�lja!!!! keycode v�ltozik!
//AND	( css.wowzalocalstreamname = lfs.keycode OR css.wowzalocalstreamname = lfs.contentkeycode )

//echo $query . "\n";
$kaka = $query;
// Record probl�m�k:
// - min�s�gi v�lt�s: nincs �j rekord (Wowza oldalon nem figyel�nk valami eventet?)
// - nem lez�rul� rekordok: Wowza restart?

	try {
		$stats = $db->Execute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = false); // TRUE!!!
		return false;
	}

	// No records
	if ( $stats->RecordCount() < 1 ) return false;

	return $stats;
}

// Get next live statistics record timestamp (to help jumping empty time slots)
// WARNING: does not check for active records
function getFirstWowzaRecordFromInterval($from_timestamp, $to_timestamp, $streaming_server_app) {
global $db, $debug, $myjobid, $app, $jconf;

	if ( empty($from_timestamp) ) $from_timestamp = 0;

	$from_datetime = date("Y-m-d H:i:s", $from_timestamp);
	$to_datetime = date("Y-m-d H:i:s", $to_timestamp);

	$query = "
		SELECT
			css.id,
			css.starttime
		FROM
			cdn_streaming_stats AS css
		WHERE
			css.starttime >= '" . $from_datetime . "' AND
			css.starttime <= '" . $to_datetime . "' AND
			css.wowzaappid = '" . $streaming_server_app . "' AND
			css.wowzalocalstreamname IS NOT NULL AND
			css.wowzalocalstreamname <> ''
		ORDER BY
			css.starttime
		LIMIT 1";

//echo $query . "\n";

	try {
		$stats = $db->getArray($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
		return false;
	}

	// No records in database
	if ( count($stats) < 1 ) return false;

	$starttime = strtotime($stats[0]['starttime']);

	return $starttime;
}

function getLastStatsRecordFrom($db_stats_table) {
global $db, $debug, $myjobid, $app, $jconf;

	$query = "
		SELECT
			id,
			timestamp
		FROM
			" . $db_stats_table . "
		ORDER BY
			timestamp DESC
		LIMIT 1";

//echo $query . "\n";

	try {
		$stats = $db->getArray($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
		return false;
	}

	// No records in database
	if ( count($stats) < 1 ) return false;

	$starttime = strtotime($stats[0]['timestamp']);

	return $starttime;
}

function getTimelineGridSeconds($timestamp, $direction = "left", $timeresolution) {

	$mod = $timestamp % $timeresolution;

	if ( $direction == "left" ) {
		$timestamp_grid = $timestamp - $mod;
	} else {
		$timestamp_grid = $timestamp + ($timeresolution + $mod);
	}

	return $timestamp_grid;
}

function findStreamingClientPlatform($platform_string) {
global $platform_definitions, $debug, $myjobid, $jconf;

	foreach ( $platform_definitions as $key => $value ) {
		if ( stripos($platform_string, $key) !== false ) return $value;
	}

	$debug->log($jconf['log_dir'], $myjobid . ".log", "[WARN] Platform \"" . $platform_string . "\" not found.", $sendmail = false);
	return 'unknown';
}

function returnStreamingClientPlatformEmptyArray($platform_definitions) {

	$platforms_null = array();

	foreach ( $platform_definitions as $key => $value ) {
		$platforms_null[$value] = 0;
	}

	$platforms_null['unknown'] = 0;

	return $platforms_null;
}

function insertStatRecord($stat_record, $db_stats_table) {
global $debug, $app, $jconf, $myjobid;

	$values = array(
		'timestamp'				=> $stat_record['timestamp'],
		'livefeedid'			=> $stat_record['livefeedid'],
		'livefeedstreamid'		=> $stat_record['livefeedstreamid'],
		'iscontent'				=> $stat_record['iscontent'],
		'country'				=> $stat_record['country'],
		'numberofflashwin'		=> $stat_record['flashwin'],
		'numberofflashmac'		=> $stat_record['flashmac'],
		'numberofflashlinux'	=> $stat_record['flashlinux'],
		'numberofandroid'		=> $stat_record['android'],
		'numberofiphone'		=> $stat_record['iphone'],
		'numberofipad'			=> $stat_record['ipad'],
		'numberofunknown'		=> $stat_record['unknown'],
	);

//var_dump($values);

	try {
		$liveStats = $app->bootstrap->getModel($db_stats_table);
		$liveStats->insert($values);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL operation failed." . trim($query), $sendmail = false);	// TRUE!!!!
		return false;
	}

	return true;
}

function recursive_array_search($needle,$haystack) {
    
	foreach( $haystack as $key=>$value ) {
        $current_key = $key;
        if ( $needle === $value OR ( is_array($value) && recursive_array_search($needle,$value) !== false ) ) {
            return $current_key;
        }
    }

    return false;
}

function fixkaka() {
global $db, $debug, $myjobid, $app, $jconf;

	$query = "
		SELECT
			id,
			starttime,
			endtime
		FROM
			cdn_streaming_stats
		WHERE
			endtime IS NULL
		";

//echo $query . "\n";

	try {
		$stats = $db->getArray($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = false);
		return false;
	}

	for ( $i = 0; $i < count($stats); $i++ ) {
		var_dump($stats[$i]);
		$time = strtotime($stats[$i]['starttime']) + 60;
		$dtime = date("Y-m-d H:i:s", $time);

		$query = "
			UPDATE
				cdn_streaming_stats
			SET
				endtime = '" . $dtime . "'
			WHERE
				id = " . $stats[$i]['id'];

	echo $query . "\n";

		try {
			$ss = $db->Execute($query);
		} catch (exception $err) {
			$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = false);
			return false;
		}

	}

echo "num: " . $i . "\n";
	return true;
}

?>
