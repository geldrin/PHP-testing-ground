<?php

// ------------
// RECORDINGS
// ------------

// new functions
function updateRecordingStatus($recordingid, $status, $type = "recording") {
global $app, $debug, $jconf, $myjobid;

	if ( ( $type != "recording" ) and ( $type != "content" ) and ( $type != "smil" ) and ( $type != "contentsmil" ) and ( $type != "mobile" ) ) return false;

	if ( empty($status) ) return false;

	$idx = "";
	if ( $type == "content" ) $idx = "content";
	if ( $type == "smil" ) $idx = "smil";
	if ( $type == "contentsmil" ) $idx = "contentsmil";
	if ( $type == "mobile" ) $idx = "mobile";
	
	if ( !empty($status) ) {
		$values = array(
			$idx . 'status' => $status
		);
	} else {
		$values = array(
			$idx . 'status' => null
		);
	}

	$recordingVersionObj = $app->bootstrap->getModel('recordings');
	$recordingVersionObj->select($recordingid);
    $recordingVersionObj->updateRow($values);

	// Update index photos
	if ( ( $status == $jconf['dbstatus_copystorage_ok'] ) and ( $type == "recording" ) ) {
		$recordingObj = $app->bootstrap->getModel('recordings');
		$recordingObj->select($recordingid);
		$recordingObj->updateChannelIndexPhotos();
	}

	// Log status change
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Recording id = " . $recordingid . " " . $type . " status has been changed to '" . $status . "'.", $sendmail = false);

	return true;
}

function updateMasterRecordingStatus($recordingid, $status, $type = "recording") {
global $app, $debug, $jconf, $myjobid;

	if ( ( $type != "recording" ) and ( $type != "content" ) ) return false;

	if ( empty($status) ) return false;

	$idx = "";
	if ( $type == "content" ) $idx = "content";

	$values = array(
		$idx . 'masterstatus' => $status
	);

	$recordingVersionObj = $app->bootstrap->getModel('recordings');
	$recordingVersionObj->select($recordingid);
    $recordingVersionObj->updateRow($values);

	// Log status change
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Recording id = " . $recordingid . " " . $type . " master status has been changed to '" . $status . "'.", $sendmail = false);

	return true;
}

function updateMediaInfo($recording, $profile) {
global $app, $debug, $jconf, $myjobid;

	$values = array(
		'qualitytag'			=> $profile['shortname'],
		'filename'				=> $recording['output_basename'],
		'isdesktopcompatible'	=> $profile['isdesktopcompatible'],
		'ismobilecompatible'	=> max($profile['isioscompatible'], $profile['isandroidcompatible'])
	);

	if ( !empty($recording['encodingparams']['resx']) and !empty($recording['encodingparams']['resy']) ) {
		$values['resolution'] = $recording['encodingparams']['resx'] . "x" . $recording['encodingparams']['resy'];
	}

	$values['bandwidth'] = 0;
	if ( !empty($recording['encodingparams']['audiobitrate']) ) $values['bandwidth'] += $recording['encodingparams']['audiobitrate'];
	if ( !empty($recording['encodingparams']['videobitrate']) ) $values['bandwidth'] += $recording['encodingparams']['videobitrate'];

	$recordingVersionObj = $app->bootstrap->getModel('recordings_versions');
	$recordingVersionObj->select($recording['recordingversionid']);
    $recordingVersionObj->updateRow($values);

	// Log status change
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Recording version id = " . $recording['recordingversionid'] . " media information has been updated.\n" . print_r($values, true), $sendmail = false);

	// Video thumbnails: update if generated
	if ( !empty($recording['thumbnail_numberofindexphotos']) and !empty($recording['thumbnail_indexphotofilename']) ) {

		$values = array(
			'indexphotofilename'	=> $recording['thumbnail_indexphotofilename'],
			'numberofindexphotos'	=> $recording['thumbnail_numberofindexphotos']
		);

		$recordingObj = $app->bootstrap->getModel('recordings');
		$recordingObj->select($recording['id']);
		$recordingObj->updateRow($values);

		// Log status change
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Recording id = " . $recording['id'] . " thumbnail information has been updated.\n" . print_r($values, true), $sendmail = false);
	}

	return true;
}

