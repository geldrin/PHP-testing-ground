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
	'Flash/WIN'		=> "flashwin",		// Example: Flash/WIN 12,0,0,77
	'Flash/MAC'		=> "flashmac",		// Example: ?
	'Flash/Linux'	=> "flashlinux",	// Example: ?
	'Android'		=> "android",		// Example: Samsung GT-I9100 stagefright/Beyonce/1.1.9 (Linux;Android 4.1.2)
	'iPhone'		=> "iphone",		// Example: AppleCoreMedia/1.0.0.11D167 (iPhone; U; CPU OS 7_1 like Mac OS X; en_us)
	'iPad'			=> "ipad"			// Example: AppleCoreMedia/1.0.0.10B329 (iPad; U; CPU OS 6_1_3 like Mac OS X; hu_hu)
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

/*$stats_time_steps['short']['label']  = "5min";			// 5 min label
$stats_time_steps['short']['secs']   = 60 * 5;			// 5 min
$stats_time_steps['medium']['label'] = "1hour";			// 1 hour label
$stats_time_steps['medium']['secs']  = 60 * 60;			// 1 hour
$stats_time_steps['long']['label']   = "1day";			// 1 day label
$stats_time_steps['long']['secs']    = 60 * 60 * 24;	// 1 day
*/

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

// Load last processed record time
$status_filename = $jconf['temp_dir'] . $myjobid . ".live.status";

$last_processed_timestamp = 0;	// Unix epoch: 1970-01-01 00:00:00

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

var_dump($stats_config);

// Which statistics filtering are we doing? See config.
$statsidx = 0;
$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Processing statistics: " . $stats_config[$statsidx]['label'], $sendmail = false);

$ltime = getLastStatsRecordFrom($stats_config[$statsidx]['sqltablename']);
if ( $ltime === false ) {
	$ltime = 0;
} else {
	$ltime = strtotime($ltime) + $stats_config[$statsidx]['interval'] - 1;
}

if ( $stats_config[$statsidx]['lastprocessedtime'] != $ltime ) {

echo "file last proc time not equal to DB:\n";
echo "file: " . date("Y-m-d H:i:s", $stats_config[$statsidx]['lastprocessedtime']) . "\n";
echo "db: " . date("Y-m-d H:i:s", $ltime) . "\n";

} else {

echo "file last proc time EQUAL to DB:\n";
echo "file: " . date("Y-m-d H:i:s", $stats_config[$statsidx]['lastprocessedtime']) . "\n";
echo "db: " . date("Y-m-d H:i:s", $ltime) . "\n";

}
//exit;

$stats_config[$statsidx]['lastprocessedtime'] = 0;

$tmp_round = 0;
while ( 1 ) {

	// Next record to process after last processed record
	$start_interval = getFirstWowzaRecordFrom($stats_config[$statsidx]['lastprocessedtime'], $wowza_app);
	// Nothing to process, cdn_streaming_stats DB is empty after $last_processed_timestamp
	if ( $start_interval === false ) break;

	// Align to timeline grid (to left/past)
	$start_interval = getTimelineGridSeconds($start_interval, "left", $stats_config[$statsidx]['interval']);
	// End of interval to process
	$end_interval = $start_interval + $stats_config[$statsidx]['interval'] - 1;

	// Debug information for logging
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Last processed time = " . date("Y-m-d H:i:s", $stats_config[$statsidx]['lastprocessedtime']), $sendmail = false);
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Stats processing for interval: " . date("Y-m-d H:i:s", $start_interval) . " - " . date("Y-m-d H:i:s", $end_interval), $sendmail = false);

	// Query records between start and end intervals
	$stats = queryStatsForInterval($start_interval, $end_interval, $wowza_app);
	if ( $stats === false ) {
		echo "error\n";
	exit -1; // continue???
	}

	$stats_f = array();

	$records_processed = 0;
	while ( !$stats->EOF ) {

		$stat = $stats->fields;

		// Live feed ID
		$feedid = $stat['vsqrecordingfeed'];
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
			$debug->log($jconf['log_dir'], $myjobid . ".log", "[WARN] No video/content match.", $sendmail = false);
echo "exited\n";
			exit;	// !!!
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
//var_dump($stats_f);

	// Shift start interval
	$start_interval += $stats_config[$statsidx]['interval'];

	// Update last processed record
	$stats_config[$statsidx]['lastprocessedtime'] = $end_interval;

	// Debug information for logging
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Records processed/committed: " . $records_processed . " / " . $records_committed, $sendmail = false);

$tmp_round++;
if ($tmp_round > 15) break;

}

echo "aaaaaaaaaaaaaa\n";

// Write last processed record times to disk (for recovery)
$content = "";
foreach ( $stats_config as $idx => $stats_config_element ) {
	$content .= "lastprocessedtime_" . $stats_config_element['label'] . "=" . date("Y-m-d H:i:s", $stats_config_element['lastprocessedtime']) . "\n";
}
$err = file_put_contents($status_filename, $content);
if ( $err === false ) {
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot write staus file " . $status_filename, $sendmail = false);
} else {
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] Last processed record time written to " . $status_filename, $sendmail = false);
}

