<?php
// Job: maintenance 2012/08/28

define('BASE_PATH',	realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');

// Utils
include_once('job_utils_base.php');
include_once('job_utils_log.php');

set_time_limit(0);

// Init
$app = new Springboard\Application\Cli(BASE_PATH, PRODUCTION);

// Load jobs configuration file
$app->loadConfig('modules/Jobs/config_jobs.php');
$jconf = $app->config['config_jobs'];
$myjobid = $jconf['jobid_maintenance'];

// Log related init
$debug = Springboard\Debug::getInstance();
$debug->log($jconf['log_dir'], $myjobid . ".log", "Maintenance job started", $sendmail = false);

// Check operating system - exit if Windows
if ( iswindows() ) {
	echo "ERROR: Non-Windows process started on Windows platform\n";
	exit;
}

// Start an infinite loop - exit if any STOP file appears
if ( is_file( $app->config['datapath'] . 'jobs/job_maintenance.stop' ) or is_file( $app->config['datapath'] . 'jobs/all.stop' ) ) exit;

clearstatcache();
	
// Establish database connection
try {
	$db = $app->bootstrap->getAdoDB();
} catch (exception $err) {
	// Send mail alert, sleep for 15 minutes
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] No connection to DB (getAdoDB() failed). Error message:\n" . $err, $sendmail = true);
	// Sleep 15 mins then resume
	exit;
}


// Mailqueue maintenance
$err = mailqueue_cleanup();

// Stats counter reset (daily, monthly)
$err = statscounter_reset();

// DB: run optimize table
$err = db_maintenance();

// Upload chunks maintenance
$err = uploads_maintenance();


$db->close();

exit;

// *************************************************************************
// *					function mailqueue_maintenance()			   	   *
// *************************************************************************
// Description: remove sent mails from mailqueue
// INPUTS:
//	- AdoDB DB link in $db global variable
// OUTPUTS:
//	- Boolean:
//	  o FALSE: unsuccessful operation
//	  o TRUE: successful operation
function mailqueue_cleanup() {
global $db, $jconf;

	$mail_time = date("Y-m-d H:i:00", time() - (60 * 60 * 24 * 30));

	$query = "
		DELETE FROM
			mailqueue
		WHERE
			status = \"sent\" AND
			timesent < \"" . $mail_time . "\"";

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $jconf['jobid_maintenance'] . ".log", "[ERROR] SQL query failed. Query:\n\n" . trim($query), $sendmail = true);
		return FALSE;
	}

	return TRUE;
}

function statscounter_reset() {
global $app;

	$recordingModel = $app->bootstrap->getModel('recordings');
	$recordingModel->resetStats();

	return TRUE;
}

// OPTIMIZE TABLE
function db_maintenance() {
global $db, $jconf;

	try {
		$db->Execute("
			OPTIMIZE TABLE
			channels_contributors,
			channels_recordings,
			comments,
			contributors_jobs,
			contributors_roles,
			groups,
			groups_members,
			group_invitations,
			recordings_genres,
			mailqueue,
			access,
			subtitles
		");
	} catch( exception $err ) {
		$debug->log($jconf['log_dir'], $jconf['jobid_maintenance'] . ".log", "[ERROR] SQL query failed. Query:\n\n" . trim($query), $sendmail = true);
		return FALSE;
	}

	return TRUE;
}

function uploads_maintenance() {
global $db, $app, $jconf;

	$chunkpath = $app->config['chunkpath'];

	$files = array();

	// One week
	$date_old = date("Y-m-d H:i:00", time() - (60 * 60 * 24 * 7));
//	$date_old = date("Y-m-d H:i:00");

	$query = "
		SELECT
			id,
			userid,
			filename,
			size,
			chunkcount,
			currentchunk,
			status,
			timestamp
		FROM
			uploads
		WHERE
			( status NOT IN ('completed', 'deleted') AND
			timestamp < '" . $date_old . "' ) OR
			status = '" . $jconf['dbstatus_markedfordeletion'] . "'";

	try {
		$files = $db->Execute($query);
	} catch(exception $err) {
		$debug->log($jconf['log_dir'], $jconf['jobid_maintenance'] . ".log", "[ERROR] SQL query failed. Query:\n\n" . trim($query), $sendmail = true);
		return FALSE;
	}
	
	// Remove file chunks older than a week
	while ( !$files->EOF ) {

		$file = array();
		$file = $files->fields;

		$path_parts = pathinfo($file['filename']);
		$filename = $chunkpath . $file['id'] . "." . $path_parts['extension'];

		if ( file_exists($filename) ) {
			// Remove file
			if ( unlink($filename) ) {
				// Update chunk status
				$query = "
					UPDATE
						uploads
					SET
						status = 'deleted'
					WHERE
						id = " . $file['id'];
				try {
					$db->Execute($query);
				} catch(exception $err) {
					$debug->log($jconf['log_dir'], $jconf['jobid_maintenance'] . ".log", "[ERROR] SQL query failed. Query:\n\n" . trim($query), $sendmail = true);
					return FALSE;
				}
			} else {
				// File does not exist
				$debug->log($jconf['log_dir'], $jconf['jobid_maintenance'] . ".log", "[ERROR] Uploaded file chunk cannot be deleted. File: " . $filename . "\n\n" . print_r($file, TRUE), $sendmail = true);
				continue;
			}
		} else {
		    // File does not exist
		    $debug->log($jconf['log_dir'], $jconf['jobid_maintenance'] . ".log", "[ERROR] Uploaded chunk not found. File: " . $filename . "\n\n" . print_r($file, TRUE), $sendmail = true);
		    continue;
		}

		$files->MoveNext();
	}

	return TRUE;

}

?>