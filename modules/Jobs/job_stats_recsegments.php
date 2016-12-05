<?php
// Videosquare on-demand statistics processing (recording segments count)

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
if ( is_file( $app->config['datapath'] . 'jobs/' . $myjobid . '.stop' ) or is_file( $app->config['datapath'] . 'jobs/all.stop' ) ) exit;

// Runover check. Is this process already running? If yes, report and exit
if ( !runOverControl($myjobid) ) exit;

// ## CONFIG

// # Debug mode
$isdebug = false;
// This will filter to specific recording
//$debug_recording = 1252;

$vsq_epoch = strtotime("2012-11-01 00:00:00");

$stats_config = array(
    'label'             => "stats_recsegments",
    'sqltablename'      => "statistics_recording_segments",
    'segmentlengthsecs' => 60,
    'lastprocessedtime' => $vsq_epoch           // statistics records processed until
);

// ## END OF CONFIG ---

clearstatcache();

// Watchdog
$app->watchdog();

// Establish database connection
$db = db_maintain();

// Check config and status file

// Load last processed record time
$status_filename = $jconf['temp_dir'] . $myjobid . "." . $stats_config['label'] . ".status";

// ## Status file: read last processed record time

// Does it exist?
if ( !file_exists($status_filename) ) {
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Status file not found: " . $status_filename . ". Exiting...", $sendmail = true);
	exit;
}

// Is it readable/writeable?
if ( ( !is_readable($status_filename) ) or ( !is_writeable($status_filename) ) ) {

	$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Stats status file: " . $status_filename . " not readable/writeable.", $sendmail = true);
	exit;

}

// Read date and time value
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
	if ( $isdebug ) $debug->log($jconf['log_dir'], $myjobid . ".log", "[DEBUG] Recording segment length (stats resolution): " . $stats_config['segmentlengthsecs'] . "sec", $sendmail = false);
					
}

fclose($fh);
// End of reading status file

// Processing intervals
$interval_start = $stats_config['lastprocessedtime'] + 1;
$interval_end = time();
$stats_config['lastprocessedtime'] = $interval_end;
$processing_started = time();

unset($stats);
if ( isset($debug_mode) and isset($debug_recording) and ( $debug_recording > 0 ) ) {
	$stats = queryViewStatsOnDemandForInterval($interval_start, $interval_end, $debug_recording);
} else {
	$stats = queryViewStatsOnDemandForInterval($interval_start, $interval_end);
}
if ( $isdebug ) $debug->log($jconf['log_dir'], $myjobid . ".log", "[DEBUG] Processing for interval " . date("Y-m-d H:i:s", $interval_start) . " - " . date("Y-m-d H:i:s", $interval_end), $sendmail = false);


