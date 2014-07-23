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

	$db = db_maintain();

	$data = substr(trim($data), 1, 255);

	// Check DB connection: do not log if does not exist
	$values = Array(
		'timestamp'					=> date("Y-m-d H:i:s"),
		'node'						=> $app->config['node_sourceip'],
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

	// Assemble log message
	$msg = "";
	$msg .= "NODE: " . $app->config['node_sourceip'] . "\n";
	$msg .= "SITE: " . $app->config['baseuri'] . "\n";

	$msg .= "JOB: " . $job . "\n";
	if ( !empty($uploader_user['email']) && !empty($uploader_user['nickname']) && !empty($uploader_user['userid']) ) {
		$msg .= "UPLOADER: " . $uploader_user['email'] . " (nick: " . $uploader_user['nickname'] . ", id: " . $uploader_user['userid'] . ")\n";
	}
	$msg .= "RECORDING: " . $rec_id . "\n";
	$msg .= "ACTION: ". $action . "\n";
	$msg .= "STATUS MESSAGE: " . $status . "\n";
	if ( !empty($command) && ( $command != "-" ) ) {
		$msg .= "\nCOMMAND: " . $command . "\n";
	}
	if ( !empty($data) && ( $data != "-" ) ) {
		$msg .= "\nDATA: " . $data . "\n";
	}

	$debug->log($jconf['log_dir'], $job . ".log", $msg, $log2mail);

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

	return $log_msg;
}

function printMediaInfo($recording, $profile) {

	$log_msg  = "[INFO] " . $profile['name'] . ":\n";

	$idx = "";
	if ( $recording['iscontent'] ) $idx = "content";

// Audio only
/*
array(4) {
  ["name"]=>
  string(10) "Audio only"
  ["audiochannels"]=>
  string(1) "2"
  ["audiosamplerate"]=>
  int(48000)
  ["audiobitrate"]=>
  int(128)
}
*/

	// General
	if ( isset($recording[$idx . 'masterlength']) ) $log_msg .= "Playtime: " . secs2hms($recording[$idx . 'masterlength']) . "\n";
	if ( isset($profile['filecontainerformat']) ) $log_msg .= "Format: " . $profile['filecontainerformat'] . "\n";

	// Audio related information
	if ( ( $recording[$idx . 'mastermediatype'] != "videoonly" ) and ( !empty($profile['audiocodec']) ) ) {
		if ( isset($profile['audiocodec']) ) $log_msg .= "Audio codec: " . $profile['audiocodec'] . "\n";
		$log_msg .= "Audio quality: ";
		if ( isset($recording['encodingparams']['audiochannels']) ) $log_msg .= $recording['encodingparams']['audiochannels'] . "ch ";
		if ( isset($recording['encodingparams']['audiosamplerate']) ) $log_msg .= $recording['encodingparams']['audiosamplerate'] . "Hz ";
		if ( isset($recording['encodingparams']['audiobitrate']) ) $log_msg .= "@ " . $recording['encodingparams']['audiobitrate'] . "Kbps\n";
	}

	// Video related information
/*
array(17) {
  ["videofps"]=>
  string(6) "23.976"
  ["resxdar"]=>
  float(624)
  ["resydar"]=>
  string(3) "352"
  ["SAR"]=>
  float(1.7727272727273)
  ["PAR"]=>
  float(1.0001538461538)
  ["DAR"]=>
  float(1.773)
  ["DAR_MN"]=>
  string(5) "1.773"
  ["scaler"]=>
  int(1)
  ["resx"]=>
  float(624)
  ["resy"]=>
  string(3) "352"
  ["videobpp_source"]=>
  float(0.22480990362935)
  ["videobpp"]=>
  string(4) "0.09"
  ["videobitrate"]=>
  float(473965.24032)

*/

	if ( ( $recording[$idx . 'mastermediatype'] != "audio" ) and !empty($profile['videocodec']) ) {
		if ( isset($profile['videocodec']) ) $log_msg .= "Video codec: " . $profile['videocodec'] . "\n";
		$log_msg .= "Video quality: ";
		if ( isset($recording['encodingparams']['resx']) && isset($recording['encodingparams']['resy']) ) $log_msg .= $recording['encodingparams']['resx'] . "x" . $recording['encodingparams']['resy'];
		if ( isset($recording['encodingparams']['videofps']) ) $log_msg .= "@" . $recording['encodingparams']['videofps'] . "fps\n";

		// Aspect ratios
		if ( isset($recording['encodingparams']['SAR']) ) $log_msg .= "SAR: " . round($recording['encodingparams']['SAR'], 5) . "\n";
		if ( isset($recording['encodingparams']['DAR']) ) $log_msg .= "DAR: " . round($recording['encodingparams']['DAR'], 5) . "\n";
		if ( isset($recording['encodingparams']['PAR']) ) $log_msg .= "PAR: " . round($recording['encodingparams']['PAR'], 5) . "\n";
		if ( isset($recording['encodingparams']['resxdar']) && isset($recording['encodingparams']['resydar']) ) $log_msg .= "DAR resolution: " . $recording['encodingparams']['resxdar'] . "x" . $recording['encodingparams']['resydar'] . "\n";
		if ( isset($recording['encodingparams']['scaler']) ) $log_msg .= "Resolution scaler: " . $recording['encodingparams']['scaler'] . "\n";

		if ( isset($recording['encodingparams']['videobitrate']) ) $log_msg .= "Video bitrate: " . ceil( $recording['encodingparams']['videobitrate'] / 1000 ) . "Kbps\n";
		if ( isset($recording['encodingparams']['videobpp']) ) $log_msg .= "BPP: " . $recording['encodingparams']['videobpp'] . "\n";
		if ( isset($recording[$idx . 'masterinterlaced']) ) $log_msg .= "Interlaced: " . (($recording[$idx . 'interlaced'] == 1)?"yes":"no") . "\n";
		if ( isset($recording['encodingparams']['pipresx']) and isset($recording['encodingparams']['pipresy']) ) $log_msg .= "PiP res: " . $recording['encodingparams']['pipresx'] . "x" . $recording['encodingparams']['pipresy'] . "\n";
		if ( isset($recording['encodingparams']['pipx']) and isset($recording['encodingparams']['pipy']) ) $log_msg .= "PiP pos: " . $recording['encodingparams']['pipx'] . "x" . $recording['encodingparams']['pipy'] . "\n";
	}

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

	// Aspect ratios
	if ( isset($recording['SAR']) ) $log_msg .= "SAR: " . round( $recording['SAR'], 5 ) . "\n";
	if ( isset($recording['DAR']) ) $log_msg .= "DAR: " . round( $recording['DAR'], 5 ) . "\n";
	if ( isset($recording['PAR']) ) $log_msg .= "PAR: " . round( $recording['PAR'], 5 ) . "\n";
	if ( isset($recording['res_x_dar']) && isset($recording['res_y_dar']) ) $log_msg .= "DAR resolution: " . $recording['res_x_dar'] . "x" . $recording['res_y_dar'] . "\n";
	if ( isset($recording['scaler']) ) $log_msg .= "Resolution scaler: " . $recording['scaler'] . "\n";

	if ( isset($recording['video_bitrate']) ) $log_msg .= "Video bitrate: " . ceil( $recording['video_bitrate'] / 1000 ) . "Kbps\n";
	if ( isset($recording['video_bpp']) ) $log_msg .= "BPP: " . $recording['video_bpp'] . "\n";
	if ( isset($recording['interlaced']) ) $log_msg .= "Interlaced: " . (($recording['interlaced'] == 1)?"yes":"no") . "\n";
	if ( isset($recording['pip_res_x']) and isset($recording['pip_res_y']) ) $log_msg .= "PiP res: " . $recording['pip_res_x'] . "x" . $recording['pip_res_y'] . "\n";
	if ( isset($recording['pip_x']) and isset($recording['pip_y']) ) $log_msg .= "PiP pos: " . $recording['pip_x'] . "x" . $recording['pip_y'] . "\n";
	if ( isset($recording['audio_codec']) ) $log_msg .= "Audio codec: " . $recording['audio_codec'] . "\n";
	$log_msg .= "Audio quality: ";
	if ( isset($recording['audio_ch']) ) $log_msg .= $recording['audio_ch'] . "ch ";
	if ( isset($recording['audio_srate']) ) $log_msg .= $recording['audio_srate'] . "Hz ";
	if ( isset($recording['audio_bitrate']) ) $log_msg .= "@ " . $recording['audio_bitrate'] . "Kbps\n";

	return $log_msg;
}

