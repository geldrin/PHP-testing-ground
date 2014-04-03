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
$app = new Springboard\Application\Cli(BASE_PATH, false);

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

	// Recording finalization last time
	$finalizedonelasttime = null;

    while ( 1 ) {

		$app->watchdog();
	
		// Establish database connection
		$db = db_maintain();

		$sleep_length = $jconf['sleep_short'];

		// Initialize log for closing message and total duration timer
		$global_log = "Moving document(s) to storage:\n\n";
		$start_time = time();

//updateAttachedDocumentStatus(1, $jconf['dbstatus_uploaded']);
//updateAttachedDocumentStatus(2, $jconf['dbstatus_uploaded']);

		// Attached documents: query pending uploads
		$docs = getUploadedAttachments();
		if ( $docs !== false ) {

			while ( !$docs->EOF ) {
break;
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

				updateAttachedDocumentStatus($doc['id'], $jconf['dbstatus_copyfromfe']);

				// Move file to storage
				$err = move_uploaded_file_to_storage($fname, $fname_target, false);
				if ( !$err ) {
					updateAttachedDocumentStatus($doc['id'], $jconf['dbstatus_copyfromfe_err']);
					$global_log .= $err['message'] . "\n\n";
//					log_document_conversion($doc['id'], $doc['recordingid'], $jconf['jobid_upload_finalize'], $jconf['dbstatus_copyfromfe'], $err['message'], $err['command'], $err['result'], $err['duration'], true);
					$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Attached document id = " . $doc['id'] . " cannot be moved to storage.\nCOMMAND: " . $err['command'] . "\nRESULT: " . $err['result'], $sendmail = true);
				} else {
					$global_log .= "Status: [OK] Document moved in " . $err['duration'] . " seconds.\n\n";
					updateAttachedDocumentStatus($doc['id'], $jconf['dbstatus_copystorage_ok']);

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
			$debug->log($jconf['log_dir'], $myjobid . ".log", "Document finalize finished in " . $hms . " time.\n\nSummary:\n\n" . $global_log, $sendmail = false);

		} // End of attached document finalize

		// User avatars: handle uploaded avatars
		$global_log = "Moving avatars to storage:\n\n";
		$start_time = time();
		$avatars = getUploadedAvatars();
		if ( $avatars !== false ) {

			while ( !$avatars->EOF ) {
break;
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
				updateAvatarStatus($avatar['userid'], $jconf['dbstatus_copyfromfe']);

				// Move file to storage
				$err = move_uploaded_file_to_storage($avatar['path_source'], $avatar['path_target'], true);
				if ( !$err ) {
					// Status = error
					$global_log .= $err['message'] . "\n\n";
					updateAvatarStatus($avatar['userid'], $jconf['dbstatus_copyfromfe_err']);
//					log_document_conversion(0, 0, $jconf['jobid_upload_finalize'], $jconf['dbstatus_copyfromfe'], $err['message'], $err['command'], $err['result'], $err['duration'], true);
					$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Avatar for uid = " . $avatar['userid'] . " cannot be moved to storage.\nCOMMAND: " . $err['command'] . "\nRESULT: " . $err['result'], $sendmail = true);
				} else {
					// Status = OK
					updateAvatarStatus($avatar['userid'], $jconf['dbstatus_copystorage_ok']);
					try {
						\Springboard\Image::resizeAndCropImage($avatar['path_target'], 36, 36, 'middle');
					} catch (exception $err) {
//						log_document_conversion(0, 0, $jconf['jobid_upload_finalize'], $jconf['dbstatus_copyfromfe'], $err['message'], $err['command'], $err['result'], $err['duration'], true);
						$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Avatar for uid = " . $avatar['userid'] . " cannot be resized (/Springboard/Image::resizeAndCropImage).", $sendmail = true);
					}
					$global_log .= "Status: [OK] Avatar moved and resized in " . $err['duration'] . " seconds.\n\n";
				}

				$app->watchdog();
				$avatars->MoveNext();
			}

			$duration = time() - $start_time;
			$hms = secs2hms($duration);
//			log_document_conversion(0, 0, $jconf['jobid_upload_finalize'], "-", "Avatar finalize finished in " . $hms . " time.\n\nSummary:\n\n" . $global_log, "-", "-", $duration, false);
			$debug->log($jconf['log_dir'], $myjobid . ".log", "Avatar finalize finished in " . $hms . " time.\n\nSummary:\n\n" . $global_log, $sendmail = false);

		} // End of avatar finalize

		// Contributor images: handle selected video index pictures
		$global_log = "Copying contributor images to storage:\n\n";
		$start_time = time();
		$cimages = getSelectedContributorImages();
		if ( $cimages !== false ) {

			while ( !$cimages->EOF ) {
break;
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
					$msg = "[ERROR] Contributor index image not found at " . $source_file;
//					log_document_conversion(0, 0, $jconf['jobid_upload_finalize'], $jconf['dbstatus_cimage'], $msg, "-", 0, 0, true);
					$debug->log($jconf['log_dir'], $myjobid . ".log", $msg, $sendmail = true);
					$global_log .= $msg . "\n\n";
					$cimages->MoveNext();
					continue;
				}

				// Create destination directory under "contributors"
				$err = create_directory($destination_path);
				if ( !$err['code'] ) {
					$msg = "[ERROR] Cannot create directory " . $destination_path;
//					log_document_conversion(0, 0, $jconf['jobid_upload_finalize'], $jconf['dbstatus_cimage'], $msg, "-", 0, 0, true);
					$debug->log($jconf['log_dir'], $myjobid . ".log", $msg, $sendmail = true);
					$global_log .= $msg . "\n\n";
					$cimages->MoveNext();
					continue;
				}

				// Copy file to destination
				$err = copy($source_file, $destination_file);
				if ( !$err ) {
					$msg = "[ERROR] Cannot copy " . $source_file . " -> " . $destination_file;
//					log_document_conversion(0, 0, $jconf['jobid_upload_finalize'], $msg, "-", 0, 0, true);
					$debug->log($jconf['log_dir'], $myjobid . ".log", $msg, $sendmail = true);
					$global_log .= $msg . "\n\n";
					$cimages->MoveNext();
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
//			log_document_conversion(0, 0, $jconf['jobid_upload_finalize'], "-", "Contributor images finalize finished in " . $hms . " time.\n\nSummary:\n\n" . $global_log, "-", "-", $duration, false);
			$debug->log($jconf['log_dir'], $myjobid . ".log", "Contributor index image finalize finished in " . $hms . " time.\n\nSummary:\n\n" . $global_log, $sendmail = false);

		} // End of contributor images finalize

		break;
	} // End of while(1)

	// Recordings: finalize masters (once daily, after midnight)
	$start_time = time();
	$inwhichhour = 18;
	if ( ( date("G") == $inwhichhour ) and ( empty($finalizedonelasttime) or ( ( $start_time - $finalizedonelasttime ) > 3600 * 24 ) ) ) {

		$recordings = getRecordingMastersToFinalize();
		if ( $recordings !== false ) {

			$recording = $recordings->fields;

			$destination_path = $app->config['recordingpath'] . ( $recording['id'] % 1000 ) . "/" . $recording['id'] . "/master/";

var_dump($recording);

			$debug->log($jconf['log_dir'], $myjobid . ".log", "Recording finalization for id = " . $recording['id'] . " started.", $sendmail = false);

			$err = create_directory($destination_path);
			if ( !$err['code'] ) {
				$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot create directory " . $destination_path, $sendmail = true);
				$recordings->MoveNext();
				continue;
			}

			if ( $recording['masterstatus'] == $jconf['dbstatus_uploaded'] ) {

				$idx = "";
				$suffix = "video";
				$source_filename = $app->config['uploadpath'] . "recordings/" . $recording['id'] . "_" . $suffix . "." . $recording[$idx . 'mastervideoextension'];
				$destination_filename = $destination_path . $recording['id'] . "_" . $suffix . "." . $recording[$idx . 'mastervideoextension'];
echo $source_filename . "\n";
echo $destination_filename . "\n";

				// Copy media file
				$start_time = time();
				if ( !copy($source_filename, $destination_filename) ) {
					$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot copy file " . $source_filename . " to " . $destination_filename, $sendmail = true);
					$recordings->MoveNext();
					continue;
				} else {
					$duration = time() - $start_time;
					$hms = secs2hms($duration);
					$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Copying " . $source_filename . " to " . $destination_filename . " finished in " . $hms . " time", $sendmail = false);
					if ( !unlink($source_filename) ) {
						$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot remove file " . $source_filename, $sendmail = true);
						$recordings->MoveNext();
						continue;
					} else {
						$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] File " . $source_filename . " removed.", $sendmail = false);
						updateMasterRecordingStatus($recording['id'], $jconf['dbstatus_copystorage_ok'], "recording");
					}

exit;
				}

			}

			$app->watchdog();
			$recordings->MoveNext();
		}

		$finalizedonelasttime = time();
	}

	// Close DB connection if open
	if ( is_resource($db->_connectionID) ) $db->close();

	$app->watchdog();

	sleep($sleep_length);
	
}	// End of outer while

exit;


// *************************************************************************
// *						function query_docnew()						   *
// *************************************************************************
// Description: queries next uploaded document from attached_documents
function getUploadedAttachments() {
global $jconf, $db, $app, $debug, $myjobid;

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
//		log_document_conversion(0, 0, $jconf['jobid_upload_finalize'], $jconf['dbstatus_init'], "[ERROR] Cannot query next uploaded document. SQL query failed.", trim($query), $err, 0, true);
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
		return false;
	}

	// Check if pending job exsits
	if ( $docs->RecordCount() < 1 ) {
		return false;
	}

	return $docs;
}

