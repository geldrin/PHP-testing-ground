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
$myjobid = $jconf['jobid_stats_recseg'];
$debug = Springboard\Debug::getInstance();

// Check operating system - exit if Windows
if ( iswindows() ) {
  echo "ERROR: Non-Windows process started on Windows platform\n";
  exit;
}

// Exit if any STOP file appears
//if ( is_file( $app->config['datapath'] . 'jobs/' . $myjobid . '.stop' ) or is_file( $app->config['datapath'] . 'jobs/all.stop' ) ) exit;

// Runover check. Is this process already running? If yes, report and exit
if ( !runOverControl($myjobid) ) exit;

$vsq_epoch = strtotime("2012-11-01 00:00:00");

$stats_config = array(
    'label'             => "stats_recsegments",
    'sqltablename'      => "statistics_recording_segments_users",
    'segmentlengthsecs' => 60,
    'lastprocessedtime' => $vsq_epoch           // statistics records processed until
);

// --- END OF CONFIG ---

clearstatcache();

// Watchdog
$app->watchdog();

// Establish database connection
$db = db_maintain();

// Check config and status file

// Load last processed record time
$status_filename = $jconf['temp_dir'] . $myjobid . "." . $stats_config['label'] . ".status";

// Status file: read last processed record time
if ( file_exists($status_filename) ) {

    // Is readable?
    if ( !is_readable($status_filename) ) {

        $debug->log($jconf['log_dir'], $myjobid . ".log", "[WARN] Stats status file: " . $status_filename . " does not exist. Restarting from VSQ EPOCH (" . date("Y-m-d H:i:s", $vsq_epoch) . ").", $sendmail = true);
        $stats_config['lastprocessedtime'] = $vsq_epoch;

    } else {

        $fh = fopen($status_filename, "r");

        while( !feof($fh) ) {

            // Read one line from descriptor file
            $line = fgets($fh);
            $line = trim($line);

            // Skip empty lines
            if ( empty($line) ) continue;

            $line_split = explode("=", $line);
            $timestamp = strtotime($line_split[1]);
            if ( $timestamp === false ) {
              $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Stats status file: not a datetime value ('". $line . "')", $sendmail = true);
              exit;
            }
            $stats_config['lastprocessedtime'] = $timestamp;
            $debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Last processed timestamp from status file (" . $stats_config['label'] . "): " . $line_split[1], $sendmail = false);
        }

        fclose($fh);
    }

}

// Processing intervals
$interval_start = $stats_config['lastprocessedtime'] + 1;
$interval_end = time();

$stats_config['lastprocessedtime'] = $interval_end;

$processing_started = time();

$stats = queryViewStatsOnDemandForInterval($interval_start, $interval_end);
if ( $stats === false ) {
    // Nothing to process
    exit;
}

$records_processed = 0;
$records_committed = 0;
$stats_p = array();

// Process interval
while ( !$stats->EOF ) {

    $stat = $stats->fields;

//var_dump($stat);

    $recid = $stat["recordingid"];
    $userid = $stat["userid"];
    if ( empty($userid) ) $userid = 0;
    
    // Calculate segment
    if ( !isset($stats_p[$recid][$userid]) ) {
        $stats_p[$recid][$userid] = array();
    }

    // Index recording segments between positionfrom - positionuntil
    $segment_from = floor($stat['positionfrom'] / $stats_config['segmentlengthsecs']);
    $segment_to = floor(min($stat['positionuntil'], $stat['recordinglength']) / $stats_config['segmentlengthsecs']);
//echo "dbpos: " . $stat['positionfrom'] . " - " . $stat['positionuntil'] . "\n";
//echo "segidx: " . $segment_from . " - " . $segment_to . "\n";

    for ( $i = $segment_from; $i <= $segment_to; $i++) {
        
        if ( !isset($stats_p[$recid][$userid][$i]) ) {
            $stats_p[$recid][$userid][$i] = 1;
        } else {
            $stats_p[$recid][$userid][$i] += 1;
        }

    }

//if ( $records_processed > 10 ) break;

    $records_processed++;
    $stats->MoveNext();
} // End of interval processing while

//var_dump($stats_p);

