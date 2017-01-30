<?php

// ------------
// RECORDINGS
// ------------

// new functions
///////////////////////////////////////////////////////////////////////////////////////////////////
function updateRecordingStatus($recordingid, $status, $type = "recording") {
///////////////////////////////////////////////////////////////////////////////////////////////////
global $app, $debug, $jconf, $myjobid;
	
	// Check allowed status field types
	$allowed_types = array('recording', 'content', 'mobile', 'ocr', 'smil', 'contentsmil', 'mobilesmil');
	if (!in_array($type, $allowed_types, $strict = true)) return false;
    
	if ( empty($status) ) return false;
	
	if ( $status == 'NULL' ) $status = null;

	$idx = null;
	if ( $type === 'recording' ) $idx = '';
	else $idx = $type;
	
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
	$debug->log($jconf['log_dir'], $myjobid .".log", "[INFO] Recording id = ". $recordingid ." ". $type ." status has been changed to ". var_export($status, 1) .".", false);

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
global $app, $debug, $jconf, $myjobid;

	if ( ( $type != "recording" ) and ( $type != "content" ) and ( $type != "all" ) ) return false;

	if ( empty($status) ) return false;

	if ( $type == "recording" ) $iscontent_filter = " AND rv.iscontent = 0";
	if ( $type == "content" ) $iscontent_filter = " AND rv.iscontent = 1";
	if ( $type == "all" ) $iscontent_filter = "";

	$query = "
		UPDATE
			recordings_versions AS rv
		SET
			rv.status = '" . $status . "'
		WHERE
			rv.recordingid = " . $recordingid . $iscontent_filter;

	try {
        $model = $app->bootstrap->getModel('recordings_versions');
        $rs = $model->safeExecute($query);
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
global $app, $debug, $jconf, $myjobid;

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

	$query = "
		UPDATE
			recordings_versions AS rv,
            encoding_profiles AS ep
		SET
			rv.status = '" . $status . "'
		WHERE
			rv.recordingid = " . $recordingid . " AND
			rv.encodingprofileid = ep.id" . $sql_typefilter . $sql_statusfilter;

	try {
        $model = $app->bootstrap->getModel('recordings_versions');
        $rs = $model->safeExecute($query);
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
			rv.ismobilecompatible,
            ep.type
		FROM
			recordings_versions AS rv,
            encoding_profiles AS ep
		WHERE
			rv.recordingid = " . $recordingid . " AND
            rv.encodingprofileid = ep.id" . $iscontent_filter . $sql_statusfilter;

	try {
        $model = $app->bootstrap->getModel('recordings_versions');
        $rs = $model->safeExecute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed.\n" . trim(substr($query, 1, 255)) . "\n\nERR:\n" . trim(substr($err, 1, 255)), $sendmail = true);
		return false;
	}

	// Check if any record returned
	if ( $rs->RecordCount() < 1 ) return false;

	return $rs;
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

	$idx = "";
	if ( $type == "indexingstatus" ) $idx = "indexing";

    if ( empty($status) ) $status = null;

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

    $AttachmentObj = $app->bootstrap->getModel('attached_documents');
    
	if ( !empty($documentcache) ) {
		$documentcache_escaped = $AttachmentObj->db->qstr($documentcache);
	} else {
		$documentcache_escaped = null;
	}

	$values = array(
		'documentcache' => $documentcache_escaped
	);

	try {
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

// ------
// AVATAR
// ------

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

//------------
//Live SMIL
//------------

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
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Livefeed id#" . $livefeedid . " type#" . $type . " SMIL status has been changed to '" . $status . "'.", $sendmail = false);

	return true;
}

//---------------------
// Infrastructure nodes
//---------------------
function updateInfrastructureNodeStatus($nodeid, $statusfield, $status) {
global $app, $debug, $jconf, $myjobid;

    if ( ( $statusfield != "statusstorage" ) and ( $statusfield != "statusnetwork" ) ) return false;
	if ( empty($status) ) return false;
	$values = array(
		$statusfield => $status
	);

    $converterNodeObj = $app->bootstrap->getModel('infrastructure_nodes');
    $converterNodeObj->select($nodeid);
    $converterNodeObj->updateRow($values);
    
	// Log status change
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Converter node#" . $nodeid . " " . $statusfield . " changed to '" . $status . "'.", $sendmail = false);

	return true;
}

//----
// VCR
//----

// VCR: update recording link. Previously: update_db_vcr_reclink_status
function updateVCRReclinkStatus($id, $status) {
global $app, $debug, $jconf, $myjobid;

	if ( empty($status) ) return false;

	$values = array(
		'status' => $status
	);

    $converterNodeObj = $app->bootstrap->getModel('recording_links');
    $converterNodeObj->select($id);
    $converterNodeObj->updateRow($values);
    
	// Log status change
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Recording link id#" . $id . " status changed to '" . $status . "'.", $sendmail = false);

	return true;
}

// VCR: update live stream status
function updateLiveStreamStatus($id, $status) {
global $app, $debug, $jconf, $myjobid;

	if ( empty($status) ) return false;

    $values = array(
		'status' => $status
	);

    $converterNodeObj = $app->bootstrap->getModel('livefeed_streams');
    $converterNodeObj->select($id);
    $converterNodeObj->updateRow($values);
    
	// Log status change
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Live stream id#" . $id . " status changed to '" . $status . "'.", $sendmail = false);
    
	return true;
}

// VCR: update live stream status
function updateLiveFeedStatus($id, $status) {
global $app, $debug, $jconf, $myjobid;

	if ( empty($status) ) return false;

    $values = array(
		'status' => $status
	);

    $converterNodeObj = $app->bootstrap->getModel('livefeeds');
    $converterNodeObj->select($id);
    $converterNodeObj->updateRow($values);
    
	// Log status change
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Livefeed id#" . $id . " status changed to '" . $status . "'.", $sendmail = false);
    
	return true;
}

// update_db_stream_params
function updateVCRLiveStreamParams($id, $streamid = null) {
global $app, $debug, $jconf, $myjobid;

	if ( empty($streamid) ) return false;

    $values = array();
    
    $values['keycode'] = $streamid;

    $converterNodeObj = $app->bootstrap->getModel('livefeed_streams');
    $converterNodeObj->select($id);
    $converterNodeObj->updateRow($values);
    
	// Log status change
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] VCR live stream id#" . $id . " params updated:\n" . print_r($values, true), $sendmail = false);

	return true;
}

// update_db_stream_params
function updateVCRLiveFeedParams($id, $conferenceid = null) {
global $app, $debug, $jconf, $myjobid;

    if ( empty($conferenceid) ) return false;

    $values = array();

	$values['vcrconferenceid'] = $conferenceid;

    $converterNodeObj = $app->bootstrap->getModel('livefeeds');
    $converterNodeObj->select($id);
    $converterNodeObj->updateRow($values);
    
	// Log status change
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] VCR livefeed id#" . $id . " params updated:\n" . print_r($values, true), $sendmail = false);

	return true;
}


// update_db_vcr_reclink_params
function updateVCRReclinkParams($id, $conf_id) {
global $app, $debug, $jconf, $myjobid;

	if ( empty($conf_id) ) return false;

    $values = array(
        'conferenceid'  => $conf_id
	);

    $converterNodeObj = $app->bootstrap->getModel('recording_links');
    $converterNodeObj->select($id);
    $converterNodeObj->updateRow($values);
    
	// Log status change
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] VCR recording link id#" . $id . " params updated with conferenceid = " . $conf_id . ".", $sendmail = false);

	return true;
}

