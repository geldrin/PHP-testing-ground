<?php
// File remove job handling:
//	- Remove recordings.status = "markedfordeleltion" recordings from storage

define('BASE_PATH',	realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

// Utils
include_once('job_utils_base.php');
include_once('job_utils_log.php');
include_once('job_utils_status.php');
include_once('job_utils_media.php');

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');

set_time_limit(0);

// Init
$app = new Springboard\Application\Cli(BASE_PATH, PRODUCTION);

// Load jobs configuration file
$app->loadConfig('modules/Jobs/config_jobs.php');
$jconf = $app->config['config_jobs'];
$myjobid = $jconf['jobid_remove_files'];

// Log related init
$debug = Springboard\Debug::getInstance();
$debug->log($jconf['log_dir'], $myjobid . ".log", "*************************** Remove files job started *************************** ", $sendmail = false);

// Debug mode on/off
$isdebug = true;
// Should we remove files and do any changes to DB?
$isexecute = false;

// Check operating system - exit if Windows
if ( iswindows() ) {
	echo "ERROR: Non-Windows process started on Windows platform\n";
	exit;
}

// Exit if any STOP file exists
if ( is_file( $app->config['datapath'] . 'jobs/job_remove_files.stop' ) or is_file( $app->config['datapath'] . 'jobs/all.stop' ) ) exit;

clearstatcache();

// Watchdog
$app->watchdog();

//$sleep_length = $jconf['sleep_long'];

// Establish database connection
$db = db_maintain();

// Should we delete files or just testing?
if ( !$isexecute ) {
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] NO FILES WILL BE REMOVED! isexecute = 1!\n", $sendmail = false);
}

// Recording to remove: delete all recording including master, surrogates, documents and index pictures
$recordings = queryRecordingsToRemove("recording");
if ( $recordings !== false ) {

	$size_toremove = 0;

	while ( !$recordings->EOF ) {

		$recording = $recordings->fields;

		// Check recording retain time period
		$now = time();
		$rec_deleted = strtotime($recording['deletedtimestamp']);
		$rec_retain = $recording['daystoretainrecordings'] * 24 * 3600;
		if ( ( $now - $rec_deleted ) < $rec_retain ) {
			// Falls within retain period, no action taken
			$recordings->MoveNext();
			continue;
		}
		
		$log_msg  = "recordingid: " . $recording['id'] . "\n";
		$log_msg .= "userid: " . $recording['email'] . " (domain: " . $recording['domain'] . ")\n";

		// Directory to remove
		$remove_path = $app->config['recordingpath'] . ( $recording['id'] % 1000 ) . "/" . $recording['id'] . "/";

		// Log file path information
		$log_msg .= "Remove: " . $remove_path . "\n";

		// Check directory size
		$err = directory_size($remove_path);
		$dir_size = 0;
		if ( $err['code'] === true ) {
			$size_toremove += $err['size'];
			$dir_size = round($err['size'] / 1024 / 1024, 2);
			$log_msg .= "Recording size: " . $dir_size . "MB\n\n";
		}

		// Log recording to remove
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Removing recording:\n" . $log_msg, $sendmail = false);

		// Remove recording directory
		if ( $isexecute ) {

			$err = remove_file_ifexists($remove_path);
			if ( !$err['code'] ) {
				// Error: we skip this one, admin must check it manually
				$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot remove recording directory.\n" . $err['message'] . "\n\nCommand:\n" . $err['command'] . "\n\nOutput:\n" . $err['command_output'], $sendmail = true);
				// Next recording
				$recordings->MoveNext();
				continue;
			}

			// Update status fields: status, masterstatus and all recording versions
			updateRecordingStatus($recording['id'], $jconf['dbstatus_deleted'], "recording");
			updateMasterRecordingStatus($recording['id'], $jconf['dbstatus_deleted'], "recording");
			updateRecordingVersionStatusAll($recording['id'], $jconf['dbstatus_deleted'], "all");
// !!!
			update_db_mobile_status($recording['id'], $jconf['dbstatus_deleted']);
// !!!
			// smilstatus = NULL
			updateRecordingStatus($recording['id'], null, "smil");
			if ( !empty($recording['contentmasterstatus']) ) {
				// Update status fields: contentstatus, contentmasterstatus, contentsmilstatus
				updateRecordingStatus($recording['id'], $jconf['dbstatus_deleted'], "content");
				updateMasterRecordingStatus($recording['id'], $jconf['dbstatus_deleted'], "content");
				updateRecordingStatus($recording['id'], null, "contentsmil");
			}

			// Update attached documents: of removed recording: status, delete document cache
			$query = "
				UPDATE
					attached_documents
				SET
					status = '" . $jconf['dbstatus_deleted'] . "',
					indexingstatus = NULL,
					documentcache = NULL
				WHERE
					recordingid = " . $recording['id'];

			try {
				$rs = $db->Execute($query);
			} catch (exception $err) {
				$debug->log($jconf['log_dir'], $jconf['jobid_file_remove'] . ".log", "[ERROR] SQL query failed.\n" . trim($query), $sendmail = true);
				$recordings->MoveNext();
				continue;
			}

			// New recording and master size
			$values = array(
				'recordingdatasize'	=> 0,
				'masterdatasize'	=> 0
			);
			$recDoc = $app->bootstrap->getModel('recordings');
			$recDoc->select($recording['id']);
			$recDoc->updateRow($values);
			$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Recording and master data size updated.\n\n" . print_r($values, true), $sendmail = false);

		} // End of file remove

		// Watchdog
		$app->watchdog();

		// Next recording
		$recordings->MoveNext();
	}

} // End of removing recordings