$records_processed = 0;
$records_committed = 0;
unset($stats_p);
$stats_p = array();
if ( $stats !== false ) {

	// Process interval
	while ( !$stats->EOF ) {

		$stat = $stats->fields;

		$recid = $stat["recordingid"];
		
		// Calculate segment
		if ( !isset($stats_p[$recid]) ) {
			$stats_p[$recid] = array();
		}

		// Index recording segments between positionfrom - positionuntil
		$segment_from = floor($stat['positionfrom'] / $stats_config['segmentlengthsecs']);
		$segment_to = floor(min($stat['positionuntil'], $stat['recordinglength']) / $stats_config['segmentlengthsecs']);
		
		if ( $isdebug ) $debug->log($jconf['log_dir'], $myjobid . ".log", "[DEBUG] (" . $records_processed . ") Stats id# " . $stat['id'] . " record recid#" . $recid . ": " . $stat['positionfrom'] . "-" . $stat['positionuntil'] . "sec (segments: " . $segment_from . "-" . $segment_to . ")", false);

		for ( $i = $segment_from; $i <= $segment_to; $i++) {
			
			if ( !isset($stats_p[$recid][$i]) ) {
				$stats_p[$recid][$i] = 1;
			} else {
				$stats_p[$recid][$i] += 1;
			}

		}

		$records_processed++;
		$stats->MoveNext();
	} // End of interval processing while

	// Debug information for logging
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Player statistics records to be processed: " . $records_processed, false);

	// Insert records to DB
	foreach ($stats_p as $recid => $recsegments) {

		// Debug
		if ( $isdebug ) $debug->log($jconf['log_dir'], $myjobid . ".log", "[DEBUG] Recording id#" . $recid . " is being processed.", false);

		// Get existing recsegment records from DB
		unset($stats_db);
		$stats_db = queryStatsRecordingsSegments($recid);

		// SQL query insert data: assemble into an array
		$rec_updated = 0;
		$sql_insert = array();
		$debug_tmp = "";
		foreach ($recsegments as $recsegment => $viewcounter) {
			
			$debug_tmp .= $recsegment . ";" . $viewcounter . "\n";
		  
			// Find this recording and user in existing DB records              
			$insert_id = searchForStatsRecordInArray($stats_db, $recsegment);
			array_push($sql_insert, "(" . (($insert_id === false)?"NULL":$insert_id) . "," . $recid . "," . $recsegment . "," . $viewcounter . ")");
			
			$rec_updated++;
		}

		// Debug
		if ( $isdebug ) $debug->log($jconf['log_dir'], $myjobid . ".log", "[DEBUG] Recording stats to be updated:\n" . $debug_tmp, false);
		if ( $isdebug ) $debug->log($jconf['log_dir'], $myjobid . ".log", "[DEBUG] SQL insert array: id, recid, recsegment, viewcounter\n" . print_r($sql_insert, true), false);
		
		// INSERT/UPDATE recsegment DB record
		if ( !empty($sql_insert) ) {
			$sql_insert_string = implode(",", $sql_insert);
			
			$query = "
				INSERT INTO
					statistics_recordings_segments (id, recordingid, recordingsegment, viewcounter)
				VALUES " . $sql_insert_string . "
				ON DUPLICATE KEY UPDATE
					viewcounter = viewcounter + VALUES(viewcounter)";
	 
			try {
				$rs = $db->Execute($query);
			} catch (exception $err) {
				$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed. Processing IS BROKEN! SQL error:\n" . trim($query), true);
				exit;
			}
		
			// Debug
			$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Recording id#" . $recid . " recsegments inserted or updated: " . $rec_updated, false);
		
			$records_committed += count($sql_insert);
		}
		
	}

	// Watchdog
	$app->watchdog();
	
	// Time of processing
	$processing_time = time() - $processing_started;

	// Debug information for logging
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Player statistics records processed: " . $records_processed, $sendmail = false);

	// Log number of committed records to DB
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] " . $stats_config['label'] . " processing finished in " . secs2hms($processing_time) . ".", $sendmail = false);

} else {
	if ( $isdebug) $debug->log($jconf['log_dir'], $myjobid . ".log", "[DEBUG] No stats records were found. Exiting.", $sendmail = false);
}

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

function queryViewStatsOnDemandForInterval($start_interval, $end_interval, $debug_recording = null) {
global $db, $debug, $myjobid, $app, $jconf;

  $start_interval_datetime = date("Y-m-d H:i:s", $start_interval);
  $end_interval_datetime = date("Y-m-d H:i:s", $end_interval);
  
  $recording_sql = "";
  if ( !empty($debug_recording) and ( $debug_recording > 0) ) $recording_sql = " AND vso.recordingid = " . $debug_recording;

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
        vso.timestamp < '" . $end_interval_datetime . "' AND
        ( vso.positionuntil - vso.positionfrom ) > 0" .
		$recording_sql;
        
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

function queryStatsRecordingsSegments($recordingid) {
global $db, $debug, $myjobid, $app, $jconf;

    $query = "
        SELECT
            srs.id,
            srs.recordingid,
            srs.recordingsegment,
            srs.viewcounter
        FROM
            statistics_recordings_segments AS srs
        WHERE
            srs.recordingid = " . $recordingid . "
        ORDER BY
            srs.recordingsegment";
        
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
        if ( $record['recordingsegment'] == $recsegment ) return $id;
    }
    
    return false;
}

?>
