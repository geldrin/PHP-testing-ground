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
$app = new Springboard\Application\Cli(BASE_PATH, FALSE);

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
	
		// Establish database connection
		$db = null;
		$db = db_maintain();

		$sleep_length = $jconf['sleep_short'];

		// Initialize log for closing message and total duration timer
		$global_log = "Moving document(s) to storage:\n\n";
		$start_time = time();

//update_db_attachment_status(1, $jconf['dbstatus_uploaded']);
//update_db_attachment_status(2, $jconf['dbstatus_uploaded']);

		// Attached documents: query pending uploads
		$docs = array();
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
				$err = move_uploaded_file_to_storage($fname, $fname_target, FALSE);
				if ( !$err ) {
					update_db_attachment_status($doc['id'], $jconf['dbstatus_copyfromfe_err']);
					$global_log .= $err['message'] . "\n\n";
					log_document_conversion($doc['id'], $doc['recordingid'], $jconf['jobid_upload_finalize'], $jconf['dbstatus_copyfromfe'], $err['message'], $err['command'], $err['result'], $err['duration'], TRUE);
				} else {
					$global_log .= "Status: [OK] Document moved in " . $err['duration'] . " seconds.\n\n";
					update_db_attachment_status($doc['id'], $jconf['dbstatus_copystorage_ok']);

					// Update recording size
					$recording_directory = $app->config['recordingpath'] . ( $doc['recordingid'] % 1000 ) . "/" . $doc['recordingid'] . "/";
					$err = directory_size($recording_directory . "master/");
					$master_filesize = 0;
					if ( $err['code'] ) $master_filesize = $err['value'];
					$err = directory_size($recording_directory);
					$recording_filesize = 0;
					if ( $err['code'] ) $recording_filesize = $err['value'];
					// Update DB
					$update = array(
						'masterdatasize'	=> $master_filesize,
						'recordingdatasize'	=> $recording_filesize
					);
					$recDoc = $app->bootstrap->getModel('recordings');
					$recDoc->select($doc['recordingid']);
					$recDoc->updateRow($update);
				}

				$app->watchdog();
				$docs->MoveNext();
			}

			$duration = time() - $start_time;
			$hms = secs2hms($duration);
			log_document_conversion(0, 0, $jconf['jobid_upload_finalize'], "-", "Document finalize finished in " . $hms . " time.\n\nSummary:\n\n" . $global_log, "-", "-", $duration, FALSE);

		} // End of attached document finalize

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
				$uploadpath = $app->config['useravatarpath'];
				$extension = \Springboard\Filesystem::getExtension( $avatar['avatarfilename'] );
				$base_filename = $avatar['userid'] . "." . $extension;
				$fname = $uploadpath . $base_filename;
				$avatar['path_source'] = $fname;

				// Target file
				$targetpath = $app->config['mediapath'] . "users/" . ( $avatar['userid'] % 1000 ) . "/" . $avatar['userid'] . "/avatar/";
				$fname_target = $targetpath . $base_filename;
				$avatar['path_target'] = $fname_target;

				// Log file path information
				$global_log .= "Source path: " . $avatar['path_source'] . "\n";
				$global_log .= "Target path: " . $avatar['path_target'] . "\n";

				// Status = copying
				update_db_avatar_status($avatar['userid'], $jconf['dbstatus_copyfromfe']);

				// Move file to storage
				$err = move_uploaded_file_to_storage($avatar['path_source'], $avatar['path_target'], TRUE);
				if ( !$err ) {
					// Status = error
					$global_log .= $err['message'] . "\n\n";
					update_db_avatar_status($avatar['userid'], $jconf['dbstatus_copyfromfe_err']);
					log_document_conversion(0, 0, $jconf['jobid_upload_finalize'], $jconf['dbstatus_copyfromfe'], $err['message'], $err['command'], $err['result'], $err['duration'], TRUE);
				} else {
					// Status = OK
					update_db_avatar_status($avatar['userid'], $jconf['dbstatus_copystorage_ok']);
					try {
						\Springboard\Image::resizeAndCropImage($avatar['path_target'], 36, 36, 'middle');
					} catch (exception $err) {
						log_document_conversion(0, 0, $jconf['jobid_upload_finalize'], $jconf['dbstatus_copyfromfe'], $err['message'], $err['command'], $err['result'], $err['duration'], TRUE);
					}
					$global_log .= "Status: [OK] Avatar moved and resized in " . $err['duration'] . " seconds.\n\n";
				}

				$app->watchdog();
				$avatars->MoveNext();

			}

			$duration = time() - $start_time;
			$hms = secs2hms($duration);
			log_document_conversion(0, 0, $jconf['jobid_upload_finalize'], "-", "Avatar finalize finished in " . $hms . " time.\n\nSummary:\n\n" . $global_log, "-", "-", $duration, FALSE);

		} // End of avatar finalize

		// Contributor images: handle selected video index pictures
		$global_log = "Copying contributor images to storage:\n\n";
		$start_time = time();
		$cimages = array();
		if ( query_contributor_images($cimages) ) {

			while ( !$cimages->EOF ) {

				$cimage = array();
				$cimage = $cimages->fields;

				$global_log .= "Contributor ID: " . $cimage['contributorid'] . "\n";

				// Source and destination file
				$source_file = $app->config['mediapath'] . $cimage['indexphotofilename'];
				$contributor_path = "contributors/" . ( $cimage['contributorid'] % 1000 ) . "/" . $cimage['contributorid'] . "/";
				$destination_path = $app->config['mediapath'] . $contributor_path;
				$destination_file = $destination_path . $cimage['id'] . ".jpg";

				// Log file path information
				$global_log .= "Source file: " . $source_file . "\n";
				$global_log .= "Target file: " . $destination_file . "\n";

				// Source file: check
				if ( !file_exists($source_file) ) {
					$msg = "ERROR: index image not found at " . $source_file;
					log_document_conversion(0, 0, $jconf['jobid_upload_finalize'], $jconf['dbstatus_cimage'], $msg, "-", 0, 0, TRUE);
					$global_log .= $msg . "\n\n";
					continue;
				}

				// Create destination directory under "contributors"
				$err = create_directory($destination_path);
				if ( !$err['code'] ) {
					$msg = "ERROR: cannot create directory " . $destination_path;
					log_document_conversion(0, 0, $jconf['jobid_upload_finalize'], $jconf['dbstatus_cimage'], $msg, "-", 0, 0, TRUE);
					$global_log .= $msg . "\n\n";
					continue;
				}

				// Copy file to destination
				$err = copy($source_file, $destination_file);
				if ( !$err ) {
					$msg = "ERROR: cannot copy " . $source_file . " -> " . $destination_file;
					log_document_conversion(0, 0, $jconf['jobid_upload_finalize'], $msg, "-", 0, 0, TRUE);
					$global_log .= $msg . "\n\n";
					continue;
				}

				// Update new path in DB

				// Update DB
				$update = array(
					'indexphotofilename'	=> $contributor_path . $cimage['id'] . ".jpg"
				);
				$cImg = $app->bootstrap->getModel('contributor_images');
				$cImg->select($cimage['id']);
				$cImg->updateRow($update);

				$global_log .= "Status: [OK] Image copied.\n\n";

				$app->watchdog();
				$cimages->MoveNext();

			}

			$duration = time() - $start_time;
			$hms = secs2hms($duration);
			log_document_conversion(0, 0, $jconf['jobid_upload_finalize'], "-", "Contributor images finalize finished in " . $hms . " time.\n\nSummary:\n\n" . $global_log, "-", "-", $duration, FALSE);

		} // End of contributor images finalize

		break;
	} // End of while(1)

	// Close DB connection if open
	if ( is_resource($db->_connectionID) ) $db->close();

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

	$db = db_maintain();

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

	$db = db_maintain();

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

function query_contributor_images(&$cimages) {
global $jconf, $db, $app;

	$db = db_maintain();

	$query = "
		SELECT
			id,
			contributorid,
			indexphotofilename
		FROM
			contributor_images
		WHERE
			indexphotofilename LIKE \"%recordings%\"
	";

	try {
		$cimages = $db->Execute($query);
	} catch (exception $err) {
		log_document_conversion(0, 0, $jconf['jobid_upload_finalize'], $jconf['dbstatus_init'], "[ERROR] Cannot query contributor images. SQL query failed.", trim($query), $err, 0, TRUE);
		return FALSE;
	}

	// Check if pending job exsits
	if ( $cimages->RecordCount() < 1 ) {
		return FALSE;
	}

	return TRUE;
}

?>