// old functions - LEGACY: TO BE REMOVED WHEN ENCODER 2.0 IS LIVE
function update_db_recording_status($rec_id, $status) {
global $app, $jconf, $db;

	$db = db_maintain();

	$query = "
		UPDATE
			recordings
		SET
			status = \"" . $status . "\"
		WHERE
			id = " . $rec_id;

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		log_video_conversion($rec_id, $jconf['jobid_media_convert'], "-", "[ERROR] Cannot update media status. SQL query failed.", trim($query), $err, 0, TRUE);
		return FALSE;
	}

	$recordingObj = $app->bootstrap->getModel('recordings');
	$recordingObj->select($rec_id);
	$recordingObj->updateChannelIndexPhotos();

	return TRUE;
}

function update_db_masterrecording_status($rec_id, $status) {
global $app, $jconf, $db;

	$db = db_maintain();

	$query = "
		UPDATE
			recordings
		SET
			masterstatus = \"" . $status . "\"
		WHERE
			id = " . $rec_id;

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		log_recording_conversion(0, $jconf['jobid_media_convert'], "-", "[ERROR] Cannot update master media status. SQL query failed.", trim($query), $err, 0, TRUE);
		return FALSE;
	}

	return TRUE;
}

function update_db_mediainfo($recording, $mobile_lq, $mobile_hq, $video_lq, $video_hq) {
global $app, $jconf, $db;

	$db = db_maintain();

	unset($indexphotodata);

	$query = "
		UPDATE
			recordings
		SET
			";

	// Mobile LQ
	if ( !empty($mobile_lq) ) {
		$query .= "mobilevideoreslq = \"" . $mobile_lq['res_x'] . "x" . $mobile_lq['res_y'] . "\"";
	} else {
		// Should never happen
		$query .= "mobilevideoreslq = mobilevideoreslq";
	}

	// Mobile HQ
	if ( !empty($mobile_hq) ) {
		$query .= ", mobilevideoreshq = \"" . $mobile_hq['res_x'] . "x" . $mobile_hq['res_y'] . "\"";
	}

	// Video LQ
	if ( !empty($video_lq) ) {
		$query .= ", videoreslq = \"" . $video_lq['res_x'] . "x" . $video_lq['res_y'] . "\"";
	}

	// Video HQ
	if ( !empty($video_hq) ) {
		$query .= ", videoreshq = \"" . $video_hq['res_x'] . "x" . $video_hq['res_y'] . "\"";
	}

	if ( !empty($recording['thumbnail_indexphotofilename']) ) {
		$query .= ", indexphotofilename = \"" . $recording['thumbnail_indexphotofilename'] . "\",\n";
		$query .= "numberofindexphotos = \"" . $recording['thumbnail_numberofindexphotos'] . "\"\n";
	}

	$query .= "
		WHERE
			id = " . $recording['id'];

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		log_recording_conversion(0, $jconf['jobid_media_convert'], "-", "[ERROR] Cannot update media information. SQL query failed.", trim($query), $err, 0, TRUE);
		return FALSE;
	}

	return TRUE;
}

function update_db_content_status($id, $status) {
global $db;

	$db = db_maintain();

	$query = "
		UPDATE
			recordings
		SET
			contentstatus = \"" . $status . "\"
		WHERE
			id = " . $id;

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		log_recording_conversion($id, $jconf['jobid_content_convert'], "-", "[ERROR] Cannot update content status. SQL query failed.", trim($query), $err, 0, TRUE);
		return FALSE;
	}

	return TRUE;
}

function update_db_mobile_status($id, $status) {
global $db;

	$db = db_maintain();

	$query = "
		UPDATE
			recordings
		SET
			mobilestatus = \"" . $status . "\"
		WHERE
			id = " . $id;

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		log_recording_conversion($id, $jconf['jobid_content_convert'], "-", "[ERROR] Cannot update mobile status. SQL query failed.", trim($query), $err, 0, TRUE);
		return FALSE;
	}

	return TRUE;
}

function update_db_mastercontent_status($id, $status) {
 global $db;

	$db = db_maintain();

	$query = "
		UPDATE
			recordings
		SET
			contentmasterstatus = \"" . $status . "\"
		WHERE
			id = " . $id;

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		log_recording_conversion($id, $jconf['jobid_content_convert'], "-", "[ERROR] Cannot update master content status. SQL query failed.", trim($query), $err, 0, TRUE);
		return FALSE;
	}

	return TRUE;
}

