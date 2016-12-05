<?php
// Videosquare live statistics process job 2

define('BASE_PATH', realpath( __DIR__ . '/../..' ) . '/' );
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
$myjobid = $jconf['jobid_stats_process'] . "2";
$debug = Springboard\Debug::getInstance();

// Check operating system - exit if Windows
if ( iswindows() ) {
  echo "ERROR: Non-Windows process started on Windows platform\n";
  exit;
}

// Exit if any STOP file appears
if ( is_file( $app->config['datapath'] . 'jobs/' . $myjobid . '.stop' ) or is_file( $app->config['datapath'] . 'jobs/all.stop' ) ) exit;

// Runover check. Is this process already running? If yes, report and exit
if ( !runOverControl($myjobid) ) exit;

// --- CONFIG ---
$platform_definitions = array(
  //  'string to find'  => "array index/sql column name"
  'Flash/WIN'   => "flashwin",      // Example: Flash/WIN 12,0,0,77
  'Flash/MAC'   => "flashmac",      // Example: Flash/MAC 12,0,0,77
  'Flash/Linux' => "flashlinux",    // Example: ?
  'Flash'       => "flashwin",      // Example: Flash/Wirecast/FM 1.0 (compatible; FMSc/1.0) | Flash/FMLE/3.0 (compatible; FMSc/1.0) (nem korrekt???)
  'Android'     => "android",       // Example: Samsung GT-I9100 stagefright/Beyonce/1.1.9 (Linux;Android 4.1.2)
  'iPhone'      => "iphone",        // Example: AppleCoreMedia/1.0.0.11D167 (iPhone; U; CPU OS 7_1 like Mac OS X; en_us)
  'iPad'        => "ipad"           // Example: AppleCoreMedia/1.0.0.10B329 (iPad; U; CPU OS 6_1_3 like Mac OS X; hu_hu)
// Egyebek?
// Flash IDE Builder: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_2) AppleWebKit/537.75.14 (KHTML, like Gecko) Version/7.0.3 Safari/537.75.14
);

$vsq_epoch = strtotime("2014-11-01 00:00:00");

$stats_config = array(
    0 => array(
        'label'             => "5min",
        'sqltablename'      => "statistics_live_5min",
        'interval'          => 60 * 5,
        'lastprocessedtime' => $vsq_epoch
    ),
    1 => array(
        'label'             => "hourly",
        'sqltablename'      => "statistics_live_hourly",
        'interval'          => 60 * 60,
        'lastprocessedtime' => $vsq_epoch
    ),
    2 => array(
        'label'             => "daily",
        'sqltablename'      => "statistics_live_daily",
        'interval'          => 60 * 60 * 24,
        'lastprocessedtime' => $vsq_epoch
    )
);

// Wowza application to filter
$wowza_app = $jconf['streaming_live_app'];
if ( isset($app->config['production']) && $app->config['production'] === false ) $wowza_app = "dev" . $wowza_app;

// --- END OF CONFIG ---

clearstatcache();

// Watchdog
$app->watchdog();

// Check GeoIP database
$geoip = true;
if ( !geoip_db_avail(GEOIP_COUNTRY_EDITION) ) {
    $debug->log($jconf['log_dir'], $myjobid . ".log", "[WARN] GeoIP database is not available. Please check.", $sendmail = false);
    $geoip = false;
}

// Delete all stuff - if required!
//removeStatsAll($stats_config, true);

// Check Wowza records with open endtime
$now_hour = date("G");
$now_min = date("i");

// Empty array for each record initialization
$platforms_null = returnStreamingClientPlatformEmptyArray($platform_definitions);

// Query media servers
$media_servers = queryMediaServers();
if ( $media_servers === false ) {
    $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot find media servers", $sendmail = false);
    exit;
}

// Loop through defined statistics (5min, hourly, daily). See config.
for ( $statsidx = 0; $statsidx < count($stats_config); $statsidx++ ) {

    // Load last processed record time
    $status_filename = $jconf['temp_dir'] . $myjobid . "." . $stats_config[$statsidx]['label'] . ".status";

    // ## Status file: read last processed record time
	// Does it exist?
	if ( !file_exists($status_filename) ) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Stats status file: " . $status_filename . " not found.", $sendmail = true);
		exit;
	}

	// Is it readable/writeable?
    if ( ( !is_readable($status_filename) ) or ( !is_writeable($status_filename) ) ) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Stats status file: " . $status_filename . " not readable/writeable.", $sendmail = true);
		exit;
	}

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
	// End of status file operation
    
    // Get last processed record from stats_live_* tables to cross check last processed record. - NOT NEEDED TO CROSS CHECK?
