<?php

// *************************************************************************
// *					function log_recording_conversion()		   		   *
// *************************************************************************
// Description: log required video conversion event to database and file.
// INPUTS:
//	- $rec_id: recording ID
//	- $job: job name issuing log entry
//	- $action: phase of conversion (INIT, THUMBS, AUDIO, VIDEO)
//	- $status: log message
//	- $command: executed command (if applies)
//	- $data: executed command output (if applies)
//	- $duration: length of operation
// OUTPUTS:
//	- Boolean:
//	  o FALSE: operation failed
//	  o TRUE: operation OK
//	- Others:
//	  o Log entries (file and database)
function log_recording_conversion($rec_id, $job, $action, $status, $command, $data, $duration, $log2mail) {
global $app, $jconf, $debug, $db, $uploader_user;

	$values = Array(
		'timestamp'					=> date("Y-m-d H:i:s"),
		'node'						=> $jconf['node'],
		'recordingid'				=> $rec_id,
		'job'						=> $job,
		'action'					=> $action,
		'status'					=> $status,
		'command'					=> $command,
		'data'						=> mb_convert_encoding($data, 'utf-8'),
		'duration'					=> $duration
	);

	$recording_logs = $app->bootstrap->getModel('recording_logs');
	$recording_logs->insert($values);

	$msg = "";
	$msg .= "NODE: " . $jconf['node'] . "\n";
	$msg .= "JOB: " . $job . "\n";
	if ( !empty($uploader_user['email']) && !empty($uploader_user['nickname']) && !empty($uploader_user['userid']) ) {
		$msg .= "UPLOADER: " . $uploader_user['email'] . " (nick: " . $uploader_user['nickname'] . ", id: " . $uploader_user['userid'] . ")\n";
	}
	$msg .= "RECORDING: " . $rec_id . "\n";
	$msg .= "ACTION: ". $action . "\n";
	$msg .= "STATUS MESSAGE: \n\n" . $status . "\n";
	if ( !empty($command) && ( $command != "-" ) ) {
		$msg .= "\nCOMMAND: " . $command . "\n";
	}
	if ( !empty($data) && ( $data != "-" ) ) {
		$msg .= "\nDATA: " . $data . "\n";
	}

//$log2mail = false;
	$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", $msg, $log2mail);

	return TRUE;
}

// *************************************************************************
// *					function print_audio_info()			   			   *
// *************************************************************************
// Description: Returns formatted audio track information based on
//   $audio_lq and $audio_hq arrays.
function print_audio_info($audio) {

	if ( isset($audio['name']) ) {
		$log_msg  = "[INFO] " . $audio['name'] . ":\n";
	} else {
		$log_msg  = "[INFO] Summary:\n";
	}
	if ( isset($audio['playtime']) ) $log_msg .= "Playtime: " . secs2hms($audio['playtime']) . "\n";
	if ( isset($audio['format']) ) $log_msg .= "Format: " . $audio['format'] . "\n";
	if ( isset($audio['audio_codec']) ) $log_msg .= "Audio codec: " . $audio['audio_codec'] . "\n";
	$log_msg .= "Audio quality: ";
	if ( isset($audio['audio_ch']) ) $log_msg .= $audio['audio_ch'] . "ch ";
	if ( isset($audio['audio_srate']) ) $log_msg .= $audio['audio_srate'] . "Hz ";
	if ( isset($audio['audio_bitrate']) ) $log_msg .= "@ " . $audio['audio_bitrate'] . "Kbps\n";

/*	if ( isset($audio['source_file']) ) {
		$log_msg .= "Source file: " . $audio['source_file'] . "\n";
	}
	if ( isset($audio['output_file']) ) {
		$log_msg .= "Target file: " . $audio['output_file'] . "\n";
	} */

	return $log_msg;
}

// *************************************************************************
// *				function print_recording_info()						   *
// *************************************************************************
// Description: Returns formatted video track information based on
//   content array.
function print_recording_info($recording) {

	if ( isset($recording['name']) ) {
		$log_msg  = "[INFO] " . $recording['name'] . ":\n";
	} else {
		$log_msg  = "[INFO] Stream summary:\n";
	}
	if ( isset($recording['playtime']) ) $log_msg .= "Playtime: " . secs2hms($recording['playtime']) . "\n";
	if ( isset($recording['format']) ) $log_msg .= "Format: " . $recording['format'] . "\n";
	if ( isset($recording['video_codec']) ) $log_msg .= "Video codec: " . $recording['video_codec'] . "\n";
	$log_msg .= "Video quality: ";
	if ( isset($recording['res_x']) && isset($recording['res_y']) ) $log_msg .= $recording['res_x'] . "x" . $recording['res_y'];
	if ( isset($recording['fps']) ) $log_msg .= "@" . $recording['fps'] . "fps\n";
	if ( isset($recording['video_bitrate']) ) $log_msg .= "Video bitrate: " . ceil( $recording['video_bitrate'] / 1000 ) . "Kbps\n";
	if ( isset($recording['scaler']) ) $log_msg .= "Resolution scaler: " . $recording['scaler'] . "\n";
	if ( isset($recording['video_bpp']) ) $log_msg .= "BPP: " . $recording['video_bpp'] . "\n";
	if ( isset($recording['interlaced']) ) $log_msg .= "Interlaced: " . (($recording['interlaced'] == 1)?"yes":"no") . "\n";
	if ( isset($recording['audio_codec']) ) $log_msg .= "Audio codec: " . $recording['audio_codec'] . "\n";
	$log_msg .= "Audio quality: ";
	if ( isset($recording['audio_ch']) ) $log_msg .= $recording['audio_ch'] . "ch ";
	if ( isset($recording['audio_srate']) ) $log_msg .= $recording['audio_srate'] . "Hz ";
	if ( isset($recording['audio_bitrate']) ) $log_msg .= "@ " . $recording['audio_bitrate'] . "Kbps\n";

	return $log_msg;
}




?>
