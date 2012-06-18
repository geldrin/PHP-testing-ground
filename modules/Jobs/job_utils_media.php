<?php

// *************************************************************************
// *				function ffmpeg_convert()		   					   *
// *************************************************************************
// Description: Convert video track using ffmpeg.
// INPUTS:
//	- $input_file: input media file
//	- $profile: media profile defined in configuration file
// OUTPUTS:
//	- Status messages: in $err[] array
//	  o $err['code']: boolean status of operation (TRUE/FALSE)
//	  o $err['command']: executed shell command line
//	  o $err['command_output']: command output
//	  o $err['duration']: operation length in seconds
//	  o $err['result']: command result returned by shell
//	- Others:
//	  o Converted media file as $output_file
//	  o Log entries (file and database)
// TODO:
//	- FLV is converted instead of F4V!
function ffmpeg_convert($media_info, $profile) {
global $jconf;

	$err = array();

	// Audio parameters
	if ( empty($profile['audio_codec']) || empty($profile['audio_ch']) || empty($media_info['audio_bitrate']) || empty($media_info['audio_srate']) ) {
		$ffmpeg_audio = " -an ";
	} else {
		$ffmpeg_audio = "-async " . $jconf['ffmpeg_async_frames'] . " -c:a " . $profile['audio_codec'] . " -ac " . $profile['audio_ch'] . " -b:a " . $media_info['audio_bitrate'] . "k -ar " . $media_info['audio_srate'] . " ";
	}

	if ( empty($profile['video_codec']) || empty($media_info['res_x']) || empty($media_info['res_y']) || empty($media_info['video_bitrate']) ) {
		$ffmpeg_video = " -vn ";
	} else {

		// Video bitrate
		$ffmpeg_bw = " -b:v " . 10 * ceil($media_info['video_bitrate'] / 10000) . "k";

		// Resize
		$resize = " -s " . $media_info['res_x'] . "x" . $media_info['res_y'];

		// FPS
		$fps = "";
		if ( ( $media_info['fps'] > 0 ) || ( !empty($media_info['fps'] ) ) ) {
			$fps = " -r " . $media_info['fps'] ;
		}

		// Deinterlace
		$deint = "";
		if ( $media_info['interlaced']  > 0 ) {
			$deint = " -deinterlace";
		}

		$ffmpeg_video = "-c:v libx264 " . $profile['codec_profile'] . $resize . $deint . $fps . $ffmpeg_bw;

	}

	// 1 pass encoding
	if ( $profile['passes'] < 2 ) {
		// Execute ffmpeg command
		$command  = $jconf['nice'] . " ffmpeg -y -i " . $media_info['source_file'] . " -v " . $jconf['ffmpeg_loglevel'] . " " . $jconf['ffmpeg_flags'] . " ";
		$command .= $ffmpeg_audio;
		$command .= $ffmpeg_video;
		$command .= " -threads " . $jconf['ffmpeg_threads'] . " -f " . $profile['format'] . " " . $media_info['output_file'] . " 2>&1";

		$time_start = time();
		$output = runExternal($command);
		$err['duration'] = time() - $time_start;
		$mins_taken = round( $err['duration'] / 60, 2);
		$err['command'] = $command;
		$err['command_output'] = $output['cmd_output'];
		$err['result'] = $output['code'];
		// ffmpeg returns -1 (?)
//echo "errcode: " . $err['result'] . "\n";
		if ( $err['result'] < 0 ) $err['result'] = 0;

		// ffmpeg terminated with error
		if ( $err['result'] != 0 ) {
			$err['code'] = FALSE;
			$err['message'] = "[ERROR] ffmpeg conversion FAILED";
			return $err;
		}

		$err['code'] = TRUE;
		$err['message'] = "[OK] ffmpeg conversion OK (in " . $mins_taken . " mins)";
	}

/*
‘-pass n’

Select the pass number (1 or 2). It is used to do two-pass video encoding. The statistics of the video are recorded in the first pass into a log file (see also the option -passlogfile), and in the second pass that log file is used to generate the video at the exact requested bitrate. On pass 1, you may just deactivate audio and set output to null, examples for Windows and Unix:  	ffmpeg -i foo.mov -c:v libxvid -pass 1 -an -f rawvideo -y NUL
ffmpeg -i foo.mov -c:v libxvid -pass 1 -an -f rawvideo -y /dev/null

 ‘-passlogfile prefix (global)’

Set two-pass log file name prefix to prefix, the default file name prefix is “ffmpeg2pass”. The complete file name will be ‘PREFIX-N.log’, where N is a number specific to the output stream

*/

	return $err;

}

