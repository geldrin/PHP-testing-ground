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

// Check operating system - exit if Windows
if ( iswindows() ) {
	echo "ERROR: Non-Windows process started on Windows platform\n";
	exit;
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

// Start an infinite loop - exit if any STOP file appears
if ( is_file( $app->config['datapath'] . 'jobs/' . $myjobid . '.stop' ) or is_file( $app->config['datapath'] . 'jobs/all.stop' ) ) exit;

// Log related init
$debug = Springboard\Debug::getInstance();
$debug->log($jconf['log_dir'], $myjobid . ".log", "Job: " . $myjobid . " started", $sendmail = false);

clearstatcache();

// Watchdog
$app->watchdog();

// Establish database connection
$db = db_maintain();

// Empty array for each record initialization
$platforms_null = returnStreamingClientPlatformEmptyArray($platform_definitions);

// Loop through defined statistics (5min, hourly, daily). See config.
for ( $statsidx = 0; $statsidx < count($stats_config); $statsidx++ ) {

	$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Processing statistics: " . $stats_config[$statsidx]['label'], $sendmail = false);

	// Load last processed record time
	$status_filename = $jconf['temp_dir'] . $myjobid . "." . $stats_config[$statsidx]['label'] . ".status";

	// Status file: read last processed record time
	if ( file_exists($status_filename) ) {

		// Is readable?
		if ( !is_readable($status_filename) ) {

			$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Stats status file: " . $status_filename . ".", $sendmail = false); // TRUE!!!

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
					$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Stats status file: not a datetime value ('". $line . "')", $sendmail = false); // TRUE!!!
					exit;
				}
				$stats_config[$idx]['lastprocessedtime'] = $timestamp;
				$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Last processed timestamp from status file (" . $stats_config[$idx]['label'] . "): " . $line_split[1], $sendmail = false); // TRUE!!!
			}

			fclose($fh);
		}

	}

	// Get last processed record to determine latest processing time
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

	//$stats_config[$statsidx]['lastprocessedtime'] = 0;

	$start_interval = $stats_config[$statsidx]['lastprocessedtime'] + 1;

	//$tmp_round = 0;
	$records_committed_all = 0;
	$processing_started = time();
	while ( ( $start_interval + $stats_config[$statsidx]['interval'] ) < time() ) {

	/*$tmp_round++;
	if ($tmp_round > 3) break;
	*/

		// Debug information for logging
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Processing from time = " . date("Y-m-d H:i:s", $stats_config[$statsidx]['lastprocessedtime']), $sendmail = false);

		// Next record to process after last processed record
		$start_interval = getFirstWowzaRecordFrom($stats_config[$statsidx]['lastprocessedtime'] + 1, $wowza_app);
		// Nothing to process, cdn_streaming_stats DB is empty after $last_processed_timestamp
		if ( $start_interval === false ) {
			$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] No record to process. Exiting.", $sendmail = false);
			break;
		}
		// Debug information for logging
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] First record after last processed time: " . date("Y-m-d H:i:s", $start_interval), $sendmail = false);

		// Align to timeline grid (to left/past)
		$start_interval = getTimelineGridSeconds($start_interval, "left", $stats_config[$statsidx]['interval']);
		// End of interval to process
		$end_interval = $start_interval + $stats_config[$statsidx]['interval'] - 1;

		// Debug information for logging
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Stats processing for interval: " . date("Y-m-d H:i:s", $start_interval) . " - " . date("Y-m-d H:i:s", $end_interval), $sendmail = false);

		// Query records between start and end intervals
		$stats = queryStatsForInterval($start_interval, $end_interval, $wowza_app);
		if ( $stats === false ) {
			// Shift start interval
			$start_interval += $stats_config[$statsidx]['interval'];
			// Update last processed record
			$stats_config[$statsidx]['lastprocessedtime'] = $end_interval;
			// Absolutely unexpected, but we go on when occurs. We send a notification for admins. SQL query needs to be adjusted?
			$debug->log($jconf['log_dir'], $myjobid . ".log", "[WARN] No records found for interval: " . date("Y-m-d H:i:s", $start_interval) . " - " . date("Y-m-d H:i:s", $end_interval), $sendmail = true);
			continue;
		}

		$stats_f = array();

		$records_processed = 0;
		while ( !$stats->EOF ) {

			$stat = $stats->fields;

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
				$debug->log($jconf['log_dir'], $myjobid . ".log", "[WARN] No video/content match.", $sendmail = true);
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

			$records_processed++;
			$stats->MoveNext();
		}

		// Update results to DB
		$records_committed = 0;
		if ( count($stats_f) > 0 ) {

			$records_committed = 0;
			foreach ( $stats_f as $feedid => $feed_array ) {
				foreach ( $feed_array as $idx => $stat_record ) {
					insertStatRecord($stat_record, $stats_config[$statsidx]['sqltablename']);
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

		if ( ( $start_interval + $stats_config[$statsidx]['interval'] ) >= time() ) {
			$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] now() time reached for " . $stats_config[$statsidx]['label'] . ". Stopping.", $sendmail = false);
		}

		// Watchdog
		$app->watchdog();
	}

	// Time of processing
	$processing_time = time() - $processing_started;

	// Log number of committed records to DB
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] " . $stats_config[$statsidx]['label'] . " processing finished in " . secs2hms($processing_time) . ". Number of records inserted to DB: " . $records_committed_all, $sendmail = false);

	// Write last processed record times to disk (for recovery)
	$content = "lastprocessedtime_" . $stats_config[$statsidx]['label'] . "=" . date("Y-m-d H:i:s", $stats_config[$statsidx]['lastprocessedtime']) . "\n";
	$err = file_put_contents($status_filename, $content);
	if ( $err === false ) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot write staus file " . $status_filename, $sendmail = false);
	} else {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] Last processed record time written to " . $status_filename, $sendmail = false);
	}

}