// Content to remove: delete content including master, surrogates and others
$recordings = queryRecordingsToRemove("content");
if ( $recordings !== false ) {

	$size_toremove = 0;

	while ( !$recordings->EOF ) {

		$recording = $recordings->fields;

		$log_msg  = "recordingid: " . $recording['id'] . "\n";
		$log_msg .= "userid: " . $recording['email'] . " (domain: " . $recording['domain'] . ")\n";

		// Main path
		$remove_path = $app->config['recordingpath'] . ( $recording['id'] % 1000 ) . "/" . $recording['id'] . "/";

		// Master path
		$master_filename = $remove_path . "master/" . $recording['id'] . "_content." . $recording['contentmastervideoextension'];

		// Log recording to remove
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Removing content: " . $master_filename, $sendmail = false);

		// Remove content master
		if ( $isexecute ) {

			// Size of the file to be removed
			$size_toremove = filesize($master_filename);

			$err = remove_file_ifexists($master_filename);
			if ( !$err['code'] ) {
				// Error: we skip this one, admin must check it manually
				$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot remove recording.\n" . $err['message'] . "\nCommand:\n" . $err['command'] . "\nOutput:\n" . $err['command_output'], $sendmail = true);
			} else {
				// contentmasterstatus = "deleted"
				updateMasterRecordingStatus($recording['id'], $jconf['dbstatus_deleted'], "content");
				// contentstatus = "deleted"
				updateRecordingStatus($recording['id'], $jconf['dbstatus_deleted'], "content");
				$debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] Recording content master id = " . $recording['id'] . " removed. Filename: " . $master_filename, $sendmail = false);
			}

			// recordings_versions.status = "markedfordeletion" for all content surrogates (will be deleted in the next step, see below)
			updateRecordingVersionStatusAll($recording['id'], $jconf['dbstatus_markedfordeletion'], "content");
			// contentsmilstatus = NULL
			updateRecordingStatus($recording['id'], null, "contentsmil");

			// New recording and master size
			$values = array(
				'recordingdatasize'	=> $recording['recordingdatasize'] - $size_toremove,
				'masterdatasize'	=> $recording['masterdatasize'] - $size_toremove
			);
			$recDoc = $app->bootstrap->getModel('recordings');
			$recDoc->select($recording['id']);
			$recDoc->updateRow($values);
			$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Recording and master data size updated.\n\n" . print_r($values, true), $sendmail = false);

		} // End of file removal

		// Watchdog
		$app->watchdog();

		// Next recording
		$recordings->MoveNext();
	}
}