// *************************************************************************
// *				function calculate_video_scaler()			   		   *
// *************************************************************************
// Description: Returns new resolution and scaler according to bounding box
// and actual resolution
// INPUTS:
//	$resx, $resy: current X and Y resolution
//	$bbox: bounding box resolution in "123x456" format
// OUTPUTS:
//	array (
//		'scaler' => $scaler,		// New scaler for keeping aspect ratio
//		'x'		 => $resx_new,		// New X resolution
//		'y'		 => $resy_new,		// New Y resolution
//	);
function calculate_video_scaler($resx, $resy, $bbox) {
global $jconf;

	$scaler = 1;
	$maxres = explode("x", $bbox, 2);

	// Check if video is larger than bounding box
	if ( ( $resx > $maxres[0] ) || ( $resy > $maxres[1] ) ) {
		$scaler_x = $maxres[0] / $resx;
		$scaler_y = $maxres[1] / $resy;
		// Select minimal scaler to fit bounding box
		$scaler = min($scaler_x, $scaler_y);
		$resx_new = $jconf['video_res_modulo'] * floor(($resx * $scaler) / $jconf['video_res_modulo']);
		$resy_new = $jconf['video_res_modulo'] * floor(($resy * $scaler) / $jconf['video_res_modulo']);
	} else {
		// Recalculate resolution with codec modulo if needed (fix for odd resolutions)
		$resx_new = $resx;
		$resy_new = $resy;
		if ( ( ( $resx % $jconf['video_res_modulo'] ) > 0 ) || ( ( $resy % $jconf['video_res_modulo'] ) > 0 ) ) {
			$resx_new = $jconf['video_res_modulo'] * floor($resx / $jconf['video_res_modulo']);
			$resy_new = $jconf['video_res_modulo'] * floor($resy / $jconf['video_res_modulo']);
		}
	}

	$new_resolution = array (
		'scaler' => $scaler,
		'x'		 => $resx_new,
		'y'		 => $resy_new,
	);

	return $new_resolution;
}

// *************************************************************************
// *					function calculate_mobile_pip()					   *
// *************************************************************************
// Description: calculate mobile picture-in-picture resolution values
// INPUTS:
//	- $mastervideores: media resolution
//	- $contentmastervideores: content resolution
//	- $recording_info: recording info array 
//	- $profile: conversion profile 
// OUTPUTS:
//	- Boolean:
//	  o FALSE: encoding failed (error cause logged in DB and local files)
//	  o TRUE: encoding OK
//	- $recording_info: info updated
function calculate_mobile_pip($mastervideores, $contentmastervideores, &$recording_info, $profile) {
global $jconf;

	// Content resolution
	$tmp = explode("x", $contentmastervideores, 2);
	$c_resx = $tmp[0];
	$c_resy = $tmp[1];
	$c_resnew = calculate_video_scaler($c_resx, $c_resy, $profile['video_bbox']);
	$recording_info['scaler'] = $c_resnew['scaler'];
	$recording_info['res_x'] = $c_resnew['x'];
	$recording_info['res_y'] = $c_resnew['y'];

	// Media resolution
//	$tmp = explode("x", $contentmastervideores, 2);
//	$recording_info['pip_res_x'] = $jconf['video_res_modulo'] * floor(($tmp[0] * $profile['pip_resize']) / $jconf['video_res_modulo']);
//	$recording_info['pip_res_y'] = $jconf['video_res_modulo'] * floor(($tmp[1] * $profile['pip_resize']) / $jconf['video_res_modulo']);
	$tmp = explode("x", $mastervideores, 2);
	$resx = $tmp[0];
	$resy = $tmp[1];
	$scaler_pip = $resy / $resx;
	$recording_info['pip_res_x'] = $jconf['video_res_modulo'] * floor(($recording_info['res_x'] * $profile['pip_resize']) / $jconf['video_res_modulo']);
	$recording_info['pip_res_y'] = $jconf['video_res_modulo'] * floor(($recording_info['pip_res_x'] * $scaler_pip) / $jconf['video_res_modulo']);

	// Calculate PiP position
	$pip_align = ceil($recording_info['res_x'] * $profile['pip_align']);
	if ( $profile['pip_posx'] == "left" ) $recording_info['pip_x'] = 0 + $pip_align;
	if ( $profile['pip_posx'] == "right" ) $recording_info['pip_x'] = $recording_info['res_x'] - $recording_info['pip_res_x'] - $pip_align;
	if ( $profile['pip_posy'] == "up" ) $recording_info['pip_y'] = 0 + $pip_align;
	if ( $profile['pip_posy'] == "down" ) $recording_info['pip_y'] = $recording_info['res_y'] - $recording_info['pip_res_y'] - $pip_align;

//	if ( $profile['pip_posx'] == "left" ) $recording_info['pip_x'] = 0 + $profile['pip_align'];
//	if ( $profile['pip_posx'] == "right" ) $recording_info['pip_x'] = $recording_info['res_x'] - $recording_info['pip_res_x'] - $profile['pip_align'];
//	if ( $profile['pip_posy'] == "up" ) $recording_info['pip_y'] = 0 + $profile['pip_align'];
//	if ( $profile['pip_posy'] == "down" ) $recording_info['pip_y'] = $recording_info['res_y'] - $recording_info['pip_res_y'] - $profile['pip_align'];

	return TRUE;
}