function update_db_contentinfo($id, $content_info_lq, $content_info_hq, $mobile_info_lq, $mobile_info_hq) {
global $jconf, $db;

	$db = db_maintain();

	$is_update = FALSE;

	$query = "
		UPDATE
			recordings
		SET
			";

	// Content LQ
	if ( !empty($content_info_lq) ) {
		$query .= "contentvideoreslq = \"" . $content_info_lq['res_x'] . "x" . $content_info_lq['res_y']. "\"";
		$is_update = TRUE;
	}

	// Content HQ
	if ( !empty($content_info_hq) ) {
		if ( $is_update ) $query .= ", ";
		$query .= "contentvideoreshq = \"" . $content_info_hq['res_x'] . "x" . $content_info_hq['res_y']. "\"";
		$is_update = TRUE;
	}

	// Mobile LQ
	if ( !empty($mobile_info_lq) ) {
		if ( $is_update ) $query .= ", ";
		$query .= "mobilevideoreslq = \"" . $mobile_info_lq['res_x'] . "x" . $mobile_info_lq['res_y']. "\"";
		$is_update = TRUE;
	}

	// Mobile HQ
	if ( !empty($mobile_info_hq) ) {
		if ( $is_update ) $query .= ", ";
		$query .= "mobilevideoreshq = \"" . $mobile_info_hq['res_x'] . "x" . $mobile_info_hq['res_y']. "\"";
		$is_update = TRUE;
	}

	$query .= "
		WHERE
			id = " . $id;

	if ( $is_update ) {
		try {
			$rs = $db->Execute($query);
		} catch (exception $err) {
			log_recording_conversion($id, $jconf['jobid_content_convert'], "-", "[ERROR] Cannot update content information. SQL query failed.", trim($query), $err, 0, TRUE);
			return FALSE;
		}
	}

	return TRUE;
}

// ------------
// RECORDINGS VERSIONS
// ------------

function updateRecordingVersionStatus($recordingversionid, $status) {
global $app, $debug, $jconf, $myjobid;

	if ( empty($status) ) return false;

	$values = array(
		'status' => $status
	);

	$recordingVersionObj = $app->bootstrap->getModel('recordings_versions');
	$recordingVersionObj->select($recordingversionid);
    $recordingVersionObj->updateRow($values);

	// Log status change
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Recording version id = " . $recordingversionid . " status has been changed to '" . $status . "'.", $sendmail = false);

	return true;
}

function getRecordingVersionStatus($recordingversionid) {
global $app, $debug, $jconf, $myjobid;

	$recordingVersionObj = $app->bootstrap->getModel('recordings_versions');
	$recordingVersionObj->select($recordingversionid);
    $recversion = $recordingVersionObj->getRow();

	return $recversion['status'];
}

function getRecordingStatus($recordingid, $type = "recording") {
global $app, $debug, $jconf, $myjobid;

	if ( ( $type != "recording" ) and ( $type != "content" ) ) return false;

	$idx = "";
	if ( $type == "content" ) $idx = "content";

	$recordingObj = $app->bootstrap->getModel('recordings');
	$recordingObj->select($recordingid);
    $recording = $recordingObj->getRow();

	return $recording[$idx . 'status'];
}

function updateRecordingVersionStatusAll($recordingid, $status, $type = "recording") {
global $app, $debug, $jconf, $myjobid, $db;

	if ( ( $type != "recording" ) and ( $type != "content" ) and ( $type != "all" ) ) return false;

	if ( empty($status) ) return false;

	if ( $type == "recording" ) $iscontent_filter = " AND rv.iscontent = 0";
	if ( $type == "content" ) $iscontent_filter = " AND rv.iscontent = 1";
	if ( $type == "all" ) $iscontent_filter = "";

	$db = db_maintain();

	$query = "
		UPDATE
			recordings_versions as rv
		SET
			rv.status = '" . $status . "'
		WHERE
			rv.recordingid = " . $recordingid . $iscontent_filter;

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed.\n" . trim(substr($query, 1, 255)) . "\n\nERR:\n" . trim(substr($err, 1, 255)), $sendmail = true);
		return false;
	}

	// Log status change
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] All recording versions for recording id = " . $recordingid . " have been changed to '" . $status . "' status.", $sendmail = false);

	return true;
}

