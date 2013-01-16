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

// Start an infinite loop - exit if any STOP file appears
while( !is_file( $app->config['datapath'] . 'jobs/job_remove_files.stop' ) and !is_file( $app->config['datapath'] . 'jobs/all.stop' ) ) {

	clearstatcache();

    while ( 1 ) {

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
			$sleep_length = 15 * 60;
			break;
		}
		$db_close = TRUE;

		// Initialize log for closing message and total duration timer
		$global_log = "Removing document(s) from storage:\n\n";
		$start_time = time();

//update_db_attachment_status(1, $jconf['dbstatus_uploaded']);
//update_db_attachment_status(2, $jconf['dbstatus_uploaded']);

		// Attached documents: query pending uploads
		$recordings = array();
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

// check + log filesize
// rm -r $path
$dir_size = $directory_size($remove_path);

echo $recording['id'] . ": " . $dir_size;

				$app->watchdog();
				$recordings->MoveNext();
			}

			$duration = time() - $start_time;
			$hms = secs2hms($duration);
//			log_document_conversion(0, 0, $jconf['jobid_upload_finalize'], "-", "File remove finished in " . $hms . " time.\n\nSummary:\n\n" . $global_log, "-", "-", $duration, TRUE);

		} // End of attached document finalize

		break;
	}	// End of while(1)

	// Close DB connection if open
	if ( $db_close ) {
		$db->close();
	}

	$app->watchdog();

	sleep( $sleep_length );
	
}	// End of outer while

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
//			a.sourceip = '" . $node . "' AND

	$query = "
		SELECT
			a.id,
			a.userid,
			a.status,
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
a.id = 26 and
			b.organizationid = c.id
	";

//echo $query . "\n";

	try {
		$recordings = $db->Execute($query);
	} catch (exception $err) {
//!!!!
//		log_document_conversion(0, 0, $jconf['jobid_file_remove'], $jconf['dbstatus_init'], "[ERROR] Cannot query next file to be removed. SQL query failed.", trim($query), $err, 0, TRUE);
		return FALSE;
	}

	// Check if pending job exsits
	if ( $recordings->RecordCount() < 1 ) {
		return FALSE;
	}

	return TRUE;
}

?>