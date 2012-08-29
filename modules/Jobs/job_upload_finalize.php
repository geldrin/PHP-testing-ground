<?php
// Document finalize job

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
$myjobid = $jconf['jobid_upload_finalize'];

// Log related init
$debug = Springboard\Debug::getInstance();
$debug->log($jconf['log_dir'], $myjobid . ".log", "Upload finalize job started", $sendmail = false);

// Check operating system - exit if Windows
if ( iswindows() ) {
	echo "ERROR: Non-Windows process started on Windows platform\n";
	exit;
}

// Start an infinite loop - exit if any STOP file appears
while( !is_file( $app->config['datapath'] . 'jobs/job_upload_finalize.stop' ) and !is_file( $app->config['datapath'] . 'jobs/all.stop' ) ) {

	clearstatcache();

    while ( 1 ) {

		$app->watchdog();
	
		$db_close = FALSE;
		$sleep_length = $jconf['sleep_short'];

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

		// Initialize log for closing message and total duration timer
		$global_log = "";
		$start_time = time();

		$doc = array();
		$docs = array();

		// Query next job - exit if none
		if ( !query_docnew($docs) ) break;

		while ( !$docs->EOF ) {

		// Start global log
//		$global_log .= "Live feed: " . $vcr['feed_name'] . " (ID = " . $vcr['feed_id'] . ")\n";

			$doc = array();
			$doc = $docs->fields;

var_dump($doc);

			if ( !move_file_to_storage($doc) ) {
				update_db_attachment_status($doc['id'], $jconf['dbstatus_copyfromfe_err']);
			}

			$app->watchdog();
			$docs->MoveNext();
		}

echo $global_log;
exit;

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
function query_docnew(&$docs) {
global $jconf, $db;

  $query = "
    SELECT
		a.id,
		a.recordingid,
		a.userid,
		a.title,
		a.masterfilename,
		a.masterextension,
		a.status,
		a.sourceip,
		b.email
	FROM
		attached_documents as a,
		users as b
	WHERE
		status = \"" . $jconf['dbstatus_uploaded'] . "\" AND
		a.userid = b.id
	";
//		a.sourceip = '" . $jconf['node'] . "' AND

	try {
		$docs = $db->Execute($query);
	} catch (exception $err) {
		log_document_conversion(0, 0, $jconf['jobid_upload_finalize'], $jconf['dbstatus_init'], "[ERROR] Cannot query next uploaded document. SQL query failed.", trim($query), $err, 0, TRUE);
		return FALSE;
	}

	// Check if pending job exsits
	if ( $docs->RecordCount() < 1 ) {
		return FALSE;
	}

	return TRUE;
}

function move_file_to_storage(&$doc) {
global $jconf, $app;

	update_db_attachment_status($doc['id'], $jconf['dbstatus_copyfromfe']);

	// Uploaded documents directory
	$uploadpath = $app->config['uploadpath'] . "attachments/";
	$base_filename = $doc['id'] . "." . $doc['masterextension'];
	$fname = $uploadpath . $base_filename;

	$filesize = filesize($fname);
	if ( !file_exists($fname) or $filesize <= 0 ) {
		log_document_conversion($doc['id'], $doc['recordingid'], $jconf['jobid_upload_finalize'], $jconf['dbstatus_copyfromfe'], "ERROR: Uploaded file does not exist or filesize invalid.", "-", "-", 0, TRUE);
		return FALSE;
	}

	// Target directory
	$targetpath = $app->config['recordingpath'] . ( $doc['recordingid'] % 1000 ) . "/" . $doc['recordingid'] . "/attachments/";
	$fname_target = $targetpath . $base_name;

echo "t: " . $targetpath . "\n";

	$available_disk = floor(disk_free_space($targetpath));
	if ( $available_disk < $filesize * 10 ) {
		log_document_conversion($doc['id'], $doc['recordingid'], $jconf['jobid_upload_finalize'], $jconf['dbstatus_copyfromfe'], "ERROR: No space on target device. Only " . ( round($available_disk / 1024 / 1024, 2) ) . " MB left.", "-", "-", 0, TRUE);
		return FALSE;
	}

echo "fs = " . $filesize . "\n";
echo "ad = " . $available_disk . "\n";

exit;

	// Prepare attachments directory on storage
	if ( !file_exists($targetpath) ) {
		$err = create_directory($targetpath . "attachments/");
		if ( !$err['code'] ) {
			log_document_conversion($doc['id'], $doc['recordingid'], $jconf['jobid_upload_finalize'], $jconf['dbstatus_copyfromfe'], $err['message'], $err['command'], $err['result'], 0, TRUE);
			return FALSE;
		}
	}

	$time_start = time();
	$err = copy($fname, $fname_target);
	$duration = time() - $time_start;
	$mins_taken = round( $duration / 60, 2);
	if ( !$err ) {
		log_document_conversion($doc['id'], $doc['recordingid'], $jconf['jobid_upload_finalize'], "-", "[ERROR] Cannot copy attachment file to storage", "php: copy(\"" . $fname . "\",\"" . $fname_target . "\")", $err, $duration, TRUE);
		return FALSE;
	}
	chmod($fname_target, 0664);

	// Remove original attachment file from front-end location
/*	$err = remove_file_ifexists($fname);
	if ( !$err['code'] ) {
		log_document_conversion($doc['id'], $doc['recordingid'], $jconf['jobid_upload_finalize'], "-", "[ERROR] Cannot remove attachment file from upload area.\n\n" . $err['message'], $err['command'], $err['result'], 0, TRUE);
	} else {
		log_document_conversion($doc['id'], $doc['recordingid'], $jconf['jobid_upload_finalize'], "-", "[OK] Attachment file moved to storage (in " . $mins_taken . " mins)", "php: copy(\"" . $fname . "\",\"" . $fname_target . "\")", $err, $duration, FALSE);
	}
*/

	update_db_attachment_status($doc['id'], $jconf['dbstatus_copyfromfe_ok']);

	return TRUE;
}

?>