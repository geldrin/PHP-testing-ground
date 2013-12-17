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
				access,
				attached_documents,
				categories,
				channels,
				channels_recordings,
				channel_types,
				comments,
				contents,
				contributors,
				contributors_jobs,
				contributors_roles,
				contributor_images,
				departments,
				genres,
				groups,
				groups_members,
				group_invitations,
				help_contents,
				languages,
				livefeeds,
				livefeed_chat,
				livefeed_streams,
				organizations,
				organizations_news,
				recordings,
				recordings_categories,
				recordings_genres,
				recording_links,
				roles,
				strings,
				subtitles,
				uploads,
				users,
				users_invitations
		");
	} catch( exception $err ) {
		$debug->log($jconf['log_dir'], $jconf['jobid_maintenance'] . ".log", "[ERROR] SQL query failed. Query:\n\n" . trim($query), $sendmail = true);
		return FALSE;
	}

	return TRUE;
}

function uploads_maintenance() {
global $db, $app, $jconf, $debug;

	$chunkpath = $app->config['chunkpath'];

	$uploadids = array();
	// vegig nezzuk a konyvtarban levo osszes filet
	foreach (new \DirectoryIterator( $chunkpath ) as $fileinfo ) {

		// az esetleges konyvtarak es . vagy .. filok nem erdekelnek minket
		if( $fileinfo->isDot() or $fileinfo->isDir() )
			continue;

		// megkapjuk a filenevbol az uploads.id-t
		if ( !preg_match( '/^(\d+)\..*$/', $fileinfo->getFilename(), $match ) )
			continue;

		$uploadids[] = $match[1];

	}

	if ( empty( $uploadids ) )
		return true;

	try {
		// minden 1 hetnel regebbi filet aminel nem volt aktivitas torlunk
		// nem nezzuk a statust sehol mert a feltoltes tobb helyen is elhasalhat
		// peldaul: amikor a file feltoltodik es a status = completed, de elhasal
		// az analizalasnal es nem kerul atmozgatasra
		// csak ott nezzuk a statust ahol a user direkt torolte (es azt toroljuk
		// minel elobb)
		$query = "
			SELECT *
			FROM uploads
			WHERE
				(
					id IN('" . implode("', '", $uploadids ) . "') AND
					timestamp < DATE_SUB(NOW(), INTERVAL 1 WEEK)
				) OR
				status = '" . $jconf['dbstatus_markedfordeletion'] . "'
		";
		$uploads = $db->getArray( $query );
	} catch(\Exception $err ) {
		$debug->log($jconf['log_dir'], $jconf['jobid_maintenance'] . ".log", "[ERROR] SQL query failed. Query:\n\n" . trim($query), $sendmail = true);
		return FALSE;
	}

	foreach( $uploads as $upload ) {

		$filepath =
			$chunkpath . $upload['id'] . '.' .
			\Springboard\Filesystem::getExtension( $upload['filename'] )
		;

		if ( !file_exists( $filepath ) ) {
			$debug->log($jconf['log_dir'], $jconf['jobid_maintenance'] . ".log", "[ERROR] Uploaded chunk not found. File: " . $filepath . "\n\n" . print_r($upload, TRUE), $sendmail = true);
			continue;
		}

		if ( !unlink( $filepath ) ) {
			$debug->log($jconf['log_dir'], $jconf['jobid_maintenance'] . ".log", "[ERROR] Uploaded file chunk cannot be deleted. File: " . $filepath . "\n\n" . print_r($upload, TRUE), $sendmail = true);
			continue;
		}

		try {
			$query = "
				UPDATE uploads
				SET status = 'deleted'
				WHERE id = '" . $upload['id'] . "'
			";
			$db->execute( $query );
		} catch(\Exception $err ) {
			$debug->log($jconf['log_dir'], $jconf['jobid_maintenance'] . ".log", "[ERROR] SQL query failed. Query:\n\n" . trim($query), $sendmail = true);
			return FALSE;
		}

	}

	return TRUE;

}

?>