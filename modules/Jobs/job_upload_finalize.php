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
		$global_log = "Moving document(s) to storage:\n\n";
		$start_time = time();

		$doc = array();
		$docs = array();

//update_db_attachment_status(1, $jconf['dbstatus_uploaded']);
//update_db_attachment_status(2, $jconf['dbstatus_uploaded']);

		// Query next job - exit if none
		if ( !query_docnew($docs) ) break;

		while ( !$docs->EOF ) {

			$doc = array();
			$doc = $docs->fields;

			$global_log .= "ID: " . $doc['id'] . " (RECORDING ID: " . $doc['recordingid'] . ")\n";
			$global_log .= "User: " . $doc['email'] . " (domain: " . $doc['domain'] . ")\n";

			$err = move_file_to_storage($doc);
			if ( !$err ) {
				update_db_attachment_status($doc['id'], $jconf['dbstatus_copyfromfe_err']);
			} else {
				update_db_attachment_status($doc['id'], $jconf['dbstatus_copystorage_ok']);
			}

			$app->watchdog();
			$docs->MoveNext();
		}

		$duration = time() - $start_time;
		$hms = secs2hms($duration);
		log_document_conversion(0, 0, $jconf['jobid_upload_finalize'], "-", "Document finalize finished in " . $hms . " time.\n\nSummary:\n\n" . $global_log, "-", "-", $duration, TRUE);

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
global $jconf, $db, $app;

	$node = $app->config['node_sourceip'];

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
			b.email,
			c.id as organizationid,
			c.domain
		FROM
			attached_documents as a,
			users as b,
			organizations as c
		WHERE
			status = \"" . $jconf['dbstatus_uploaded'] . "\" AND
			a.sourceip = '" . $node . "' AND
			a.userid = b.id AND
			b.organizationid = c.id
	";

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
global $jconf, $app, $global_log;

	update_db_attachment_status($doc['id'], $jconf['dbstatus_copyfromfe']);

	// Uploaded document
	$uploadpath = $app->config['uploadpath'] . "attachments/";
	$base_filename = $doc['id'] . "." . $doc['masterextension'];
	$fname = $uploadpath . $base_filename;
	$doc['path_source'] = $fname;

	// Target file
	$targetpath = $app->config['recordingpath'] . ( $doc['recordingid'] % 1000 ) . "/" . $doc['recordingid'] . "/attachments/";
	$fname_target = $targetpath . $base_filename;
	$doc['path_target'] = $fname_target;

	// Log file path information
	$global_log .= "Source path: " . $doc['path_source'] . "\n";
	$global_log .= "Target path: " . $doc['path_target'] . "\n";

	// Check source file and its filesize
	if ( !file_exists($fname) ) {
		$msg = "[ERROR] Uploaded file does not exist.";
		$global_log .= $msg . "\n\n";
		log_document_conversion($doc['id'], $doc['recordingid'], $jconf['jobid_upload_finalize'], $jconf['dbstatus_copyfromfe'], $msg, "-", "-", 0, TRUE);
		return FALSE;
	}

	// Check filesize
	$filesize = filesize($fname);
	if ( $filesize <= 0 ) {
		$msg = "[ERROR] Uploaded file has invalid size (" . $filesize . ").";
		$global_log .= $msg . "\n\n";
		log_document_conversion($doc['id'], $doc['recordingid'], $jconf['jobid_upload_finalize'], $jconf['dbstatus_copyfromfe'], $msg, "-", "-", 0, TRUE);
		return FALSE;
	}

	// Check available disk space
	$available_disk = floor(disk_free_space($app->config['recordingpath']));
	if ( $available_disk < $filesize * 10 ) {
		$msg = "[ERROR] No space on target device. Only " . ( round($available_disk / 1024 / 1024, 2) ) . " MB left.";
		$global_log .= $msg . "\n\n";
		log_document_conversion($doc['id'], $doc['recordingid'], $jconf['jobid_upload_finalize'], $jconf['dbstatus_copyfromfe'], $msg, "-", "-", 0, TRUE);
		return FALSE;
	}

	// Check if target file exists
	if ( file_exists($fname_target) ) {
		$msg = "[ERROR] Target file " . $fname_target . " already exists.";
		$global_log .= $msg . "\n\n";		
		log_document_conversion($doc['id'], $doc['recordingid'], $jconf['jobid_upload_finalize'], $jconf['dbstatus_copyfromfe'], $msg, "-", "-", 0, TRUE);
		return FALSE;
	}

	// Prepare attachments directory on storage
	if ( !file_exists($targetpath) ) {
		$err = create_directory($targetpath);
		if ( !$err['code'] ) {
			$global_log .= $err['message'];
			log_document_conversion($doc['id'], $doc['recordingid'], $jconf['jobid_upload_finalize'], $jconf['dbstatus_copyfromfe'], $err['message'], $err['command'], $err['result'], 0, TRUE);
			return FALSE;
		}
	}

	$time_start = time();
	$err = copy($fname, $fname_target);
	$duration = time() - $time_start;
	$mins_taken = round( $duration / 60, 2);
	if ( !$err ) {
		$msg = "[ERROR] Cannot move document file to storage";
		$global_log .= $msg . "\n\n";		
		log_document_conversion($doc['id'], $doc['recordingid'], $jconf['jobid_upload_finalize'], $jconf['dbstatus_copyfromfe'], $msg, "php: move(\"" . $fname . "\",\"" . $fname_target . "\")", $err, $duration, TRUE);
		return FALSE;
	}

	// File access. Set user/group to "conv:conv" and file access rights to "664"
	$command = "";
	$command .= "chmod -f " . $jconf['file_access']	. " " . $fname_target . " ; ";
	$command .= "chown -f " . $jconf['file_owner']	. " " . $fname_target . " ; ";
	exec($command, $output, $result);
	$output_string = implode("\n", $output);
	if ( $result != 0 ) {
		$msg = "[ERROR] Cannot stat document file on storage. Failed command:\n\n" . $command;
		$global_log .= $msg . "\n";
		log_document_conversion($doc['id'], $doc['recordingid'], $jconf['jobid_upload_finalize'], $jconf['dbstatus_copyfromfe'], $msg, $command, $output_string, 0, TRUE);
	}

	// Remove original file from front-end location
	$err = remove_file_ifexists($fname);
	if ( !$err['code'] ) {
		$msg = "[ERROR] Cannot remove attachment file from upload area.";
		$global_log .= $msg . "\n";
		log_document_conversion($doc['id'], $doc['recordingid'], $jconf['jobid_upload_finalize'], $jconf['dbstatus_copyfromfe'], $msg . $err['message'], $err['command'], $err['result'], 0, TRUE);
	}

	$global_log .= "Status: [OK] Document moved in " . $duration . " seconds.\n\n";

	return TRUE;
}

?>