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
$status_filename = $jconf['temp_dir'] . $myjobid . ".status";

$last_processed_timestamp = 0;	// Unix epoch: 1970-01-01 00:00:00
/*
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
				case "last_processed_time":
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
*/

$wowza_app = $jconf['streaming_live_app'];
if ( $app->config['baseuri'] == "dev.videosquare.eu/" ) $wowza_app = "dev" . $wowza_app;

$next_record_timestamp = getFirstStatsRecordFrom($last_processed_timestamp, $wowza_app);
// No record in cdn_streaming_stats DB table
if ( $next_record_timestamp === false ) exit;		// continue???
$start_interval = getTimelineGridSeconds($next_record_timestamp, "left", 300);	// Normalize time value to the left (past) for 5mins (e.g. 15:05:00)

/*$big_bang_time = strtotime($next_record_time);

// First run: no process have taken place before
if ( $last_processed_time < $big_bang_time ) {
	$start_interval = $big_bang_time;
} else {
	$start_interval = $last_processed_time + 1;
}*/

$end_interval = $start_interval + 5 * 60;

// Debug information for logging
$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] next_record_time = " . date("Y-m-d H:i:s", $next_record_timestamp), $sendmail = false);
$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] last_processed_time = " . date("Y-m-d H:i:s", $last_processed_timestamp), $sendmail = false);
$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] stats processing for interval: " . date("Y-m-d H:i:s", $start_interval) . " - " . date("Y-m-d H:i:s", $end_interval), $sendmail = false);

// Query records between start and end intervals
$stats = queryStatsForInterval(date("Y-m-d H:i:s", $start_interval), date("Y-m-d H:i:s", $end_interval), $wowza_app);
if ( $stats === false ) {
	echo "error\n";
exit -1;
// continue???
}

$stats_f = array();

while ( !$stats->EOF ) {

	$stat = $stats->fields;

	// Live feed ID
	$feedid = $stat["vsqrecordingfeed"];

	//// Platform
	// Example: Flash/WIN 12,0,0,77
	$platform = "";
	$pos = stripos($stats["clientplayer"], "Flash");
	if ( $pos !== false ) $platform = "flash";
	// Example: Samsung GT-I9100 stagefright/Beyonce/1.1.9 (Linux;Android 4.1.2)
	$pos = stripos($stats["clientplayer"], "Android");
	if ( $pos !== false ) $platform = "android";
	// Example: AppleCoreMedia/1.0.0.10B329 (iPad; U; CPU OS 6_1_3 like Mac OS X; hu_hu)
	$pos = stripos($stats["clientplayer"], "iPad");
	if ( $pos !== false ) $platform = "apple/ipad";
	if ( empty($platform) ) $platform = "other";
	// Others:
	// - Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)

	// Country (geo IP)
	$country = geoip_country_code_by_name($stat["clientip"]);

	// Add this record
	if ( !isset($stats_f[$feedid][$country][$platform] ) {
		$stats_f[$feedid][$country][$platform] = 1;
	} else {
		$stats_f[$feedid][$country][$platform]++;
	}


	// Add record
/*	if ( !isset($stats_f[$feedid]) ) {
		$stats_f[$feedid][$country] = array();

	}
*/

var_dump($stat);

exit;

	$stats->MoveNext();
}

exit;

function queryStatsForInterval($interval_start_time, $interval_end_time, $streaming_server_app) {
global $db, $debug, $myjobid, $app, $jconf;

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
			lfs.name,
			lfs.keycode
		FROM
			cdn_streaming_stats AS css,
			livefeed_streams AS lfs
		WHERE
			css.wowzaappid = '" . $streaming_server_app . "'  AND (
			( css.starttime >= '" . $interval_start_time . "' AND css.starttime <= '" . $interval_end_time . "' ) OR
			( css.endtime >= '" . $interval_start_time . "'   AND css.endtime <= '" . $interval_end_time . "' ) OR
			( css.starttime <= '" . $interval_end_time . "' AND css.endtime IS NULL ) ) AND 
			css.vsqrecordingfeed = lfs.livefeedid AND
			css.wowzalocalstreamname = lfs.keycode
		GROUP BY
			css.vsqsessionid";

echo $query . "\n";

	try {
		$stats = $db->Execute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = false); // TRUE!!!
		return false;
	}

	// No records for interval
	if ( $stats->RecordCount() < 1 ) return false;

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

echo $query . "\n";

	try {
		$stats = $db->getArray($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
		return false;
	}

	// No records in database
	if ( count($stats) < 1 ) return false;

var_dump($stats);

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

?>
