<?php
///////////////////////////////////////////////////////////////////////////////////////////////////
// Data integrity check for converter2.0
///////////////////////////////////////////////////////////////////////////////////////////////////
//  1. Check contributor images (placeholder)
//  2. Check recordings: media files, thumbnails
//  3. Check recording attachments
///////////////////////////////////////////////////////////////////////////////////////////////////

define('BASE_PATH', realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false);
define('DEBUG', false);

include_once(BASE_PATH . 'libraries/Springboard/Application/Cli.php');

// Utils
include_once(BASE_PATH . 'modules/Jobs/job_utils_base.php');
include_once(BASE_PATH . 'modules/Jobs/job_utils_log.php');
include_once(BASE_PATH . 'modules/Jobs/job_utils_status.php');

set_time_limit(0);

// Init
$app = new Springboard\Application\Cli(BASE_PATH, DEBUG);

// Load jobs configuration file
$app->loadConfig('modules/Jobs/config_jobs.php');
$jconf = $app->config['config_jobs'];
$myjobid = $jconf['jobid_integrity_check'];

// Log related init
$debug = Springboard\Debug::getInstance();
$debug->log($jconf['log_dir'], $myjobid . ".log", "Data integrity job started", $sendmail = FALSE);
$recordingsModel = $app->bootstrap->getModel('recordings');
clearstatcache();

// Establish database connection
$db = null;
$db = db_maintain();
$db_close = TRUE;


$num_errors  = 0;
$update      = false;
$print_debug = false;
$threshold   = 0.0;

if ($argc > 1) {
	for($i = 1; $i < $argc; $i++) {
		switch($argv[$i]) {
			case '--help':
				print_r("Checks and optionally repairs datasize values in DB.\nValid options:\n");
				print_r("  --debug - print additional info\n");
				print_r("  --update - update database if needed\n");
				print_r("  --t <int/float> - set threshold percentage for datasize comparison (default: 3%)\n");
				print_r("  --help - print this help\n");
				exit;
			case '--debug':
				$print_debug = true;
				break;
			case '--update':
				$update = true;
				break;
			case '--t':
				if (array_key_exists($i + 1, $argv)) {
					if (is_float($argv[$i + 1] + 0) || is_int($argv[$i + 1] + 0)) {
						$threshold = $argv[++$i] / 100;
						print_r("Threshold set to: ". ($threshold * 100) ."%\n");
					}
				}
				break;
			default:
				print_r("invalid option: '". $argv[$i] ."'\n");
				break 2;
		}
	}
}

echo "update: "; var_dump($update, $threshold);

$log_summary  = "NODE: " . $app->config['node_sourceip'] . "\n";
$log_summary .= "SITE: " . $app->config['baseuri'] . "\n";
$log_summary .= "JOB: " . $myjobid . "\n\n";

$time_start = time();

// Check recordings one by one
$rec = array();
$recordings = array();

$query = "
  SELECT
  id,
  userid,
	title,
  status,
  contentstatus,
  masterstatus,
  contentmasterstatus,
  masterdatasize,
  recordingdatasize
  FROM
  recordings as a
  WHERE
  (
    status = \"" . $jconf['dbstatus_copystorage_ok'] . "\" OR
    status = \"" . $jconf['dbstatus_markedfordeletion'] . "\"
  ) AND (
    masterstatus = \"" . $jconf['dbstatus_copystorage_ok'] . "\" OR
    masterstatus = \"" . $jconf['dbstatus_markedfordeletion'] . "\" OR
    masterstatus = \"" . $jconf['dbstatus_uploaded'] ."\"
  )
  ORDER BY id";

/*$query = "
  SELECT
  id,
  userid,
	title,
  status,
  contentstatus,
  masterstatus,
  contentmasterstatus,
  masterdatasize,
  recordingdatasize,
	mastersourceip,
	contentmastersourceip
  FROM
  recordings as a
  WHERE
  id = 19";
  // id IN(18, 19, 20, 92, 93, 94, 95)";*/

try {
  $recordings = $db->Execute($query);
} catch (exception $err) {
  $msg = "[ERROR] Data integrity check interrupted due to error. Manual restart is required.\nCheck log files.\n\n";
  $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] No connection to DB (getAdoDB() failed). Error message:\n" . $err, $sendmail = TRUE);
  exit (1);
}

$num_recordings = $recordings->RecordCount();
$num_checked_recs = 0;
$num_errors = 0;
$num_updated_recs = 0;

