<?php
// Upload finalize job handling:
// - Uploaded documents (attached documents)
// - User avatar images

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

//update_db_attachment_status(1, $jconf['dbstatus_uploaded']);
//update_db_attachment_status(2, $jconf['dbstatus_uploaded']);

		// Attached documents: query pending uploads
/*		$docs = array();
		if ( query_docnew($docs) ) {

			while ( !$docs->EOF ) {

				$doc = array();
				$doc = $docs->fields;

				$global_log .= "ID: " . $doc['id'] . " (RECORDING ID: " . $doc['recordingid'] . ")\n";
				$global_log .= "User: " . $doc['email'] . " (domain: " . $doc['domain'] . ")\n";

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

				update_db_attachment_status($doc['id'], $jconf['dbstatus_copyfromfe']);

				// Move file to storage
				$err = move_uploaded_file_to_storage($fname, $fname_target);
				if ( !$err ) {
					update_db_attachment_status($doc['id'], $jconf['dbstatus_copyfromfe_err']);
					$global_log .= $err['message'] . "\n\n";
					log_document_conversion($doc['id'], $doc['recordingid'], $jconf['jobid_upload_finalize'], $jconf['dbstatus_copyfromfe'], $err['message'], $err['command'], $err['result'], $err['duration'], TRUE);
				} else {
					$global_log .= "Status: [OK] Document moved in " . $err['duration'] . " seconds.\n\n";
					update_db_attachment_status($doc['id'], $jconf['dbstatus_copystorage_ok']);
				}

				$app->watchdog();
				$docs->MoveNext();
			}

			$duration = time() - $start_time;
			$hms = secs2hms($duration);
			log_document_conversion(0, 0, $jconf['jobid_upload_finalize'], "-", "Document finalize finished in " . $hms . " time.\n\nSummary:\n\n" . $global_log, "-", "-", $duration, TRUE);

		} // End of attached document finalize
*/

		// User avatars: handle uploaded avatars
		$global_log = "Moving avatars to storage:\n\n";
		$start_time = time();
		$avatars = array();
		if ( query_user_avatars($avatars) ) {

			while ( !$avatars->EOF ) {

				$avatar = array();
				$avatar = $avatars->fields;

				$global_log .= "ID: " . $avatar['userid'] . "\n";
				$global_log .= "User: " . $avatar['email'] . " (domain: " . $avatar['domain'] . ")\n";

				// Uploaded document
				$uploadpath = $app->config['uploadpath'] . "useravatars/";
				$tmp = explode(".", $avatar['avatarfilename'], 2);
				$extension = $tmp[1];
				$base_filename = $avatar['userid'] . "." . $extension;
				$fname = $uploadpath . $base_filename;
				$avatar['path_source'] = $fname;

				// Target file
				$targetpath = $app->config['useravatarpath'] . ( $avatar['userid'] % 1000 ) . "/" . $avatar['userid'] . "/avatar/";
				$fname_target = $targetpath . $base_filename;
				$avatar['path_target'] = $fname_target;

				// Log file path information
				$global_log .= "Source path: " . $avatar['path_source'] . "\n";
				$global_log .= "Target path: " . $avatar['path_target'] . "\n";

var_dump($avatar);

exit;

// status = dbstatus_copyfromfe

				// Move file to storage
				$err = move_uploaded_file_to_storage($avatar['path_source'], $avatar['path_target']);
				if ( !$err ) {
// status = dbstatus_copyfromfe_err
					$global_log .= $err['message'] . "\n\n";
					log_document_conversion($doc['id'], $doc['recordingid'], $jconf['jobid_upload_finalize'], $jconf['dbstatus_copyfromfe'], $err['message'], $err['command'], $err['result'], $err['duration'], TRUE);
				} else {
// status = dbstatus_copystorage_ok
					$global_log .= "Status: [OK] Document moved in " . $err['duration'] . " seconds.\n\n";
				}


/*
try {
	\Springboard\Image::resizeAndCropImage($fname_target, 36, 36, 'middle');
} catch (exception $err) {
	$err['message'] = "[ERROR] File " . $fname_target . " resizeAndCropImage() failed to 36x36.\n";
}
*/

				$app->watchdog();
				$avatars->MoveNext();

			}

			$duration = time() - $start_time;
			$hms = secs2hms($duration);
			log_document_conversion(0, 0, $jconf['jobid_upload_finalize'], "-", "Avatar finalize finished in " . $hms . " time.\n\nSummary:\n\n" . $global_log, "-", "-", $duration, TRUE);

		} // End of avatar finalize

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

