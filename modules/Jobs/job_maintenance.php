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
$db = db_maintain();

// Check failed conversions
$err = checkFailedRecordings();

if ($err['code'] && is_array($err['result'])) {
	$debug->log($jconf['log_dir'], $jconf['jobid_maintenance'] . ".log", $err['message'], $sendmail = true);
	print_r("REPORT SENT.\n");
}

// User validity: maintain for generated users
$err = users_setvalidity();

// Mailqueue maintenance
$err = mailqueue_cleanup();

// Stats counter reset (daily, monthly)
$err = statscounter_reset();

// DB: run optimize table
$err = db_maintenance();

// Upload chunks maintenance
$err = uploads_maintenance();

// Close DB connection if open
if ( is_resource($db->_connectionID) ) $db->close();

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
		return false;
	}

	return true;
}

function statscounter_reset() {
global $app;

	$recordingModel = $app->bootstrap->getModel('recordings');
	$recordingModel->resetStats();

	return true;
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
		return false;
	}

	return true;
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
			$debug->log($jconf['log_dir'], $jconf['jobid_maintenance'] . ".log", "[ERROR] Uploaded chunk not found. File: " . $filepath . "\n\n" . print_r($upload, true), $sendmail = true);
			continue;
		}

		if ( !unlink( $filepath ) ) {
			$debug->log($jconf['log_dir'], $jconf['jobid_maintenance'] . ".log", "[ERROR] Uploaded file chunk cannot be deleted. File: " . $filepath . "\n\n" . print_r($upload, true), $sendmail = true);
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
			return false;
		}

	}

	return true;

}

function users_setvalidity() {
global $db, $debug, $jconf, $app;

	// Contract data: Should come from DB!
	include_once('subscriber_descriptor.php');

	$myjobid = $jconf['jobid_maintenance'];

	for ($i = 0; $i < count($org_contracts); $i++ ) {

		// Query: users that generated, already logged in and should have a validity but not yet set
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
			$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed.\n" . trim($query) . "\n" . $err . "\n", $sendmail = true);
		}

		$users_num = 0;
		while ( !$users->EOF ) {

			$user = array();
			$user = $users->fields;

			$user_firstloggedin = strtotime($user['firstloggedin']);

			// If disable timestamp is not set
			if ( empty($user['timestampdisabledafter']) ) {

				$user_validity_days = $org_contracts[$i]['generateduservaliditydays'];
				if ( preg_match("/^promo[0-9][0-9][0-9][0-9]@*/", $user['email']) ) $user_validity_days = $org_contracts[$i]['promouservaliditydays'];

				$user_validitytime = date("Y-m-d H:i:s", $user_firstloggedin + $user_validity_days * 24 * 3600);

				$query = "
					UPDATE
						users as u
					SET
						u.timestampdisabledafter = '" . $user_validitytime . "'
					WHERE
						u.id = " . $user['id'];

				try {
					$rs = $db->Execute($query);
				} catch (exception $err) {
					$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed.\n" . trim($query) . "\n" . $err . "\n", $sendmail = true);
				}

				// Log change in user validity
				$log_msg = "User validity set: uid=" . $user['id'] . "," . $user['email'] . ",firstloggedin='" . $user['firstloggedin'] . "',lastloggedin='" . $user['lastloggedin'] . "',disabledafter='" . $user['timestampdisabledafter'] . "',new validity time='" . $user_validitytime . "'\n";
				$debug->log($jconf['log_dir'], $myjobid . ".log", $log_msg, $sendmail = false);

			}

			$users_num++;
			$users->MoveNext();
		}

		// Disable: users that behind their disable date
		$datetimenow = date("Y-m-d H:i:s");

		$query = "
			UPDATE
				users as u
			SET
				disabled = 1
			WHERE
				u.organizationid = " . $org_contracts[$i]['orgid'] . " AND
				u.isusergenerated = 1 AND
				u.disabled = 0 AND
				u.firstloggedin IS NOT NULL AND
				u.timestampdisabledafter < '" . $datetimenow . "'
		";

		try {
			$rs2 = $db->Execute($query);
		} catch (exception $err) {
			$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed.\n" . trim($query) . "\n" . $err . "\n", $sendmail = true);
		}

		// Log disabled user and SQL query
		if ( $db->Affected_Rows() > 0 ) {
			$log_msg = "Users disabled: " . $db->Affected_Rows() . "\n";
			$debug->log($jconf['log_dir'], $myjobid . ".log", $log_msg, $sendmail = false);
		}

	}
}