// *************************************************************************
// *					function query_user_avatars()					   *
// *************************************************************************
// Description: queries pending user avatars
function getUploadedAvatars() {
global $jconf, $db, $app, $debug, $myjobid;

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
			a.avatarstatus = '" . $jconf['dbstatus_uploaded'] . "' AND
			a.organizationid = b.id
	";

	try {
		$avatars = $db->Execute($query);
	} catch (exception $err) {
//		log_document_conversion(0, 0, $jconf['jobid_upload_finalize'], $jconf['dbstatus_init'], "[ERROR] Cannot query user avatars. SQL query failed.", trim($query), $err, 0, true);
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed.\n" . trim($query), $sendmail = true);
		return false;
	}

	// Check if pending job exsits
	if ( $avatars->RecordCount() < 1 ) {
		return false;
	}

	return $avatars;
}

function getSelectedContributorImages() {
global $jconf, $db, $app, $debug, $myjobid;

	$db = db_maintain();

	$query = "
		SELECT
			id,
			contributorid,
			indexphotofilename
		FROM
			contributor_images
		WHERE
			indexphotofilename LIKE '%recordings%'";

	try {
		$cimages = $db->Execute($query);
	} catch (exception $err) {
//		log_document_conversion(0, 0, $jconf['jobid_upload_finalize'], $jconf['dbstatus_init'], "[ERROR] Cannot query contributor images. SQL query failed.", trim($query), $err, 0, true);
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed.\n" . trim($query), $sendmail = true);
		return false;
	}

	// Check if pending job exsits
	if ( $cimages->RecordCount() < 1 ) {
		return false;
	}

	return $cimages;
}

