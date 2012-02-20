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

//	$recordingObj = $app->bootstrap->getModel('recordings');
//	$recordingObj->select($rec_id);
//	$recordingObj->updateChannelIndexPhotos();

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
		$query .= "videoresmobile = \"" . $mobile_lq['res_x'] . "x" . $mobile_lq['res_y'] . "\"";
	} else {
		// Should never happen
		$query .= "videoresmobile = videoresmobile";
	}

	// Mobile HQ
/*	if ( !empty($mobile_hq) ) {
		$query .= ", videoresmobile_hq = \"" . $mobile_hq['res_x'] . "x" . $mobile_hq['res_y'] . "\"";
	} */

	if ( !empty($video_lq) ) {
		$query .= ", videoreslq = \"" . $video_lq['res_x'] . "x" . $video_lq['res_y'] . "\"";
	}

	if ( !empty($video_hq) ) {
		$query .= ", videoreshq = \"" . $video_hq['res_x'] . "x" . $video_hq['res_y'] . "\"";
	}

	if ( !empty($recording['thumbnail_indexphotofilename']) && !empty($recording['thumbnail_numberofindexphotos']) ) {
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



?>
