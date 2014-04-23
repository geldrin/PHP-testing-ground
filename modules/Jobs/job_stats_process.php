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

// CONFIG:
$stats_time_steps['short']['label']  = "5min";			// 5 min label
$stats_time_steps['short']['secs']   = 60 * 5;			// 5 min
$stats_time_steps['medium']['label'] = "1hour";			// 1 hour label
$stats_time_steps['medium']['secs']  = 60 * 60;			// 1 hour
$stats_time_steps['long']['label']   = "1day";			// 1 day label
$stats_time_steps['long']['secs']    = 60 * 60 * 24;	// 1 day

$platform_definitions = array(
	//	'string to find'	=> "array index/sql column name"
	'Flash/WIN'		=> "flashwin",		// Example: Flash/WIN 12,0,0,77
	'Flash/MAC'		=> "flashmac",		// Example: ?
	'Flash/Linux'	=> "flashlinux",	// Example: ?
	'Android'		=> "android",		// Example: Samsung GT-I9100 stagefright/Beyonce/1.1.9 (Linux;Android 4.1.2)
	'iPhone'		=> "iphone",		// Example: AppleCoreMedia/1.0.0.11D167 (iPhone; U; CPU OS 7_1 like Mac OS X; en_us)
	'iPad'			=> "ipad"			// Example: AppleCoreMedia/1.0.0.10B329 (iPad; U; CPU OS 6_1_3 like Mac OS X; hu_hu)
);

// Empty array for each record initialization
$platforms_null = returnStreamingClientPlatformEmptyArray($platform_definitions);

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

// Load last processed record time
$status_filename = $jconf['temp_dir'] . $myjobid . ".live.status";

$last_processed_timestamp = 0;	// Unix epoch: 1970-01-01 00:00:00

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

			$tmp = explode("=", $line);

			switch ($tmp[0]) {
				case "last_processed_time_" . $stats_time_steps['short']['label']:
					$last_processed_time = strtotime($tmp[1]);
					if ( $last_processed_time === false ) {
						$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Stats status file: not a datetime value ('". $line . "')", $sendmail = false); // TRUE!!!
					}
					break;
			}

		}

		fclose($fh);
	}

}

$last_processed_timestamp = 0;	

// Wowza application to filter
$wowza_app = $jconf['streaming_live_app'];
if ( $app->config['baseuri'] == "dev.videosquare.eu/" ) $wowza_app = "dev" . $wowza_app;

$tmp_round = 0;
while ( 1 ) {

	// Next record to process after last processed record
	$start_interval = getFirstStatsRecordFrom($last_processed_timestamp, $wowza_app);
	// Nothing to process, cdn_streaming_stats DB is empty after $last_processed_timestamp
	if ( $start_interval === false ) exit;		// continue??? sleep???

	// Align to timeline grid (to left/past)
	$start_interval = getTimelineGridSeconds($start_interval, "left", $stats_time_steps['short']['secs']);
	// End of interval to process
	$end_interval = $start_interval + $stats_time_steps['short']['secs'] - 1;

	// Debug information for logging
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] last processed time = " . date("Y-m-d H:i:s", $last_processed_timestamp), $sendmail = false);
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] stats processing for interval: " . date("Y-m-d H:i:s", $start_interval) . " - " . date("Y-m-d H:i:s", $end_interval), $sendmail = false);

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
		$feedid = $stat["vsqrecordingfeed"];
		$streamid = $stat["streamid"];		// livefeeds_stream.id
		$qualitytag = $stat["qualitytag"];	// SD, HD, etc.
		$streamname = $stat["streamname"];	// Wowza stream name (e.g. 123456)

		$platform = findStreamingClientPlatform($stat["clientplayer"]);
//echo "platform: " . $platform . "\n";

		// Country (geo IP)
		$country = geoip_country_code_by_name($stat["clientip"]);
		if ( $country === false ) {
			$country = "notdef";
		}

		//// Statistics records filtered
		// Build array index
		$idx = $streamid . "_" . $country; 
		if ( !isset($stats_f[$feedid][$idx]) ) {
			$stats_f[$feedid][$idx] = $platforms_null;
			$stats_f[$feedid][$idx]['timestamp'] = date("Y-m-d H:i:s", $start_interval);
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

//	var_dump($stat);

//	var_dump($stats_f);

//exit;
//sleep(1);

		$records_processed++;
		$stats->MoveNext();
	}

	// Update results to DB
	if ( count($stats_f) > 0 ) {

		foreach ( $stats_f as $feedid => $feed_array ) {
			foreach ( $feed_array as $idx => $stat_record ) {
				insertStatRecord($stat_record);
			}
		}


	}

	// Shift start interval
	$start_interval += $stats_time_steps['short']['secs'];

	// Update last processed record
	$last_processed_timestamp = $end_interval;
	// Write last processed record to disk (for recovery)
	$content = "last_processed_time_" . $stats_time_steps['short']['label'] . "=" . date("Y-m-d H:i:s", $last_processed_timestamp);
	$err = file_put_contents($status_filename, $content);
	if ( $err === false ) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot write staus file " . $status_filename, $sendmail = false);
	} else {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] last processed record time written to " . $status_filename, $sendmail = false);
	}

	// Debug information for logging
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] records processed: " . $records_processed, $sendmail = false);

$tmp_round++;
if ($tmp_round > 15) exit;

}

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
			lfs.name AS qualitytag,
			lfs.keycode AS streamname
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

//echo $query . "\n";

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
function getFirstStatsRecordFrom($from_timestamp, $streaming_server_app) {
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
		$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
		return false;
	}

	// No records in database
	if ( count($stats) < 1 ) return false;

	$starttime = strtotime($stats[0]["starttime"]);

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
global $platform_definitions;

	foreach ( $platform_definitions as $key => $value ) {
		if ( stripos($platform_string, $key) !== false ) return $value;
	}

echo "platform not found: " . $platform_string . "\n";
exit;

	return "other";
}

function returnStreamingClientPlatformEmptyArray($platform_definitions) {

	$platforms_null = array();

	foreach ( $platform_definitions as $key => $value ) {
		$platforms_null[$value] = 0;
	}

	return $platforms_null;
}

function insertStatRecord($stat_record) {
global $db, $debug, $app, $jconf, $myjobid;

	$values = array(
		'timestamp'			=> $stat_record['timestamp'],
		'livefeedid'		=> $stat_record['livefeedid'],
		'livefeedstreamid'	=> $stat_record['livefeedstreamid'],
		'country'			=> $stat_record['country'],
		'flashwin'			=> $stat_record['flashwin'],
		'flashmac'			=> $stat_record['flashmac'],
		'flashlinux'		=> $stat_record['flashlinux'],
		'android'			=> $stat_record['android'],
		'iphone'			=> $stat_record['iphone'],
		'ipad'				=> $stat_record['ipad']
	);

var_dump($values);

/*	try {
		$liveStats = $app->bootstrap->getModel('live_stats_5min');
		$liveStats->insert($values);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL operation failed." . trim($query), $sendmail = false);	// TRUE!!!!
		return false;
	}
*/

	return true;
}

?>