// *************************************************************************
// *					function convert_video()						   *
// *************************************************************************
// Description: Generate video file based on profile
// INPUTS:
//	- $recording: recording element information
// OUTPUTS:
//	- Boolean:
//	  o FALSE: encoding failed (error cause logged in DB and local files)
//	  o TRUE: encoding OK
//	- $recording: all important info is injected into recording array
//	- $profile: encoding profile, see config_jobs.php
//	- $recording_info: info on encoded media
//	- Others:
//	  o logs in local logfile and SQL DB table recordings_log
//	  o updated 'recordings' status field
function convert_video($recording, $profile, &$recording_info) {
global $app, $jconf, $global_log;

	// Update watchdog timer
	$app->watchdog();

	$c_idx = "";
	if ( $profile['type'] == "content" ) $c_idx = "content";

	$recording_info = array();

	if ( $recording[$c_idx . 'mastermediatype'] == "audio" ) {
		return TRUE;
	}

	// Temp directory
	$temp_directory = $recording['temp_directory'];

	// Local master file name
	$recording_info['input_file'] = $recording['source_file'];

	// Basic video data for preliminary checks
	$video_in = array();
	$video_in['playtime'] = floor($recording[$c_idx . 'masterlength']);
	$res = explode("x", strtolower($recording[$c_idx . 'mastervideores']), 2);
	$video_in['res_x'] = $res[0];
	$video_in['res_y'] = $res[1];
	$video_in['bpp'] = $recording[$c_idx . 'mastervideobitrate'] / ( $video_in['res_x'] * $video_in['res_y'] * $recording[$c_idx . 'mastervideofps'] );
	$video_in['interlaced'] = 0;
	if ( $recording[$c_idx . 'mastervideoisinterlaced'] > 0 ) $video_in['interlaced'] = 1;

	// Max resolution check (fraud check)
	$maxres = explode("x", strtolower($jconf['video_max_res']), 2);
	if ( ( $video_in['res_x'] > $maxres[0] ) || ( $video_in['res_y'] > $maxres[1]) ) {
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_conv_video'], "[ERROR] Invalid video resolution: " . $video_in['res_x'] . "x" . $video_in['res_y'] . "\n", "-", "-", 0, TRUE);
		return FALSE;
	}

	// FPS check and conversion
	if ( $recording[$c_idx . 'mastervideofps'] > $jconf['video_max_fps'] ) {
		// Log if video FPS is higher than expected (for future finetune of interlace detection algorithm)
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_conv_video'], "[WARNING] Video FPS too high: " . $recording[$c_idx . 'mastervideofps'] . "\n", "-", "-", 0, TRUE);
	}

	// Calculate audio parameters
	$audio_bitrate = 0;
	$audio_sample_rate = 0;
	if ( $recording[$c_idx . 'mastermediatype'] != "videoonly" ) {

		// Samplerate settings: check if applies (f4v possible samplerates: 22050Hz, 44100Hz and 48000Hz)
		$smpl_rate = $recording[$c_idx . 'masteraudiofreq'];
		if ( ( $smpl_rate == 22050 ) or ( $smpl_rate == 44100 ) or ( ( $smpl_rate == 48000 ) and ( $profile['audio_codec'] == "libfaac" ) ) ) {
			$audio_sample_rate = $smpl_rate;
		} else {
			// Should not occur to have different sample rate from aboves
			if ( ( $smpl_rate > 22050 ) && ( $smpl_rate <= 44100 ) ) {
				$audio_sample_rate = 44100;
			} else {
				if ( $smpl_rate <= 22050 ) {
					$audio_sample_rate = 22050;
				} elseif ( ( $smpl_rate >= 44100 ) && ( $smpl_rate < 48000 ) ) {
					$audio_sample_rate = 44100;
				} else {
					// ffmpeg only allows 22050/44100Hz sample rate mp3 with f4v, 48000Hz only possible with AAC
					if ( $profile['audio_codec'] == "libmp3lame" ) {
						$audio_sample_rate = 44100;
					} else {
						$audio_sample_rate = 48000;
					}
				}
			}
		}

		// Bitrate settings for audio
		$audio_bitrate_perchannel = $profile['audio_bw_ch'];
		if ( $audio_sample_rate <= 22050 ) $audio_bitrate_perchannel = 32;
		// Calculate number of channels
		$recording_info['audio_ch'] = $profile['audio_ch'];
		if ( $recording[$c_idx . 'masteraudiochannels'] < $profile['audio_ch'] ) {
			$recording_info['audio_ch'] = $recording[$c_idx . 'masteraudiochannels'];
		}
		$audio_bitrate = $profile['audio_ch'] * $audio_bitrate_perchannel;

		// Set audio information
		$recording_info['audio_codec'] = $profile['audio_codec'];
		$recording_info['audio_srate'] = $audio_sample_rate;
		$recording_info['audio_bitrate'] = $audio_bitrate;
	}

	// Calculate video parameters
	//// Basics
	$recording_info['name'] = $profile['name'];
	$recording_info['source_file'] = $recording['source_file'];
	$recording_info['format'] = $profile['format'];
	$recording_info['video_codec'] = $profile['video_codec'];
	$recording_info['playtime'] = $video_in['playtime'];
	$recording_info['fps'] = $recording[$c_idx . 'mastervideofps'];
	$recording_info['interlaced'] = $video_in['interlaced'];
	$recording_info['video_bpp'] = $profile['video_bpp'];
	//// New resolution/scaler according to profile bounding box
	$tmp = calculate_video_scaler($video_in['res_x'], $video_in['res_y'], $profile['video_bbox']);
	$recording_info['scaler'] = $tmp['scaler'];
	$recording_info['res_x'] = $tmp['x'];
	$recording_info['res_y'] = $tmp['y'];
	//// Calculate bitrate and maximize it to avoid too high values
/*	if ( $video_in['bpp'] < $profile['video_bpp'] ) {
		$recording_info['video_bpp'] = round($video_in['bpp'], 2);
	}
echo "bpp profile: " . $profile['video_bpp'] . " | orig: " . $video_in['bpp'] . " | chosen: " . $recording_info['video_bpp'] . "\n"; */
	$recording_info['video_bitrate'] = $recording_info['video_bpp'] * $recording_info['res_x'] * $recording_info['res_y'] * $recording_info['fps'];
	if ( $recording_info['video_bitrate'] > $jconf['video_max_bw'] ) $recording_info['video_bitrate'] = $jconf['video_max_bw'];
	//// Target filename
	$extension = $profile['format'];
	if ( $extension == "flv" ) $extension = "f4v";
	$recording_info['output_file'] = $temp_directory . $recording['id'] . $profile['file_suffix'] . "." . $extension;

	// Log input and target file details
	$log_msg = print_recording_info($recording_info);
	$global_log .= $log_msg. "\n";

	// Update watchdog timer
	$app->watchdog();

	// Video conversion execution
	$err = ffmpeg_convert($recording_info, $profile);
	if ( !$err['code'] ) {
		$msg = $err['message'] . "\n" . $profile['name'] . " conversion failed.\nSource file: " . $recording_info['input_file'] . "\nDestination file: " . $recording_info['output_file'] . "\n\n" . $log_msg;
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_conv_video'], $msg, $err['command'], $err['command_output'], $err['duration'], TRUE);
		return FALSE;
	} else {
		$msg = $err['message'] . "\n" . $profile['name'] . " conversion OK.\nSource file: " . $recording_info['input_file'] . "\nDestination file: " . $recording_info['output_file'] . "\n\n" . $log_msg;
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_conv_video'], $msg, $err['command'], $err['command_output'], $err['duration'], FALSE);
		$global_log .= $profile['name'] . " conversion in " . secs2hms($err['duration']) . " time.\n";
	}

	// Update watchdog timer
	$app->watchdog();

	$global_log .= "\n";

	return TRUE;
}

?>