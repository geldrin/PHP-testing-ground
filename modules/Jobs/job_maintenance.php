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

$err = mailqueue_cleanup();

$err = statscounter_reset();

$err = db_maintenance();

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


?>