// Set recording version status only where listed statuses are set.
// Important: When especially deleting recordings and recording versions, we cannot set
// all recording version statuses to "markedfordeletion" blindly. This would affect recording versions
// with undesired statuses such as "deleted" (removed earlier), going back to "markedfordeletion" again.
function updateRecordingVersionStatusApplyFilter($recordingid, $status, $typefilter, $statusfilter) {
global $app, $debug, $jconf, $myjobid, $db;

	// Check parameters
	if ( empty($status) ) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] updateRecordingVersionStatusApplyFilter() called with invalid status: " . $status, $sendmail = false);
		return false;
	}
	if ( empty($typefilter) or ( $typefilter == "all" ) ) $typefilter = "recording|content|pip";

	// Build type filter
	$sql_typefilter = "";
	$tmp = explode("|", $typefilter);
	for ( $i = 0; $i < count($tmp); $i++ ) {
		// Check if type is valid
		if ( ( $tmp[$i] != "recording" ) and ( $tmp[$i] != "content" ) and ( $tmp[$i] != "pip" ) ) {
			$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] updateRecordingVersionStatusApplyFilter() called with invalid type filter: " . $typefilter, $sendmail = false);
			return false;
		}
		$sql_typefilter .= "'" . $tmp[$i] . "'";
		if ( $i < count($tmp) - 1 ) $sql_typefilter .= ",";
	}
	$sql_typefilter = " AND ep.type IN (" . $sql_typefilter . ")";

	// Build status filter
	$sql_statusfilter = "";
	if ( !empty($statusfilter) ) {
		$tmp = explode("|", $statusfilter);
		for ( $i = 0; $i < count($tmp); $i++ ) {
			$sql_statusfilter .= "'" . $tmp[$i] . "'";
			if ( $i < count($tmp) - 1 ) $sql_statusfilter .= ",";
		}
		$sql_statusfilter = " AND rv.status IN (" . $sql_statusfilter . ")";
	}

	$db = db_maintain();

	$query = "
		UPDATE
			recordings_versions as rv, encoding_profiles as ep
		SET
			rv.status = '" . $status . "'
		WHERE
			rv.recordingid = " . $recordingid . " AND
			rv.encodingprofileid = ep.id" . $sql_typefilter . $sql_statusfilter;

//echo $query . "\n";

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed.\n" . trim(substr($query, 1, 255)) . "\n\nERR:\n" . trim(substr($err, 1, 255)), $sendmail = true);
		return false;
	}

	// Log status change
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] All recording versions with status filter = '" . $statusfilter . "' and type filter = '" . $typefilter . "' for recording id = " . $recordingid . " have been changed to '" . $status . "' status.", $sendmail = false);

	return true;
}

function getRecordingVersionsApplyStatusFilter($recordingid, $type = "recording", $statusfilter) {
global $app, $debug, $jconf, $myjobid;

	if ( ( $type != "recording" ) and ( $type != "content" ) and ( $type != "all" ) ) return false;

	if ( $type == "recording" ) $iscontent_filter = " AND rv.iscontent = 0";
	if ( $type == "content" ) $iscontent_filter = " AND rv.iscontent = 1";
	if ( $type == "all" ) $iscontent_filter = "";

	$sql_statusfilter = "";
	if ( !empty($statusfilter) ) {
		$statuses2filter = explode("|", $statusfilter);
		for ( $i = 0; $i < count($statuses2filter); $i++ ) {
			$sql_statusfilter .= "'" . $statuses2filter[$i] . "'";
			if ( $i < count($statuses2filter) - 1 ) $sql_statusfilter .= ",";
		}
		$sql_statusfilter = " AND rv.status IN (" . $sql_statusfilter . ")";
	}

	$db = db_maintain();

	$query = "
		SELECT
			rv.id,
			rv.recordingid,
			rv.encodingprofileid,
			rv.encodingorder,
			rv.qualitytag,
			rv.filename,
			rv.iscontent,
			rv.status,
			rv.resolution,
			rv.bandwidth,
			rv.isdesktopcompatible,
			rv.ismobilecompatible
		FROM
			recordings_versions as rv
		WHERE
			rv.recordingid = " . $recordingid . $iscontent_filter . $sql_statusfilter;

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed.\n" . trim(substr($query, 1, 255)) . "\n\nERR:\n" . trim(substr($err, 1, 255)), $sendmail = true);
		return false;
	}

	// Check if any record returned
	if ( $rs->RecordCount() < 1 ) return false;

	return true;
}

