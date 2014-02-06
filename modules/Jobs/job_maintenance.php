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

// Contract data: should be retrived from DB later, when contract description database is implemented.
$org_contracts = array(
	0	=> array(
			'orgid' 					=> 200,
			'name'						=> "Conforg",
			'price_peruser'				=> 2000,
			'currency'					=> "HUF",
			'listfromdate'				=> null,
			'generateduservaliditydays'	=> 31
		),
	1	=> array(
			'orgid' 					=> 222,
			'name'						=> "Infoszféra",
			'price_peruser'				=> 2000,
			'currency'					=> "HUF",
			'listfromdate'				=> "2013-12-01 00:00:00",
			'generateduservaliditydays'	=> 31
		),
	2	=> array(
			'orgid' 					=> 282,
			'name'						=> "IIR",
			'price_peruser'				=> 2000,
			'currency'					=> "HUF",
			'listfromdate'				=> null,
			'generateduservaliditydays'	=> 31
		)
);

// Establish database connection
$db = null;
$db = db_maintain();

//$err = users_setvalidity($org_contracts);

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

function users_setvalidity($org_contracts) {
 global $db, $debug;

	for ($i = 0; $i < count($org_contracts); $i++ ) {

		// Query: users that generated, already logged in and should have a 31-days of validity but not yet set
		$query = "
			SELECT
				u.id,
				u.email,
				u.firstloggedin,
				u.lastloggedin,
				u.timestampdisabledafter
			FROM
				users as u
			WHERE
				u.organizationid = " . $org_contracts[$i]['orgid'] . " AND
				u.isusergenerated = 1 AND
				u.firstloggedin IS NOT NULL AND
				u.timestampdisabledafter IS NULL
			ORDER BY
				u.firstloggedin";

		unset($users);

		try {
			$users = $db->Execute($query);
		} catch (exception $err) {
			$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed.\n" . trim($query) . "\n" . $err . "\n", $sendmail = TRUE);
			exit -1;
		}

		// Is user list empty for this organiztion?
		if ( $users->RecordCount() < 1 ) {
			continue;
		}

		$users_num = 0;
		while ( !$users->EOF ) {

			$user = array();
			$user = $users->fields;

			echo $user['id'] . "," . $user['email'] . ",1st:" . $user['firstloggedin'] . ",last:" . $user['lastloggedin'] . ",dis:" . $user['timestampdisabledafter'] . "\n";

			// User is not yet logged in
/*			if ( empty($user['firstloggedin']) and empty($user['lastloggedin']) ) {
				echo "notyetloggedin\n";
				continue;
			}
*/

			$user_firstloggedin = strtotime($user['firstloggedin']);

			// If disable timestamp is not set
			if ( empty($user['timestampdisabledafter']) ) {
				$user_validitytime = date("Y-m-d H:i:s", $user_firstloggedin + $org_contracts[$i]['generateduservaliditydays'] * 24 * 3600);

/*				if ( $user_firstloggedin < strtotime("2014-01-01 00:00:00") ) {
					$user_validitytime = "2014-01-31 23:59:59";
				}
*/

				echo "newvaliditytime: " . $user_validitytime . "\n";

				$query = "
					UPDATE
						users as u
					SET
						u.timestampdisabledafter = \"" . $user_validitytime . "\"
					WHERE
						u.id = " . $user['id'];
/*
				try {
					$rs = $db->Execute($query);
				} catch (exception $err) {
					$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed.\n" . trim($query) . "\n" . $err . "\n", $sendmail = TRUE);
					exit -1;
				}
*/
			}

			$users_num++;
			$users->MoveNext();
		}

	}
}

?>