//--------------------
//Livefeed index photo
//--------------------

function updateLiveFeedStreamIndexPhoto($streamid, $indexphotofilename) {
global $app;

	if ( empty($indexphotofilename) ) return false;

	$values = array(
		'indexphotofilename'	=> $indexphotofilename
	);

	$recordingVersionObj = $app->bootstrap->getModel('livefeed_streams');
	$recordingVersionObj->select($streamid);
	$recordingVersionObj->updateRow($values);
	$recordingVersionObj->updateFeedThumbnail();

	return true;
}

//--------------------
// OCR data
//--------------------

///////////////////////////////////////////////////////////////////////////////////////////////////
function updateOCRstatus($recordingid, $id = null, $status) {
///////////////////////////////////////////////////////////////////////////////////////////////////
//
// A fuggveny segitsegevel a felvetelhez tartozo ocr_frames sorok statuszat lehet beallitani.
// Alapertelmezeskent a 'recordingid'-hoz tartozo osszes ocr_frames mezo statuszat updateljuk,
// amelyek nem 'failed' vagy 'deleted' allapotuak. Ha az 'id'-ban atadunk egy listat, akkor csak
// az adott listaban megadott 'ocr_frames.id' sorokat update-eljuk.
//
// id          = (opcionalis, arr/integer) Az update-elni kivant ocr_frames sorok listaja
// recordingid = felveltel ID-je
// status      = statusmezo
//
///////////////////////////////////////////////////////////////////////////////////////////////////
	global $app;
	$result = array(
		'result'  => false, // sikerult/nem sikerult
		'message' => null,  // hibauzenet
		'command' => null,  // vegrehajtott SQL parancs
		'output'  => null,  // a beszurt ocr_frames utolso ID-je
	);
	
	if ($recordingid === null && $id === null) {
		$result['message'] = "[ERROR] ". __FUNCTION__ ." has been called without 'recordingid' nor 'id'.";
		return $result;
	} elseif ($status === null ) {
		$result['messge'] = "[ERROR] no 'status' passed to ". __FUNCTION__ .".";
		return $result;
	}
	
	try {
		$updatequery = "
			UPDATE
				`ocr_frames`
			SET
				`status` = '". $status ."'
			WHERE";
		if ($id === null) {
			$updatequery .= "
				`ocr_frames`.`recordingid` = ". $recordingid ." AND
				`ocr_frames`.`status` NOT REGEXP 'delete|failed';
			";
		} elseif (is_array($id)) {
			$updatequery .= "
				`ocr_frames`.`id` IN (". implode(',', $arr) .");";
		} else {
			$updatequery .= "
				`ocr_frames`.`id` = ". intval($id) .";";
		}
		
		$result['command'] = $updatequery;
        
        $model = $app->bootstrap->getModel('ocr_frames');
        $rs = $model->safeExecute($updatequery);
	} catch (Exception $ex) {
		$result['message'] = __FUNCTION__ ." failed! Errormessage: ". $ex->getMessage();
		return $result;
	}
	$result['result'] = true;
	$result['output'] = ( int ) $model->db->Insert_ID();
	return $result;
}

?>