// Close DB connection if open
if ( is_resource($db->_connectionID) ) $db->close();

// Watchdog
$app->watchdog();

exit;

function queryStatsForInterval($start_interval, $end_interval, $streaming_server_app) {
global $db, $debug, $myjobid, $app, $jconf;

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
			( css.starttime >= '" . $start_interval_datetime . "' AND css.starttime <= '" . $end_interval_datetime . "' ) OR
			( css.endtime >= '" . $start_interval_datetime . "'   AND css.endtime <= '" . $end_interval_datetime . "' ) OR
			( css.starttime <= '" . $end_interval_datetime . "' AND css.endtime IS NULL ) ) AND 
			( ( css.vsqrecordingfeed IS NOT NULL AND css.vsqrecordingfeed = lfs.livefeedid ) OR ( css.vsqrecordingfeed IS NULL AND css.vsqsessionid IS NULL) ) AND
			( css.wowzalocalstreamname = lfs.keycode OR css.wowzalocalstreamname = lfs.contentkeycode )
		GROUP BY
			css.vsqsessionid, css.wowzalocalstreamname, css.vsqrecordingfeed";

//echo $query . "\n";

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
function getFirstWowzaRecordFrom($from_timestamp, $streaming_server_app) {
global $db, $debug, $myjobid, $app, $jconf;

	if ( empty($from_timestamp) ) $from_timestamp = 0;

	$from_datetime = date("Y-m-d H:i:s", $from_timestamp);

	$query = "
		SELECT
			css.id,
			css.starttime
		FROM
			cdn_streaming_stats AS css
		WHERE
			css.starttime >= '" . $from_datetime . "' AND
			css.wowzaappid = '" . $streaming_server_app . "' AND
			css.wowzalocalstreamname IS NOT NULL AND
			css.wowzalocalstreamname <> ''
		ORDER BY
			css.starttime
		LIMIT 1";

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
global $platform_definitions, $debug, $myjobid;

	foreach ( $platform_definitions as $key => $value ) {
		if ( stripos($platform_string, $key) !== false ) return $value;
	}

	$debug->log($jconf['log_dir'], $myjobid . ".log", "[WARN] Platform \"" . $platform_string . "\" not found." . trim($query), $sendmail = true);
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