/*    $ltime = getLastStatsRecordFrom($stats_config[$statsidx]['sqltablename']);
    if ( $ltime === false ) {
        // Debug information for logging
        $debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Last processed time from db: no record found. Falling back to " . date("Y-m-d H:i:s", $vsq_epoch), $sendmail = false);
        $stats_config[$statsidx]['lastprocessedtime'] = $vsq_epoch;
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
*/

    $start_interval = $stats_config[$statsidx]['lastprocessedtime'] + 1;
  
    $records_committed_all = 0;
    $processing_started = time();
    //  Loop through the full time period until. Exit when right end is in future.
    while ( ( $start_interval + $stats_config[$statsidx]['interval'] ) < time() ) {

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

            // Next record breaking the inactive period that also occurred before the end of the last whole interval
            $now_left = getTimelineGridSeconds(time(), "left", $stats_config[$statsidx]['interval']);
            $next_active_record = getFirstLiveStatRecordFromInterval($end_interval, $now_left, $wowza_app);
            if ( $next_active_record === false ) {
                // Nothing to process, view_stats_live DB table is empty from end of interval. Exit processing.

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
            $start_interval = $next_active_left;
            $end_interval = $next_active_left + $stats_config[$statsidx]['interval'] - 1;

            // !!! Never happens???
            // Query finally selected interval with record(s)
            $stats = queryStatsForInterval($start_interval, $end_interval, $wowza_app);
            if ( $stats === false ) {
                $debug->log($jconf['log_dir'], $myjobid . ".log", "[WARN] Unexpected. No record(s) to process. Interval: " . date("Y-m-d H:i:s", $start_interval) . " - " . date("Y-m-d H:i:s", $end_interval), $sendmail = false);
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
    $errors = "";

    while ( !$stats->EOF ) {

        $stat = $stats->fields;

        // Live feed ID
        if ( !empty($stat['livefeedid']) ) {
            $feedid = $stat['livefeedid'];
        } else {
            $debug->log($jconf['log_dir'], $myjobid . ".log", "[WARN] No livefeedid for stats#" . $stat['id'] . ". Skipping.", $sendmail = false);
            $stats->MoveNext();
            continue;
        }

        $livefeedstreamid = $stat['livefeedstreamid'];          // livefeeds_stream.id
        $qualitytag = $stat['streamname'];              // SD, HD, etc.
        $streamname = $stat['keycode'];                 // Wowza stream name (e.g. 123456)
        $contentstreamname = $stat['contentkeycode'];   // Wowza content stream name

        // User agent
        $platform = findStreamingClientPlatform($stat['useragent']);

        // Country (geo IP)
        if ( $geoip ) {
            if ( !isIpPrivate($stat['ipaddress']) ) {
                $country = @geoip_country_code_by_name($stat['ipaddress']);
                if ( $country === false ) {
                    $country = "notdef";
                }
            } else {
                    $country = "local";
            }
        }
        
        // Find Stream server
        $server_idx = findMediaServers($media_servers, $stat['streamserver']);
        if ( $server_idx === false ) {
               $errors .= print_r($stat, true) . "\n";
               $server_idx = 1;
        }
        
        //// Statistics records filtered
        // Build array index
        $idx = $livefeedstreamid . "_" . $country . "_" . $server_idx;
        // Initialize (if record is not yet open)
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
            $stats_f[$feedid][$idx]['livefeedstreamid'] = $livefeedstreamid;
        }
        // Add country
        if ( !isset($stats_f[$feedid][$idx]['country']) ) {
            $stats_f[$feedid][$idx]['country'] = $country;
        }
        // Streaming server
        if ( !isset($stats_f[$feedid][$idx]['streamserver']) ) {
            $stats_f[$feedid][$idx]['streamserver'] = $server_idx;
        }
        // Increase platform counter
        if ( isset($stats_f[$feedid][$idx][$platform]) ) {
            $stats_f[$feedid][$idx][$platform]++;
        }
        
        $records_processed++;
        $stats->MoveNext();
    } // End of stats while loop

    if ( !empty($errors) ) {
        $debug->log($jconf['log_dir'], $myjobid . ".log", "[WARN] Media server not found for the following stat records:\n" . $errors, $sendmail = true);
    }
    
    // Update results to DB
    $records_committed = 0;
    if ( count($stats_f) > 0 ) {

        $records_committed = 0;
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
    $debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Records processed/inserted: " . $records_processed . " / " . $records_committed, $sendmail = false);
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

// Watchdog
$app->watchdog();

exit;

// Query media servers
function queryMediaServers() {
global $debug, $myjobid, $app, $jconf;

    $model = $app->bootstrap->getModel('cdn_streaming_servers');

    $query = "
        SELECT
            css.id,
            css.server,
            css.serverip,
            css.servicetype,
            css.disabled
        FROM
            cdn_streaming_servers AS css
        WHERE
            css.id > 0
        ";

    try {
        $rs = $model->safeExecute($query);
    } catch (exception $err) {
        $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
        return false;
    }

    if ( $rs->RecordCount() < 1 ) return false;

    $rs_array = adoDBResourceSetToArray($rs);
    
    return $rs_array;
}

// Find media server
function findMediaServers($media_servers, $server_fqdn) {

    $server_idx = false;
    for ($i = 0; $i < count($media_servers); $i++) {

        $val = array_search ($server_fqdn, $media_servers[$i], false);
        if ( $val !== false ) {
            $server_idx = $media_servers[$i]['id'];
            break;
        }
        
    }

    return $server_idx;
}

// Query active stream records from database in a time interval
function queryStatsForInterval($start_interval, $end_interval, $streaming_server_app) {
global $debug, $myjobid, $app, $jconf;

    $start_interval_datetime = date("Y-m-d H:i:s", $start_interval);
    $end_interval_datetime = date("Y-m-d H:i:s", $end_interval);

    $model = $app->bootstrap->getModel('view_statistics_live');

    $query = "
        SELECT
            vsl.id,
            vsl.userid,
            vsl.livefeedid,
            vsl.livefeedstreamid,
            vsl.sessionid,
            vsl.viewsessionid,
            vsl.startaction,
            vsl.stopaction,
            vsl.streamscheme,
            vsl.streamserver,
            vsl.streamurl,
            vsl.ipaddress,
            vsl.useragent,
            vsl.timestampfrom,
            vsl.timestampuntil,
            lf.name AS feedname,
            lfs.qualitytag AS streamname,
            lfs.keycode,
            lfs.contentkeycode  
        FROM
            view_statistics_live AS vsl,
            livefeeds AS lf,
            livefeed_streams AS lfs
        WHERE
            vsl.streamurl LIKE '/" . $streaming_server_app . "%'
            AND (
                ( vsl.timestampfrom >= '" . $start_interval_datetime . "' AND vsl.timestampfrom <= '" . $end_interval_datetime . "' ) OR
                ( vsl.timestampuntil >= '" . $start_interval_datetime . "' AND vsl.timestampuntil <= '" . $end_interval_datetime . "' ) OR
                ( vsl.timestampfrom < '" . $start_interval_datetime . "' AND vsl.timestampuntil > '" . $end_interval_datetime . "' ) )
            AND vsl.livefeedid = lf.id
            AND vsl.livefeedstreamid = lfs.id";

    try {
        $rs = $model->safeExecute($query);
    } catch (exception $err) {
        $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = false); // TRUE!!!
        return false;
    }

    if ( $rs->RecordCount() < 1 ) return false;

    return $rs;
}

// Get next live statistics record timestamp (to help jumping empty time slots)
// WARNING: does not check for active records
function getFirstLiveStatRecordFromInterval($from_timestamp, $to_timestamp, $streaming_server_app) {
 global $debug, $myjobid, $app, $jconf;

    if ( empty($from_timestamp) ) $from_timestamp = 0;

    $from_datetime = date("Y-m-d H:i:s", $from_timestamp);
    $to_datetime = date("Y-m-d H:i:s", $to_timestamp);

    $model = $app->bootstrap->getModel('view_statistics_live');

    $query = "
    SELECT
        vsl.id,
        vsl.timestampfrom
    FROM
        view_statistics_live AS vsl
    WHERE
        vsl.timestampfrom >= '" . $from_datetime . "' AND
        vsl.timestampfrom <= '" . $to_datetime . "' AND
        vsl.streamurl LIKE '/" . $streaming_server_app . "%'
    ORDER BY
        vsl.timestampfrom
    LIMIT 1
    ";

    try {
        $rs = $model->safeExecute($query);
    } catch (exception $err) {
        $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
        return false;
    }

    if ( $rs->RecordCount() < 1 ) return false;

    $rs_array = adoDBResourceSetToArray($rs);
    
    $starttime = strtotime($rs_array[0]['timestampfrom']);

    return $starttime;
}

function getLastStatsRecordFrom($db_stats_table) {
global $debug, $myjobid, $app, $jconf;

    $model = $app->bootstrap->getModel($db_stats_table);

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
        $rs = $model->safeExecute($query);
    } catch (exception $err) {
        $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
        return false;
    }

    if ( $rs->RecordCount() < 1 ) return false;

    $rs_array = adoDBResourceSetToArray($rs);

    $starttime = strtotime($rs_array[0]['timestamp']);

    return $starttime;
}

function getTimelineGridSeconds($timestamp, $direction = "left", $timeresolution) {

  if ( ( $timeresolution != 5 * 60 ) and ( $timeresolution != 60 * 60 ) and ( $timeresolution != 60 * 60 * 24 ) ) return false;

  if ( ( $timeresolution == 5 * 60 ) or ( $timeresolution == 60 * 60 ) ) {

    $mod = $timestamp % $timeresolution;

    if ( $direction == "left" ) {
      $timestamp_grid = $timestamp - $mod;
    } else {
      $timestamp_grid = $timestamp - $mod + $timeresolution;
    }

  } else {

    $timestamp_grid = strtotime(date("Y-m-d 00:00:00", $timestamp));

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
        'timestamp'             => $stat_record['timestamp'],
        'livefeedid'            => $stat_record['livefeedid'],
        'livefeedstreamid'      => $stat_record['livefeedstreamid'],
        'streamingserverid'     => $stat_record['streamserver'],
        'iscontent'             => 0,
        'country'               => $stat_record['country'],
        'numberofflashwin'      => $stat_record['flashwin'],
        'numberofflashmac'      => $stat_record['flashmac'],
        'numberofflashlinux'    => $stat_record['flashlinux'],
        'numberofandroid'       => $stat_record['android'],
        'numberofiphone'        => $stat_record['iphone'],
        'numberofipad'          => $stat_record['ipad'],
        'numberofunknown'       => $stat_record['unknown'],
    );

    try {
        $liveStats = $app->bootstrap->getModel($db_stats_table);
        $liveStats->insert($values);
    } catch (exception $err) {
        $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL operation failed." . print_r($values, true), $sendmail = false); // TRUE!!!!
        return false;
    }

    return true;
}

function recursive_array_search($needle,$haystack) {
    
  foreach( $haystack as $key=>$value ) {
        $current_key = $key;
        if ( $needle === $value OR ( is_array($value) && recursive_array_search($needle, $value) !== false ) ) {
            return $current_key;
        }
    }

    return false;
}

function removeStatsAll($stats_config, $isexec) {
 global $debug, $myjobid, $app, $jconf;

    if ( !$isexec ) return true;
    
    // Log
    $debug->log($jconf['log_dir'], $myjobid . ".log", "[WARN] Removing all statistics information", $sendmail = false);

    for ( $statsidx = 0; $statsidx < count($stats_config); $statsidx++ ) {

        $model = $app->bootstrap->getModel($stats_config[$statsidx]['sqltablename']);
    
        // Truncate table
        $query = "TRUNCATE TABLE " . $stats_config[$statsidx]['sqltablename'];

        try {
            $rs = $model->safeExecute($query);
        } catch (exception $err) {
            $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
            return false;
        }

        // Remove status file
        $status_filename = $jconf['temp_dir'] . $myjobid . "." . $stats_config[$statsidx]['label'] . ".status";
        if ( file_exists($status_filename ) ) unlink($status_filename);

        // Log
        $debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] Stats table " . $stats_config[$statsidx]['sqltablename'] . " cleaned. Status file removed: " . $status_filename, $sendmail = false);
    }

    return true;
}

?>