// *************************************************************************
// *					function query_user_avatars()					   *
// *************************************************************************
// Description: queries pending user avatars
// INPUTS:
//	- AdoDB DB link in $db global variable
// OUTPUTS:
//	- Boolean:
//	  o FALSE: no pending job for conversion
//	  o TRUE: job is available for conversion
function query_user_avatars(&$avatars) {
global $jconf, $db, $app;

//	$node = $app->config['node_sourceip'];

	$query = "
		SELECT
			a.id as userid,
			a.nickname,
			a.email,
			a.avatarfilename,
			a.avatarstatus,
			a.organizationid,
			b.domain
		FROM
			users as a,
			organizations as b
		WHERE
			a.avatarstatus = \"" . $jconf['dbstatus_uploaded'] . "\" AND
			a.organizationid = b.id
	";

	try {
		$avatars = $db->Execute($query);
	} catch (exception $err) {
		log_document_conversion(0, 0, $jconf['jobid_upload_finalize'], $jconf['dbstatus_init'], "[ERROR] Cannot query user avatars. SQL query failed.", trim($query), $err, 0, TRUE);
		return FALSE;
	}

	// Check if pending job exsits
	if ( $avatars->RecordCount() < 1 ) {
		return FALSE;
	}

	return TRUE;
}

function move_uploaded_file_to_storage($fname, $fname_target) {
global $jconf;

	$err = array();
	$err['code'] = FALSE;
	$err['result'] = 0;
	$err['duration'] = 0;
	$err['message'] = "-";
	$err['command'] = "-";

	// Check source file and its filesize
	if ( !file_exists($fname) ) {
		$err['message'] = "[ERROR] Uploaded file does not exist.";
		$err['code'] = FALSE;
		return $err;
	}

	// Check filesize
	$filesize = filesize($fname);
	if ( $filesize <= 0 ) {
		$err['message'] = "[ERROR] Uploaded file has invalid size (" . $filesize . ").";
		$err['code'] = FALSE;
		return $err;
	}

	// Check available disk space
	$available_disk = floor(disk_free_space($app->config['recordingpath']));
	if ( $available_disk < $filesize * 10 ) {
		$err['message'] = "[ERROR] No space on target device. Only " . ( round($available_disk / 1024 / 1024, 2) ) . " MB left.";
		$err['code'] = FALSE;
		return $err;
	}

	// Check if target file exists
	if ( file_exists($fname_target) ) {
		$err['message'] = "[ERROR] Target file " . $fname_target . " already exists.";
		$err['code'] = FALSE;
		return $err;
	}

	$path_parts = pathinfo($fname_target);
	$targetpath = $path_parts['dirname'] . "/";

	// Prepare target directory on storage
	if ( !file_exists($targetpath) ) {
		$err_tmp = create_directory($targetpath);
		if ( !$err_tmp['code'] ) {
			$err['message'] = $err_tmp['message'];
			$err['command'] = $err_tmp['command'];
			$err['result'] = $err_tmp['result'];
			$err['code'] = FALSE;
			return $err;
		}
	}

	// Copy file
	$time_start = time();
	$err_tmp = copy($fname, $fname_target);
	$err['duration'] = time() - $time_start;
	if ( !$err_tmp ) {
		$err['message'] = "[ERROR] Cannot copy file to storage.";
		$err['command'] = "php: move(\"" . $fname . "\",\"" . $fname_target . "\")";
		$err['result'] = $err_tmp;
		$err['code'] = FALSE;
		return $err;
	}

	// File access. Set user/group to "conv:conv" and file access rights to "664"
	$command = "";
	$command .= "chmod -f " . $jconf['file_access']	. " " . $fname_target . " ; ";
	$command .= "chown -f " . $jconf['file_owner']	. " " . $fname_target . " ; ";
	exec($command, $output, $result);
	$output_string = implode("\n", $output);
	if ( $result != 0 ) {
		$err['message'] = "[ERROR] Cannot stat file on storage. Failed command:\n\n" . $command;
		$err['command'] = $command;
		$err['result'] = $result;
	}

	// Remove original file from front-end location
	$err_tmp = remove_file_ifexists($fname);
	if ( !$err_tmp['code'] ) {
		$err['message'] = $err_tmp['message'];
		$err['command'] = $err_tmp['command'];
		$err['result'] = $err_tmp['result'];
	}

	$err['code'] = TRUE;

	return $err;
}

?>