// Update recording encoding profile group
function updateRecordingEncodingProfile($recordingid, $encodinggroupid) {
global $app, $debug, $jconf, $myjobid;

	if ( empty($encodinggroupid) ) return false;

	$values = array(
		'encodinggroupid' => $encodinggroupid
	);

	$recordingVersionObj = $app->bootstrap->getModel('recordings');
	$recordingVersionObj->select($recordingid);
    $recordingVersionObj->updateRow($values);

	// Log change
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Recording id = " . $recordingid . " encoding group changed to '" . $encodinggroupid . "'.", $sendmail = false);

	return true;
}

// ------------
// ATTACHMENTS
// ------------

// new functions
function updateAttachedDocumentStatus($attachmentid, $status, $type = null) {
global $app, $debug, $jconf, $myjobid;

	if ( !empty($type) and ( $type != "indexingstatus" ) ) return false;

	if ( empty($status) ) return false;

	$idx = "";
	if ( $type == "indexingstatus" ) $idx = "indexing";

	$values = array(
		$idx . 'status' => $status
	);

	try {
		$AttachmentObj = $app->bootstrap->getModel('attached_documents');
		$AttachmentObj->select($attachmentid);
		$AttachmentObj->updateRow($values);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed." . trim(substr($err, 1, 255)), $sendmail = true);
		return false;
	}

	// Log status change
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Attached document id = " . $attachmentid . " " . $type . " has been changed to '" . $status . "'.", $sendmail = false);

	return true;
}

function updateAttachedDocumentCache($attachmentid, $documentcache) {
global $app, $debug, $jconf, $myjobid;

	$db = db_maintain();

	if ( !empty($documentcache) ) {
		$documentcache_escaped = $db->qstr($documentcache);
	} else {
		$documentcache_escaped = null;
	}

	$values = array(
		'documentcache' => $documentcache_escaped
	);

	try {
		$AttachmentObj = $app->bootstrap->getModel('attached_documents');
		$AttachmentObj->select($attachmentid);
		$AttachmentObj->updateRow($values);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed." . trim(substr($err, 1, 255)), $sendmail = true);
		return false;
	}

	// Log status change
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Attached document id = " . $attachmentid . " cache has been updated to '" . trim(substr($documentcache, 1, 50)) . "'.", $sendmail = false);

	return true;
}

// old functions - LEGACY: TO BE REMOVED WHEN ENCODER 2.0 IS LIVE
function update_db_attachment_status($id, $status) {
global $jconf, $db;

	$db = db_maintain();

	$query = "
		UPDATE
			attached_documents
		SET
			status = '" . $status . "'
		WHERE
			id = " . $id;

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		log_document_conversion($id, 0, $jconf['jobid_upload_finalize'], "-", "[ERROR] Cannot update document status. SQL query failed.", trim($query), $err, 0, TRUE);
		return FALSE;
	}

	return TRUE;
}

function update_db_attachment_indexingstatus($id, $status) {
global $jconf, $db;

	$db = db_maintain();

	$query = "
		UPDATE
			attached_documents
		SET
			indexingstatus = '" . $status . "'
		WHERE
			id = " . $id;

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		log_document_conversion($id, 0, $jconf['jobid_document_index'], "-", "[ERROR] Cannot update document indexing status. SQL query failed.", trim($query), $err, 0, TRUE);
		return FALSE;
	}

	return TRUE;
}

// ------------
// AVATAR
// ------------

function updateAvatarStatus($userid, $status) {
global $app, $debug, $jconf, $myjobid;

	if ( empty($status) ) return false;

	$values = array(
		'avatarstatus' => $status
	);

	try {
		$AttachmentObj = $app->bootstrap->getModel('users');
		$AttachmentObj->select($userid);
		$AttachmentObj->updateRow($values);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed." . trim(substr($err, 1, 255)), $sendmail = true);
		return false;
	}

	// Log status change
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] User avatar status = '" . $status . "'", $sendmail = false);

	return true;
}

// VCR - untouched!!!

