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
		log_video_conversion(0, $jconf['jobid_media_convert'], "-", "[ERROR] Cannot update master media status. SQL query failed.", trim($query), $err, 0, TRUE);
		return FALSE;
	}

	return TRUE;
}



?>