// Surrogates: delete recordings_versions elements one by one
$recversions = queryRecordingsVersionsToRemove();
if ( $recversions !== false ) {

	while ( !$recversions->EOF ) {

		$recversion = $recversions->fields;

		$recversion_filename = $app->config['recordingpath'] . ( $recversion['recordingid'] % 1000 ) . "/" . $recversion['recordingid'] . "/" . $recversion['filename'];

		$idx = "";
		if ( $recversion['iscontent'] ) $idx = "content";

		// Log recording to remove
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Removing recording version: " . $recversion_filename, $sendmail = false);

		// Remove content surrogate
		if ( $isexecute ) {

			// Size of the file to be removed
			$size_toremove = filesize($recversion_filename);

			$err = remove_file_ifexists($recversion_filename);
			if ( !$err['code'] ) {
				// Error: we skip this one, admin must check it manually
				$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot remove recording version.\n" . $err['message'] . "\nCommand:\n" . $err['command'] . "\nOutput:\n" . $err['command_output'], $sendmail = true);
				$recversions->MoveNext();
				continue;
			}

			// recordings_versions.status = "deleted"
			updateRecordingVersionStatus($recversion['id'], $jconf['dbstatus_deleted']);
			// recording.(content)smilstatus = "regenerate"
			updateRecordingStatus($recversion['recordingid'], $jconf['dbstatus_regenerate'], $idx . "smil");
			$debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] Recording version removed. Info: recordingid = " . $recversion['recordingid'] . ", recordingversionid = " . $recversion['id'] . ", filename = " . $recversion_filename, $sendmail = false);

			// New recording size
			$values = array(
				'recordingdatasize'	=> $recording['recordingdatasize'] - $size_toremove
			);
			$recDoc = $app->bootstrap->getModel('recordings');
			$recDoc->select($recording['id']);
			$recDoc->updateRow($values);
			$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Recording data size updated.\n\n" . print_r($values, true), $sendmail = false);

		} // End of recording version removal

		$recversions->MoveNext();
	}
}

// Attachments: remove uploaded attachments
$attachments = queryAttachmentsToRemove();
if ( $attachments !== false ) {

	while ( !$attachments->EOF ) {

		$attached_doc = array();
		$attached_doc = $attachments->fields;

		// Path and filename
		$base_dir = $app->config['recordingpath'] . ( $attached_doc['rec_id'] % 1000 ) . "/" . $attached_doc['rec_id'] . "/attachments/";
		$base_filename = $attached_doc['id'] . "." . $attached_doc['masterextension'];
		$filename = $base_dir . $base_filename;

		// Log file to remove
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Removing attachment: " . $filename, $sendmail = false);

		// Remove attachment
		if ( $isexecute ) {

			// Size of the file to be removed
			$size_toremove = filesize($filename);

			$err = remove_file_ifexists($filename);
			if ( !$err['code'] ) {
				// Error: we skip this one, admin must check it manually
				$debug->log($jconf['log_dir'], $myjobid . ".log", $err['message'] . "\n\nCommand:\n" . $err['command'] . "\n\nOutput:\n" . $err['command_output'], $sendmail = true);
				$attachments->MoveNext();
				continue;
			}

			$debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] Attached document removed. Info: attacheddocumentid = " . $attached_doc['id'] . ", recordingid = " . $attached_doc['rec_id'] . ", size = " . round($size_toremove / 1024 / 1024, 2) . "MB, filename = " . $filename . ".", $sendmail = false);

			// Update attached document status to DELETED
			updateAttachedDocumentStatus($attached_doc['id'], $jconf['dbstatus_deleted'], null);
			// Update attached document indexingstatus to NULL
			updateAttachedDocumentStatus($attached_doc['id'], null, "indexingstatus");
			// Update attached document cache to NULL
			updateAttachedDocumentCache($attached_doc['id'], null);

			// New recording size
			$values = array(
				'recordingdatasize'	=> $attached_doc['recordingdatasize'] - $size_toremove
			);
			$recDoc = $app->bootstrap->getModel('recordings');
			$recDoc->select($attached_doc['rec_id']);
			$recDoc->updateRow($values);
			$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Recording data size updated.\n\n" . print_r($values, true), $sendmail = false);

		} // End of file removal

		// Watchdog
		$app->watchdog();

		// Next attachment
		$attachments->MoveNext();
	}
}