// VCR: update recording link
function update_db_vcr_reclink_status($id, $status) {
global $db, $jconf;

	if ( empty($status) ) return false;

	$db = db_maintain();

	$query = "
		UPDATE
			recording_links
		SET
			status = \"" . $status . "\"
		WHERE
			id = " . $id;

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		log_recording_conversion($id, $jconf['jobid_vcr_control'], "-", "[ERROR] Cannot update VCR recording link status. SQL query failed.", trim($query), $err, 0, TRUE);
		return FALSE;
	}

	return TRUE;
}

// VCR: update recording link
function update_db_stream_status($id, $status) {
global $db, $jconf;

	if ( empty($status) ) return false;

	$db = db_maintain();

	$query = "
		UPDATE
			livefeed_streams
		SET
			status = \"" . $status . "\"
		WHERE
			id = " . $id;

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		log_recording_conversion($id, $jconf['jobid_vcr_control'], "-", "[ERROR] Cannot update live stream status. SQL query failed.", trim($query), $err, 0, TRUE);
		return FALSE;
	}

	return TRUE;
}

function update_db_stream_params($id, $keycode, $aspectratio, $conferenceid) {
global $db, $jconf;

	$db = db_maintain();

	if ( empty($conferenceid) ) $conferenceid = "NULL";

	// Update stream parameters
	$query = "
		UPDATE
			livefeed_streams
		SET
			keycode = '" . $keycode . "',
			vcrconferenceid = '" . $conferenceid . "'
		WHERE
			id = " . $id;

//aspectratio = '" . $aspectratio . "',

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		log_recording_conversion($id, $jconf['jobid_vcr_control'], "-", "[ERROR] VCR live stream parameters cannot be updated. SQL query failed.", trim($query), $err, 0, TRUE);
		return FALSE;
	}

	return TRUE;
}

function update_db_vcr_reclink_params($id, $conf_id) {
global $db, $jconf;

	$db = db_maintain();

	// Update stream parameters
	$query = "
		UPDATE
			recording_links
		SET
			conferenceid = '" . $conf_id . "'
		WHERE
			id = " . $id;

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		log_recording_conversion($id, $jconf['jobid_vcr_control'], "-", "[ERROR] VCR recording link parameters cannot be updated. SQL query failed.", trim($query), $err, 0, TRUE);
		return FALSE;
	}

	return TRUE;
}


// KIKUKÁZNI

// *************************************************************************
// *			function update_db_attachment_documentcache()	   	   	   *
// *************************************************************************
// Description: update attached document cache
function update_db_attachment_documentcache($attachment_id, $documentcache) {
global $db, $jconf;

	$db = db_maintain();

	$documentcache_escaped = $db->qstr($documentcache);

	$query = "
		UPDATE
			attached_documents
		SET
			documentcache = " . $documentcache_escaped . "
		WHERE
			id = " . $attachment_id;

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		log_document_conversion($attachment_id, 0, $jconf['jobid_document_index'], "-", "[ERROR] Cannot update attachment document cache. SQL query failed.", trim(substr($query, 1, 255)), trim(substr($err, 1, 255)), 0, TRUE);
		return FALSE;
	}

	return TRUE;
}

// ---------------------------------------------------------------------------

function updateLiveFeedStreamIndexPhoto($streamid, $indexphotofilename) {
global $app, $debug, $jconf, $myjobid;

	if ( empty($indexphotofilename) ) return false;

	$values = array(
		'indexphotofilename'	=> $indexphotofilename
	);

	$recordingVersionObj = $app->bootstrap->getModel('livefeed_streams');
	$recordingVersionObj->select($streamid);
    $recordingVersionObj->updateRow($values);
	$recordingVersionObj->updateFeedThumbnail();

	// Log index photo change
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] livefeed_streams.id = " . $streamid . " index photo updated to " . $indexphotofilename, $sendmail = false);

	return true;
}

function updateLiveFeedSMILStatus($livefeedid, $status, $type = "video") {
global $app, $debug, $jconf, $myjobid;

	if ( ( $type != "video" ) and ( $type != "content" ) ) return false;

	if ( empty($status) ) return false;

	$idx = "";
	if ( $type == "content" ) $idx = "content";

	$values = array(
		$idx . 'smilstatus' => $status
	);

	$recordingVersionObj = $app->bootstrap->getModel('livefeeds');
	$recordingVersionObj->select($livefeedid);
    $recordingVersionObj->updateRow($values);

	// Log status change
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Livefeed id = " . $livefeedid . " " . $type . " status has been changed to '" . $status . "'.", $sendmail = false);

	return true;
}


?>