///////////////////////////////////////////////////////////////////////////////////////////////////
function checkFailedRecordings() {
///////////////////////////////////////////////////////////////////////////////////////////////////
// Collects and generates report from failed recording versions.
//
// Args: none
//
// Return values:
// - code (bool) - The success of the function, TRUE
// - result (arr) - An array of the failed recording versions, FALSE on error or TRUE if everything were okay.
// - message (str) - A string containing the report, or the error message if something goes wrong.
///////////////////////////////////////////////////////////////////////////////////////////////////
	global $db, $jconf;
	
	$ret = array(
		'code'    => false,
		'result'  => false,
		'message' => '',
	);
	
	$data = null;
	$qry = "
		SELECT
			`rv`.`id`,
			`r`.`title`,
			`rv`.`recordingid`,
			`rv`.`status`,
			`rv`.`timestamp`,
			`rv`.`filename`,
			`ep`.`filenamesuffix`,
			`ep`.`filecontainerformat`,
			`ep`.`name`,
			`ep`.`type`,
			`ep`.`shortname`
		FROM
			`recordings_versions` AS `rv`
		LEFT JOIN
			`encoding_profiles` AS `ep`
		ON
			`rv`.`encodingprofileid`=`ep`.`id`
		LEFT JOIN
			`recordings` AS `r`
		ON
			`rv`.`recordingid`=`r`.`id`
		WHERE
			`rv`.`status` LIKE '%failed%'";
	
	try {
		$recordset = $db->Execute($qry);
		
		if ($recordset->RecordCount() < 1) {
			// everything is okay, move along
			$ret['code']    = true;
			$ret['result']  = true;
			$ret['message'] = "OK";
			return $ret;
		}
		
		$data = $recordset->GetArray();
		unset($recordset);
		
		$fldrecs = array();	
		$num_fldrecs = 0;
		$num_fldrvs  = count($data);
		
		foreach ($data as $rv) {
			if (!array_key_exists($rv['recordingid'], $fldrecs)) {
				$fldrecs[$rv['recordingid']] = array();
				$num_fldrecs++;
			}
			$fldrecs[$rv['recordingid']][] = $rv;
		}
		unset($data);
		
		$msg  = "[NOTICE] some conversion(s) have been failed. Please check them manually.\n";
		$msg .= "  - Number of failed recordings: ". $num_fldrecs ."\n";
		$msg .= "  - Number of failed conversions: ". $num_fldrvs .".\n";
		$msg .= "Detailed list:\n" . str_pad("", 100, "-", STR_PAD_BOTH);
		
		foreach ($fldrecs as $rec => $fldrec) {
			$msg .= "\n  Rec #". $rec ." (\"". $fldrec[0]['title'] ."\") - failed conversions: ". count($fldrec) ."\n";
			$n = 1;
			foreach ($fldrec as $fldrv) {
				$recver   = "rec.version = #". $fldrv['id'];
				$filename = "'". ($fldrv['filename'] === null ? ($fldrv['id'] . $fldrv['filenamesuffix'] . $fldrv['filecontainerformat'] . "/null") : $fldrv['filename']) ."'";
				$status   = "status = '". $fldrv['status'] ."'";
				$date     = "timestamp = '". $fldrv['timestamp'] ."'";
				$type     = $fldrv['type'] ." - ". $fldrv['shortname'];
				
				$msg .= "    ". $n++ .".) ". $recver .", ". $filename ." (". $type ."), ". $status .", ". $date ."\n";
			}
		}
		$msg .= "\nThis report was generated on ". date('Y-m-d H:i:s') .".";
		$ret['message'] = $msg;
		$ret['code'] = true;
		$ret['result'] = $fldrecs;
		
		return $ret;
		
	} catch(Exception $e) {
		$ret['message'] = "[ERROR] checkFailedRecordings encountered an error:\n". $e->GetMessage();
		return $ret;
	}
}

?>