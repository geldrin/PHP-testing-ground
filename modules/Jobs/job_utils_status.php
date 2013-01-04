<?php

// *************************************************************************
// *				function update_db_video_status()		   			   *
// *************************************************************************
// Description: update database status for video
// INPUTS:
//	- AdoDB DB link in $db global variable
//	- $rec_id: recording ID
//	- $status: status (see defines)
// OUTPUTS:
//	- Boolean:
//	  o FALSE: failed (error cause logged in DB and local files)
//	  o TRUE: OK
function update_db_recording_status($rec_id, $status) {
global $app, $jconf, $db;

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

// *************************************************************************
// *				function update_db_mastervideo_status()	   			   *
// *************************************************************************
// Description: update database status for video
// INPUTS:
//	- AdoDB DB link in $db global variable
//	- $rec_id: recording ID
//	- $status: status (see defines)
// OUTPUTS:
//	- Boolean:
//	  o FALSE: failed (error cause logged in DB and local files)
//	  o TRUE: OK
function update_db_masterrecording_status($rec_id, $status) {
global $app, $jconf, $db;

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

// *************************************************************************
// *				function update_db_mediainfo()			   			   *
// *************************************************************************
// Description: update recording element with converted media information
// INPUTS:
//	- AdoDB DB link in $db global variable
//	- $rec_id: recording ID
//	- $rec_element_id: recording element ID
//	- $video_hq, $video_lq: HQ and LQ video information
// OUTPUTS:
//	- Boolean:
//	  o FALSE: failed (error cause logged in DB and local files)
//	  o TRUE: OK
function update_db_mediainfo($recording, $mobile_lq, $mobile_hq, $video_lq, $video_hq) {
global $app, $jconf, $db;

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

// *************************************************************************
// *				function update_db_content_status()		   			   *
// *************************************************************************
// Description: update database status for content
// INPUTS:
//	- AdoDB DB link in $db global variable
//	- $id: recording ID
//	- $status: status (see defines)
// OUTPUTS:
//	- Boolean:
//	  o FALSE: failed (error cause logged in DB and local files)
//	  o TRUE: OK
function update_db_content_status($id, $status) {
global $db;

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

// *************************************************************************
// *				function update_db_mobile_status()		   			   *
// *************************************************************************
// Description: update database status for mobile version
// INPUTS:
//	- global/$db: AdoDB DB resource
//	- $id: recording ID
//	- $status: status (see defines)
// OUTPUTS:
//	- Boolean:
//	  o FALSE: failed (error cause logged in DB and local files)
//	  o TRUE: OK
function update_db_mobile_status($id, $status) {
global $db;

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


// *************************************************************************
// *				function update_db_mastercontent_status()	   		   *
// *************************************************************************
// Description: update database status for video
// INPUTS:
//	- AdoDB DB link in $db global variable
//	- $id: recording ID
//	- $status: status (see defines)
// OUTPUTS:
//	- Boolean:
//	  o FALSE: failed (error cause logged in DB and local files)
//	  o TRUE: OK
function update_db_mastercontent_status($id, $status) {
 global $db;

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

// *************************************************************************
// *				function update_db_contentinfo()			   		   *
// *************************************************************************
// Description: update recording element with converted media information
// INPUTS:
//	- AdoDB DB link in $db global variable
//	- $id: recording ID
//	- $content_info_hq, $content_info_lq: HQ and LQ content information
// OUTPUTS:
//	- Boolean:
//	  o FALSE: failed (error cause logged in DB and local files)
//	  o TRUE: OK
function update_db_contentinfo($id, $content_info_lq, $content_info_hq, $mobile_info_lq, $mobile_info_hq) {
global $jconf, $db;

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

// VCR: update recording link
function update_db_vcr_reclink_status($id, $status) {
global $db, $jconf;

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

function update_db_attachment_status($id, $status) {
global $jconf, $db;

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


function update_db_avatar_status($userid, $status) {
global $jconf, $db;

	$query = "
		UPDATE
			users
		SET
			avatarstatus = '" . $status . "'
		WHERE
			id = " . $userid;

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		log_document_conversion(0, 0, $jconf['jobid_upload_finalize'], "-", "[ERROR] Cannot update document status. SQL query failed.", trim($query), $err, 0, TRUE);
		return FALSE;
	}

	return TRUE;
}

// *************************************************************************
// *			function update_db_attachment_documentcache()	   	   	   *
// *************************************************************************
// Description: update document cache
// INPUTS:
//	- AdoDB DB link in $db global variable
//	- $attachment_id: attachment ID
//	- $rec_id: recording element ID
//	- $status: status (see defines)
// OUTPUTS:
//	- Boolean:
//	  o FALSE: failed (error cause logged in DB and local files)
//	  o TRUE: OK
function update_db_attachment_documentcache($attachment_id, $documentcache) {
 global $db;

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
    log_document_conversion($recording_id, $attachment_id, "-", "-", "[ERROR] Cannot update attachment document cache. SQL query failed.", trim($query), $err, 0, TRUE);
    return FALSE;
  }

  return TRUE;
}

?>