// Insert records to DB
foreach ($stats_p as $recid => $users) {
    //echo "recid: " . $recid . "\n";
    foreach ($users as $userid => $recuser_segments) {
        //echo "userid: " . $userid . "\n";
        $stats_db = queryStatsRecordingsSegments($recid, $userid);
        //$stats_db = queryStatsRecordingsSegments(20, 15);

        echo "Segments for: rec = " . $recid . " / userid = " . $userid . "\n";
        
        //echo "DB already contains:\n";
        //var_dump($stats_db);
        
        //echo "To be UPDATED: rec = " . $recid . " / userid = " . $userid . "\n";
        //var_dump($recuser_segments);
        
        $sql_insert = array();            
        foreach ($recuser_segments as $recsegment => $viewcounter) {
            //echo "recseg = " . $recsegment . " / vc = " . $viewcounter . "\n";
            
            // Find this recording and user in existing DB records              
            $insert_id = searchForStatsRecordInArray($stats_db, $recsegment);
            array_push($sql_insert, "(" . (($insert_id == false)?"NULL":$insert_id) . "," . $recid . "," . (($userid == 0)?"NULL":$userid) . "," . $recsegment . "," . $viewcounter . ")");
        }
       
        //var_dump($sql_insert);
        if ( !empty($sql_insert) ) {
            $sql_insert_string = implode(",", $sql_insert);
            
            $query = "
                INSERT INTO
                    statistics_recordings_segments_users (id, recordingid, userid, recordingsegment, viewcounter)
                VALUES " . $sql_insert_string . "
                ON DUPLICATE KEY UPDATE
                    viewcounter = viewcounter + VALUES(viewcounter)";
        
            //echo $query . "\n";
     
            try {
                $rs = $db->Execute($query);
            } catch (exception $err) {
                $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed. Processing IS BROKEN! SQL error:\n" . trim($query), $sendmail = true);
                exit;
            }
        
            $records_committed += count($sql_insert);
        }
        
    }

}

// Watchdog
$app->watchdog();

// Time of processing
$processing_time = time() - $processing_started;

// Debug information for logging
$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Statistics records processed and recording segment records inserted/updated: " . $records_processed . " / " . $records_committed, $sendmail = false);

// Log number of committed records to DB
$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] " . $stats_config['label'] . " processing finished in " . secs2hms($processing_time) . ".", $sendmail = false);

// Write last processed record times to disk (for recovery)
$content = "lastprocessedtime_" . $stats_config['label'] . "=" . date("Y-m-d H:i:s", $stats_config['lastprocessedtime']) . "\n";
$err = file_put_contents($status_filename, $content);
if ( $err === false ) {
    $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot write status file " . $status_filename, $sendmail = false);
} else {
    $debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] Last processed record time written to " . $status_filename, $sendmail = false);
}

// Close DB connection if open
if ( is_resource($db->_connectionID) ) $db->close();

// Watchdog
$app->watchdog();

exit;

function queryViewStatsOnDemandForInterval($start_interval, $end_interval) {
global $db, $debug, $myjobid, $app, $jconf;

  $start_interval_datetime = date("Y-m-d H:i:s", $start_interval);
  $end_interval_datetime = date("Y-m-d H:i:s", $end_interval);

  $query = "
    SELECT
        vso.id,
        vso.recordingid,
        vso.viewsessionid,
        vso.positionfrom,
        vso.positionuntil,
        vso.timestamp,
        vso.userid,
        r.organizationid,
        ROUND(GREATEST(IFNULL(r.masterlength, 0), IFNULL(r.contentmasterlength, 0))) AS recordinglength
    FROM
        view_statistics_ondemand AS vso
    LEFT JOIN
        recordings AS r
    ON
        vso.recordingid = r.id
    WHERE
        vso.timestamp >= '" . $start_interval_datetime . "' AND
        vso.timestamp < '" . $end_interval_datetime . "'
    ";

//AND vso.recordingid = 20
    
//echo $query . "\n";
        
  try {
    $stats = $db->Execute($query);
  } catch (exception $err) {
    $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed. " . trim($query), $sendmail = true);
    return false;
  }

  // No records
  if ( $stats->RecordCount() < 1 ) return false;

  return $stats;
}

function queryStatsRecordingsSegments($recordingid, $userid) {
global $db, $debug, $myjobid, $app, $jconf;

    $query = "
        SELECT
            srsu.id,
            srsu.recordingid,
            srsu.userid,
            srsu.recordingsegment,
            srsu.viewcounter
        FROM
            statistics_recordings_segments_users AS srsu
        WHERE
            srsu.recordingid = " . $recordingid . " AND
            srsu.userid " . (($userid == 0)?"IS NULL":"= " . $userid) . "
        ORDER BY
            srsu.recordingsegment";

//echo $query . "\n";
        
  try {
    $stats = $db->getAssoc($query);
  } catch (exception $err) {
    $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed. " . trim($query), $sendmail = true);
    return false;
  }

  if ( count($stats) < 1 ) return array();

  return $stats;
}

function searchForStatsRecordInArray($stats_db, $recsegment) {

    foreach( $stats_db as $id => $record ) {
        //echo "s: id = " . $id . " / recseg = " . $record['recordingsegment'] . "\n";
        if ( $record['recordingsegment'] == $recsegment ) {
            //echo "*** found = " . $id . "\n";
            return $id;
        }
    }

    return false;
}

?>