function log_document_conversion($doc_id, $rec_id, $job, $action, $status, $command, $data, $duration, $log2mail) {
global $app, $jconf, $debug, $db, $uploader_user;

	$db = db_maintain();

	$values = Array(
		'timestamp'					=> date("Y-m-d H:i:s"),
		'node'						=> $app->config['node_sourceip'],
		'attacheddocumentid'		=> $doc_id,
		'recordingid'				=> $rec_id,
		'job'						=> $job,
		'action'					=> $action,
		'status'					=> $status,
		'command'					=> $command,
		'data'						=> mb_convert_encoding($data, 'utf-8'),
		'duration'					=> $duration
	);

	$document_logs = $app->bootstrap->getModel('document_logs');
	$document_logs->insert($values);

	$msg = "";
	$msg .= "NODE: " . $app->config['node_sourceip'] . "\n";
	$msg .= "SITE: " . $app->config['baseuri'] . "\n";

	$msg .= "JOB: " . $job . "\n";
	if ( !empty($uploader_user['email']) && !empty($uploader_user['nickname']) && !empty($uploader_user['userid']) ) {
		$msg .= "UPLOADER: " . $uploader_user['email'] . " (nick: " . $uploader_user['nickname'] . ", id: " . $uploader_user['userid'] . ")\n";
	}
	$msg .= "DOCUMENT: " . $doc_id . " (RECORDING: " . $rec_id . ")\n";
	$msg .= "ACTION: ". $action . "\n";
	$msg .= "STATUS MESSAGE: " . $status . "\n";
	if ( !empty($command) && ( $command != "-" ) ) {
		$msg .= "\nCOMMAND: " . $command . "\n";
	}
	if ( !empty($data) && ( $data != "-" ) ) {
		$msg .= "\nDATA: " . $data . "\n";
	}

	$debug->log($jconf['log_dir'], $job . ".log", $msg, $log2mail);

	return TRUE;
}

?>
