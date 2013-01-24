<?php
// File remove job handling:
//	- Remove recordings.status = "markedfordeleltion" recordings from storage

define('BASE_PATH',	realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

// Utils
include_once('job_utils_base.php');
include_once('job_utils_log.php');
include_once('job_utils_status.php');

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');

set_time_limit(0);

// Init
$app = new Springboard\Application\Cli(BASE_PATH, PRODUCTION);

// Load jobs configuration file
$app->loadConfig('modules/Jobs/config_jobs.php');
$jconf = $app->config['config_jobs'];
$myjobid = $jconf['jobid_remove_files'];

// Log related init
$debug = Springboard\Debug::getInstance();
$debug->log($jconf['log_dir'], $myjobid . ".log", "Remove files job started", $sendmail = false);

// Check operating system - exit if Windows
if ( iswindows() ) {
	echo "ERROR: Non-Windows process started on Windows platform\n";
	exit;
}

// Exit if any STOP file exists
if ( is_file( $app->config['datapath'] . 'jobs/job_remove_files.stop' ) or is_file( $app->config['datapath'] . 'jobs/all.stop' ) ) exit;

clearstatcache();

$app->watchdog();

$db_close = FALSE;
$sleep_length = $jconf['sleep_long'];

// Establish database connection
try {
	$db = $app->bootstrap->getAdoDB();
} catch (exception $err) {
	// Send mail alert, sleep for 15 minutes
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] No connection to DB (getAdoDB() failed). Error message:\n" . $err, $sendmail = true);
	// Sleep 15 mins then resume
//		$sleep_length = 15 * 60;
	exit -1;
}
$db_close = TRUE;

// Initialize log for closing message and total duration timer
$global_log = "Removing recording(s) from storage:\n\n";
$start_time = time();

// Attached documents: query pending uploads
$recordings = array();
$size_toremove = 0;
if ( query_recordings2remove($recordings) ) {

	while ( !$recordings->EOF ) {

		$recording = array();
		$recording = $recordings->fields;

		$global_log .= "ID: " . $recording['id'] . "\n";
		$global_log .= "User: " . $recording['email'] . " (domain: " . $recording['domain'] . ")\n";

		// Directory to remove
		$remove_path = $app->config['recordingpath'] . ( $recording['id'] % 1000 ) . "/" . $recording['id'] . "/";

		// Log file path information
		$global_log .= "Remove: " . $remove_path . "\n";

		$err = array();
		$err = directory_size($remove_path);
	
		$dir_size = 0;
		if ( $err['code'] === TRUE ) {
			$size_toremove += $err['size'];
			$dir_size = round($err['size'] / 1024 / 1024, 2);
			$global_log .= "Recording size: " . $dir_size . "MB\n\n";
		}

		// Remove recording directory
		$err = remove_file_ifexists($remove_path);
		if ( !$err['code'] ) $debug->log($jconf['log_dir'], $myjobid . ".log", $err['message'] . "\n\nCommand:\n" . $err['command'] . "\n\nOutput:\n" . $err['command_output'], $sendmail = true);

		// Update status fields
		update_db_recording_status($recording['id'], $jconf['dbstatus_deleted']);
		update_db_masterrecording_status($recording['id'], $jconf['dbstatus_deleted']);
		update_db_mobile_status($recording['id'], $jconf['dbstatus_deleted']);
		if ( !empty($recording['contentmasterstatus']) ) {
			update_db_content_status($recording['id'], $jconf['dbstatus_deleted']);
			update_db_mastercontent_status($recording['id'], $jconf['dbstatus_deleted']);
		}

		$app->watchdog();
		$recordings->MoveNext();
	}

	$global_log .= "\nTotal size removed: " . round($size_toremove / 1024 / 1024, 2) . "MB\n";

	$duration = time() - $start_time;
	$hms = secs2hms($duration);
	$debug->log($jconf['log_dir'], $myjobid . ".log", "File remove finished in " . $hms . " time.\n\nSummary:\n\n" . $global_log, $sendmail = true);

} // End of removing recordings

// Close DB connection if open
if ( $db_close ) {
	$db->close();
}

$app->watchdog();
	
exit;

// *************************************************************************
// *						function query_docnew()						   *
// *************************************************************************
// Description: queries next uploaded document from attached_documents
// INPUTS:
//	- AdoDB DB link in $db global variable
// OUTPUTS:
//	- Boolean:
//	  o FALSE: no pending job for conversion
//	  o TRUE: job is available for conversion
//	- $recording: recording_element DB record returned in global $recording variable
function query_recordings2remove(&$recordings) {
 global $jconf, $db, $app;

	$node = $app->config['node_sourceip'];

	$query = "
		SELECT
			a.id,
			a.userid,
			a.status,
			a.masterstatus,
			a.contentstatus,
			a.contentmasterstatus,
			a.mastersourceip,
			b.email,
			c.id as organizationid,
			c.domain
		FROM
			recordings as a,
			users as b,
			organizations as c
		WHERE
			a.status = \"" . $jconf['dbstatus_markedfordeletion'] . "\" AND
			a.userid = b.id AND
			a.mastersourceip = '" . $node . "' AND
			b.organizationid = c.id
	";

	try {
		$recordings = $db->Execute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $jconf['jobid_file_remove'] . ".log", "[ERROR] SQL query failed.\n\n" . trim($query), $sendmail = true);

		return FALSE;
	}

	// Check if pending job exsits
	if ( $recordings->RecordCount() < 1 ) {
		return FALSE;
	}

	return TRUE;
}

?>