// MAIN CYCLE /////////////////////////////////////////////////////////////////////////////////////
while ( !$recordings->EOF ) {
  // Get current field from the query
  $rec = $recordings->fields;
	// var_dump($rec);exit;
  $rec_id = $rec['id'];
  
  // init log string
  $recording_summary = "";

  $recording_path = $app->config['recordingpath'] . ( $rec_id % 1000 ) . "/" . $rec_id . "/";
	$master_path = $recording_path .'master/';
  $upload_path = $app->config['uploadpath'];

  // Check recording directory
  if ( !file_exists($recording_path) ) {
		$recording_summary .= "[ERROR] recording path does not exist (" . $recording_path . ")\n";
		$recordings->MoveNext();
		continue;
  } elseif  ($rec['status'] !== $jconf['dbstatus_copystorage_ok']) {
		$recordings->MoveNext();
		continue;
	}
	
	$masterdatasize = $rec['masterdatasize'];
	$recordingdatasize = $rec['recordingdatasize'];
	
	$tmp = directory_size($recording_path);
	if ($tmp['code'])	$recordingdatasize_m = $tmp['size'];
	unset($tmp);
	
	$tmp = directory_size($master_path);
	if ($tmp['code']) $masterdatasize_m = $tmp['size'];
	unset($tmp);
	
	if ($recordingdatasize_m !== 0) {
		$diff = abs( 1 - ($rec['recordingdatasize'] / $recordingdatasize_m ));
		if ( $rec['recordingdatasize'] === 0 ) {
			$recording_summary .= "[WARN] 'recordingdatasize' is 0.\n";
			$recordingdatasize = $recordingdatasize_m;
		} elseif ( $diff > $threshold) {
			$recording_summary .= "[WARN] database value and real size of 'recordingdatasize' differs. (db - ". round(
			  $rec['recordingdatasize'] / 1024, 2) ."k / ". round($recordingdatasize_m / 1024, 2) ."k)\n";
			$recordingdatasize = $recordingdatasize_m;
		}
	}
	
	if ($masterdatasize_m !== 0) {
		$diff = abs( 1 - ($rec['masterdatasize'] / $masterdatasize_m));
		if ( $rec['masterdatasize'] === 0 ) {
			$recording_summary .= "[WARN] 'masterdatasize' is 0.\n";
			$masterdatasize = $masterdatasize_m;
		} elseif ( $diff > $threshold) {
			$recording_summary .= "[WARN] database value and real size of 'masterdatasize' differs. (db - ". round(
			  $rec['masterdatasize'] / 1024, 2) ."k / ". round($masterdatasize_m / 1024, 2) ."k)\n";
			$masterdatasize = $masterdatasize_m;
		}
	}
	unset($diff);
	
	if ($recording_summary) {
		$recording_summary = "-- REC#". $rec_id ."\n". $recording_summary;

		// update size
		$values = array(
			'masterdatasize'    => $masterdatasize,
			'recordingdatasize' => $recordingdatasize,
		);
		
		if ($update) {
			$r_model = $app->bootstrap->getModel('recordings');
			$r_model->select($rec['id']);
			$r_model->updateRow($values);
			$recording_summary .= "Updating rec#". $rec_id .": masterdatasize > ". $masterdatasize ." | recordingdatasize > ". $recordingdatasize .".\n";
			$num_updated_recs++;
		}
	} elseif($print_debug) {
		print_r("-- REC#". $rec_id ." - OK (mds-db > ". $rec['masterdatasize'] ." - mds-m > ". $masterdatasize_m ." | rds > ". $rec['recordingdatasize'] ." - rds_m > ". $recordingdatasize_m .")\n");
	}
	
	if ($recording_summary) {
		$num_errors++;
		$debug->log($jconf['log_dir'], "datasize_repair.log", $recording_summary, $sendmail = false);
		print_r($recording_summary);
	}
	
	$num_checked_recs++;
  $recordings->MoveNext();
}
// MAIN CYCLE END ///////////////////////////////////////////////////////////////////////////////

// Calculate check duration
$duration = time() - $time_start;
$log_summary .= "Check duration: " . secs2hms($duration) . "\n";

$log_summary .= "\nNumber of recordings: " . $num_recordings . "\n";
$log_summary .= "Number of checked recordings: " . $num_checked_recs . "\n";
$log_summary .= "Number of updated recordings: " . $num_updated_recs . "\n";
$log_summary .= "Number of faulty recordings: ". $num_errors ."\n\n";

if ($print_debug) print_r($log_summary);
$debug->log($jconf['log_dir'], "datasize_repair.log", "Data integrity check results:\n\n" . $log_summary, $sendmail = false);

// Close DB connection if open
if ( is_resource($db->_connectionID) ) $db->close();

exit;

?>