exit;

function queryStatsForInterval($start_interval, $end_interval, $streaming_server_app) {
global $db, $debug, $myjobid, $app, $jconf;

	$start_interval_datetime = date("Y-m-d H:i:s", $start_interval);
	$end_interval_datetime = date("Y-m-d H:i:s", $end_interval);

/*
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
			lfs.name AS qualitytag,
			lfs.keycode
		FROM
			cdn_streaming_stats AS css,
			livefeed_streams AS lfs
		WHERE
			css.wowzaappid = '" . $streaming_server_app . "'  AND (
			( css.starttime >= '" . $start_interval_datetime . "' AND css.starttime <= '" . $end_interval_datetime . "' ) OR
			( css.endtime >= '" . $start_interval_datetime . "'   AND css.endtime <= '" . $end_interval_datetime . "' ) OR
			( css.starttime <= '" . $end_interval_datetime . "' AND css.endtime IS NULL ) ) AND 
			css.vsqrecordingfeed = lfs.livefeedid AND
			css.wowzalocalstreamname = lfs.keycode
		GROUP BY
			css.vsqsessionid";
*/

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
			css.vsqrecordingfeed = lfs.livefeedid AND
			( css.wowzalocalstreamname = lfs.keycode OR css.wowzalocalstreamname = lfs.contentkeycode )
		GROUP BY
			css.vsqsessionid, css.wowzalocalstreamname, css.vsqrecordingfeed";

//echo $query . "\n";

// Record problémák:
// - minõségi váltás: nincs új rekord (Wowza oldalon nem figyelünk valami eventet?)
// - nem lezáruló rekordok: Wowza restart?

/*
contentet is figyeljük, plusz a group by bonyolultabb:
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
		lfs.name AS qualitytag,
		lfs.keycode,
		lfs.contentkeycode
	FROM
		cdn_streaming_stats AS css,
		livefeed_streams AS lfs
	WHERE
		css.wowzaappid = 'devvsqlive'  AND (
		( css.starttime >= '2014-04-24 16:00:00' AND css.starttime <= '2014-04-24 16:10:00' ) OR
		( css.endtime >= '2014-04-24 16:00:00'   AND css.endtime <= '2014-04-24 16:10:00' ) OR
		( css.starttime <= '2014-04-24 16:10:00' AND css.endtime IS NULL ) ) AND
		css.vsqrecordingfeed = lfs.livefeedid AND
		( css.wowzalocalstreamname = lfs.keycode OR css.wowzalocalstreamname = lfs.contentkeycode )
		AND css.endtime IS NOT NULL
	GROUP BY
		css.vsqsessionid, css.wowzalocalstreamname, css.vsqrecordingfeed
	ORDER BY
		css.starttime
*/


	try {
		$stats = $db->Execute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = false); // TRUE!!!
		return false;
	}

	// No records for interval
//	if ( $stats->RecordCount() < 1 ) return false;

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
			css.wowzaappid = '" . $streaming_server_app . "'
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

var_dump($values);

/*
	try {
		$liveStats = $app->bootstrap->getModel($db_stats_table);
		$liveStats->insert($values);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL operation failed." . trim($query), $sendmail = false);	// TRUE!!!!
		return false;
	}
*/

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

?>