function getRecordingMastersToFinalize() {
global $jconf, $debug, $db, $app, $myjobid;

	$db = db_maintain();

	$node = $app->config['node_sourceip'];
// !!!
	$node = "stream.videosquare.eu";

	$query = "
		SELECT
			r.id,
			r.status,
			r.contentstatus,
			r.masterstatus,
			r.contentmasterstatus,
			r.mastersourceip,
			r.contentmastersourceip,
			r.mastervideofilename,
			r.contentmastervideofilename,
			r.mastervideoextension,
			r.contentmastervideoextension
		FROM
			recordings AS r
		WHERE
			( r.mastersourceip = '" . $node . "' AND r.masterstatus = '" . $jconf['dbstatus_uploaded'] . "' AND r.status = '" . $jconf['dbstatus_copystorage_ok'] . "' ) OR
			( r.contentmastersourceip = '" . $node . "' AND r.contentmasterstatus = '" . $jconf['dbstatus_uploaded'] . "' AND r.contentstatus = '" . $jconf['dbstatus_copystorage_ok'] . "' )
		ORDER BY
			r.id";

echo $query . "\n";

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed.\n" . trim($query), $sendmail = true);
		return false;
	}

	// Check if any record returned
	if ( $rs->RecordCount() < 1 ) return false;

	return $rs;
}

?>