// Close DB connection if open
if ( is_resource($db->_connectionID) ) $db->close();

// Watchdog
$app->watchdog();
	
exit;

// *************************************************************************
// *				function queryRecordingsToRemove()					   *
// *************************************************************************
// Description: queries next uploaded document from attached_documents
function queryRecordingsToRemove($type = null) {
global $jconf, $db, $app;

	if ( empty($type) or ( $type != "recording" ) and ( $type != "content" ) ) return false;

	$idx = "";
	if ( $type == "content" ) $idx = "content";

	$query = "
		SELECT
			a.id,
			a.userid,
			a.status,
			a.masterstatus,
			a.contentstatus,
			a.contentmasterstatus,
			a.mastersourceip,
			a.contentmastersourceip,
			a.deletedtimestamp,
			a.contentdeletedtimestamp,
			a.mastervideofilename,
			a.mastervideoextension,
			a.contentmastervideofilename,
			a.contentmastervideoextension,
			b.email,
			c.id AS organizationid,
			c.domain,
			c.daystoretainrecordings
		FROM
			recordings AS a,
			users AS b,
			organizations AS c
		WHERE
			a." . $idx . "status = '" . $jconf['dbstatus_markedfordeletion'] . "' AND
 			a.userid = b.id AND
			b.organizationid = c.id
	";
// OR a." . $type . "masterstatus = '" . $jconf['dbstatus_markedfordeletion'] . "' ) AND
// 			a." . $type . "mastersourceip = '" . $node . "' AND

	try {
		$recordings = $db->Execute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $jconf['jobid_remove_files'] . ".log", "[ERROR] SQL query failed.\n\n" . trim($query), $sendmail = true);
		return false;
	}

	// Check if pending job exsits
	if ( $recordings->RecordCount() < 1 ) {
		return false;
	}

	return $recordings;
}

function queryRecordingsVersionsToRemove() {
global $jconf, $db, $app;

	$query = "
		SELECT
			rv.id,
			rv.recordingid,
			rv.qualitytag,
			rv.iscontent,
			rv.status,
			rv.resolution,
			rv.filename,
			rv.bandwidth,
			rv.isdesktopcompatible,
			rv.ismobilecompatible,
			rv.encodingprofileid,
			ep.type,
			ep.mediatype,
			r.masterdatasize,
			r.recordingdatasize
		FROM
			recordings_versions AS rv,
			encoding_profiles AS ep,
			recordings AS r
		WHERE
			rv.recordingid = r.id AND
			rv.status = '" . $jconf['dbstatus_markedfordeletion'] . "' AND
			rv.encodingprofileid = ep.id";

//echo $query . "\n";

	try {
		$recversions = $db->Execute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $jconf['jobid_remove_files'] . ".log", "[ERROR] SQL query failed.\n\n" . trim($query), $sendmail = true);
		return false;
	}

	// Check if pending job exsits
	if ( $recversions->RecordCount() < 1 ) {
		return false;
	}

	return $recversions;
}


function queryAttachmentsToRemove() {
global $jconf, $db, $app;

//	$node = $app->config['node_sourceip'];

	$query = "
		SELECT
			a.id,
			a.masterfilename,
			a.masterextension,
			a.status,
			a.sourceip,
			a.recordingid as rec_id,
			a.userid,
			b.nickname,
			b.email,
			r.recordingdatasize
		FROM
			attached_documents AS a,
			users AS b,
			recordings AS r
		WHERE
			a.status = '" . $jconf['dbstatus_markedfordeletion'] . "' AND
			( a.indexingstatus IS NULL OR a.indexingstatus = '' OR a.indexingstatus = '" . $jconf['dbstatus_indexing_ok'] . "' ) AND
			a.userid = b.id AND
			a.recordingid = r.id
	";

	try {
		$attachments = $db->Execute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $jconf['jobid_remove_files'] . ".log", "[ERROR] SQL query failed.\n\n" . trim($query), $sendmail = true);
		return false;
	}

	// Check if pending job exists
	if ( $attachments->RecordCount() < 1 ) {
		return false;
	}

	return $attachments;
}

?>