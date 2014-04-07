<?php

// *************************************************************************
// *				function ffmpeg_qt-faststart()		   				   *
// *************************************************************************
// Description: convert metadata for safer player compatibility
function ffmpeg_qtfaststart($input_file) {
 global $jconf;

	$err = array();

	$temp_file = $jconf['media_dir'] . rand(10000, 99999) . ".mp4";

	if ( file_exists($temp_file) ) {
		$err['code'] = FALSE;
		$err['message'] = "[ERROR] qt-faststart metadata conversion FAILED. Temp file " . $temp_file . " already exists.";
		return $err;
	}

	// Call qt-faststart
	$command = "qt-faststart " . $input_file . " " . $temp_file;
//echo $command . "\n";
	$time_start = time();
	$output = runExternal($command);
	$err['duration'] = time() - $time_start;
	$mins_taken = round( $err['duration'] / 60, 2);

	$err['command'] = $command;
	$err['command_output'] = $output['cmd_output'];
	$err['result'] = $output['code'];

//var_dump($err['result']);

	$filesize_diff = abs(filesize($temp_file) - filesize($input_file));
// Wrong error codes from qt-faststart?
//	if ( $err['result'] != 0 ) {
// If no result file or result file's size is very different
	if ( !file_exists($temp_file) or ( $filesize_diff > ( 0.05 * filesize($input_file) ) ) ) {
		// Conversion failed, but use unconverted media file instead???
		$err['code'] = FALSE;
		$err['message'] = "[ERROR] qt-faststart metadata conversion FAILED. ERR code = " . $err['result'];
		return $err;
	}

	// Remove original file
	if ( !unlink($input_file) ) {
		$err['code'] = FALSE;
		$err['message'] = "[ERROR] Cannot remove file: " . $input_file;
		unlink($temp_file);
		return $err;
	}

	// Rename temp file to original file's name
	if ( !rename($temp_file, $input_file) ) {
		$err['code'] = FALSE;
		$err['message'] = "[ERROR] Cannot rename temp file: " . $temp_file . " to " . $input_file;
		return $err;
	}

	$err['code'] = TRUE;
	$err['message'] = "[OK] qt-faststart conversion OK (in " . $mins_taken . " mins). ERR code = " . $err['result'];

	return $err;
}

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

		// Display Aspect Ratio should be updated (changed by conversion)
		$aspect = "";
		if ( !empty($media_info['DAR_MN']) ) {
			$aspect = " -aspect " . $media_info['DAR_MN'];
		}

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

		$ffmpeg_video = "-c:v libx264 " . $profile['codec_profile'] . $resize . $aspect . $deint . $fps . $ffmpeg_bw;

	}

	// 1 encoding
	if ( $profile['passes'] < 2 ) {
		// Execute ffmpeg command
		$command  = $profile['nice'] . " ffmpeg -y -i " . $media_info['source_file'] . " -v " . $jconf['ffmpeg_loglevel'] . " " . $jconf['ffmpeg_flags'] . " ";
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
		if ( $err['result'] < 0 ) $err['result'] = 0;

		// ffmpeg terminated with error or filesize suspiciously small
		if ( ( $err['result'] != 0 ) or ( filesize($media_info['output_file']) < 1000 ) ) {
			$err['code'] = FALSE;
			$err['message'] = "[ERROR] ffmpeg conversion FAILED";
			return $err;
		}

		$err['code'] = TRUE;
		$err['message'] = "[OK] ffmpeg conversion OK (in " . $mins_taken . " mins)";
	}

	return $err;
}

function ffmpegConvert($recording, $profile) {
global $jconf, $debug;

	$err = array();
	$err['code'] = false;
	$err['result'] = 0;
	$err['command'] = "";
	$err['command_output'] = "-";
	$err['message'] = "";
	$err['result'] = 0;

	// Encoding paramteres: an array for recording final parameters used for encoding
	$encodingparams = array();
	$encodingparams['name'] = $profile['name'];

// !!!!!!!!!!!!!!
	$idx = "";
	if ( $recording['iscontent'] ) $idx = "content";

	// Audio parameters
	if ( ( $recording[$idx . 'mastermediatype'] == "videoonly" ) || ( empty($profile['audiocodec']) ) ) {
		// No audio channels to be encoded
		$encodingparams['audiochannels'] = null;
		$encodingparams['audiosamplerate'] = null;
		$encodingparams['audiobitrate'] = null;
		$ffmpeg_audio = " -an ";
	} else {
		// Samplerate correction according to encoding profile
		$audiosamplerate = doSampleRateCorrectionForProfile($recording[$idx . 'masteraudiofreq'], $profile);
		// Bitrate settings for audio
		$audiochannels = $profile['audiomaxchannels'];
		if ( $recording['masteraudiochannels'] < $profile['audiomaxchannels'] ) $audiochannels = $recording[$idx . 'masteraudiochannels'];
		$audiobitrate = $audiochannels * $profile['audiobitrateperchannel'];

		$encodingparams['audiochannels'] = $audiochannels;
		$encodingparams['audiosamplerate'] = $audiosamplerate;
		$encodingparams['audiobitrate'] = $audiobitrate;

		// ffmpeg audio encoding settings
		$ffmpeg_audio = "-async " . $jconf['ffmpeg_async_frames'] . " -c:a " . $profile['audiocodec'] . " -ac " . $audiochannels . " -b:a " . $audiobitrate . "k -ar " . $audiosamplerate . " ";
	}

	// Video parameters
	if ( ( $recording[$idx . 'mastermediatype'] == "audio" ) || empty($profile['videocodec']) ) {
		$ffmpeg_video = " -vn ";
	} else {

		// FPS check and correction
		$fps = "";
		$encodingparams['videofps'] = $recording[$idx . 'mastervideofps'];
		if ( empty($recording[$idx . 'mastervideofps']) ) {
			$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "[WARNING] Media FPS is zero/empty. Resetting to " . $jconf['video_default_fps'] . ", might cause problem.", $sendmail = true);
			$encodingparams['videofps'] = $jconf['video_default_fps'];
		} else {
			// Max fps check
			if ( $recording[$idx . 'mastervideofps'] > $profile['videomaxfps'] ) {
				$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "[WARNING] Media fps too high: " . $recording[$idx . 'mastervideofps'] . " (max: " . $profile['videomaxfps'] . ")", $sendmail = true);
				switch ($recording[$idx . 'mastervideofps']) {
					case 60:
						$encodingparams['videofps'] = 30;
						break;
					case 50:
						$encodingparams['videofps'] = 25;
						break;
					default:
						$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "[WARNING] Strange video FPS? Will not apply video_maxfps profile value. Info:\n\nInput FPS: " . $recording[$idx . 'mastervideofps'] . " (profile limit: " . $profile['videomaxfps'] . ")", $sendmail = true);
				}
			}
		}
		$ffmpeg_fps = " -r " . $encodingparams['videofps'];

		// Max resolution check (fraud check)
		$videores = explode("x", strtolower($recording[$idx . 'mastervideores']), 2);
		$maxres = explode("x", strtolower($jconf['video_max_res']), 2);
		if ( ( $videores[0] > $maxres[0] ) || ( $videores[1] > $maxres[1]) ) {
			$err['message'] = "[ERROR] Invalid video resolution: " . $recording[$idx . 'mastervideores'];
			return $err;
		}

/*
// nem lehetnek üresek a recording táblában:
// - bitrate?
// - resolution?

*/

// !!!!!!!!!!!!!!!!

		//// Scaling 1: Display Aspect Ratio (DAR)
		// Display Aspect Ratio (DAR): check and update if not square pixel
		$encodingparams['resxdar'] = $videores[0];
		$encodingparams['resydar'] = $videores[1];
		if ( !empty($recording[$idx . 'mastervideodar'] ) ) {
			// Display Aspect Ratio: M:N
			$tmp = explode(":", $recording[$idx . 'mastervideodar'], 2);
			if ( count($tmp) == 1 ) $tmp[1] = 1;
			if ( !empty($tmp[0]) and !empty($tmp[1]) ) {
				$DAR_M = $tmp[0];
				$DAR_N = $tmp[1];
				// Pixel Aspect Ratio: square pixel?
				$PAR = ( $videores[1] * $DAR_M ) / ( $videores[0] * $DAR_N );
				if ( $PAR != 1 ) {
					// No square pixel, add ARs to logs
					// SAR: Source Aspect Ratio = Width/Height
					$encodingparams['SAR'] = $videores[0] / $videores[1];
					// PAR
					$encodingparams['PAR'] = $PAR;
					// DAR
					$encodingparams['DAR'] = $DAR_M / $DAR_N;
					$encodingparams['DAR_MN'] = $recording[$idx . 'mastervideodar'];
					// Y: keep fixed, X: recalculate
					$encodingparams['resxdar'] = round($encodingparams['resydar'] * $encodingparams['DAR']);
				}
			}
		}
		// ffmpeg aspect ratio parameter
		$ffmpeg_aspect = "";
		if ( !empty($encodingparams['DAR_MN']) ) $ffmpeg_aspect = " -aspect " . $encodingparams['DAR_MN'];

		//// Scaling 2: profile bounding box
		$tmp = calculate_video_scaler($encodingparams['resxdar'], $encodingparams['resydar'], $profile['videobboxsizex'] . "x" . $profile['videobboxsizey']);
		$encodingparams['scaler'] = $tmp['scaler'];
		$encodingparams['resx'] = $tmp['x'];
		$encodingparams['resy'] = $tmp['y'];
		// ffmpeg scaling parameter
		$ffmpeg_resize = " -s " . $encodingparams['resx'] . "x" . $encodingparams['resy'];

		//// Video bitrate calculation
		// Source Bit Per Pixel
		$encodingparams['videobpp_source'] = $recording[$idx . 'mastervideobitrate'] / ( $videores[0] * $videores[1] * $recording[$idx . 'mastervideofps'] );
		// BPP check and update: use input BPP if lower than profile BPP
		$encodingparams['videobpp'] = $profile['videobpp'];
		if ( $encodingparams['videobpp_source'] < $profile['videobpp'] ) $encodingparams['videobpp'] = $encodingparams['videobpp_source'];
		$encodingparams['videobitrate'] = $encodingparams['videobpp'] * $encodingparams['resx'] * $encodingparams['resy'] * $encodingparams['videofps'];
		if ( $encodingparams['videobitrate'] > $jconf['video_max_bw'] ) $encodingparams['videobitrate'] = $jconf['video_max_bw'];
		// ffmpeg video bitrate encoding parameter
		$ffmpeg_bw = " -b:v " . 10 * ceil($encodingparams['videobitrate'] / 10000) . "k";

		// Deinterlace
		$ffmpeg_deint = "";
		if ( $recording[$idx . 'mastervideoisinterlaced'] > 0 ) $ffmpeg_deint = " -deinterlace";

		// H.264 profile
		$ffmpeg_profile = "-profile:v " . $profile['ffmpegh264profile'] . " -preset:v " . $profile['ffmpegh264preset'];
	
		// ffmpeg video encoding parameters
		$ffmpeg_video = "-c:v libx264 " . $ffmpeg_profile . $ffmpeg_resize . $ffmpeg_aspect . $ffmpeg_deint . $ffmpeg_fps . $ffmpeg_bw;
	}

	// Final encoding parameters to return
	$err['value'] = $encodingparams;

	// 1 pass encoding
	if ( $profile['videopasses'] < 2 ) {
		// Execute ffmpeg command
		$command  = $jconf['encoding_nice'] . " ffmpeg -y -i " . $recording['master_filename'] . " -v " . $jconf['ffmpeg_loglevel'] . " " . $jconf['ffmpeg_flags'] . " ";
		$command .= $ffmpeg_audio;
		$command .= $ffmpeg_video;
		$command .= " -threads " . $jconf['ffmpeg_threads'] . " -f " . $profile['filecontainerformat'] . " " . $recording['output_file'] . " 2>&1";

		// Log ffmpeg command
		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "[INFO] ffmpeg conversion. Command:\n" . $command, $sendmail = false);

		$time_start = time();
		$output = runExternal($command);
		$err['duration'] = time() - $time_start;
		$mins_taken = round( $err['duration'] / 60, 2);
		$err['command'] = $command;
		$err['command_output'] = $output['cmd_output'];
		$err['result'] = $output['code'];
		if ( $err['result'] < 0 ) $err['result'] = 0;

		// Log ffmpeg output
		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "[INFO] ffmpeg conversion output:\n" . print_r($err['command_output'], true) . "\nError code: " . $err['result'], $sendmail = false);

		// ffmpeg terminated with error or filesize suspiciously small
		if ( ( $err['result'] != 0 ) or ( filesize($recording['output_file']) < 1000 ) ) {
			$err['code'] = false;
			$err['message'] = "[ERROR] ffmpeg conversion FAILED";
			return $err;
		}

		$err['code'] = true;
		$err['message'] = "[OK] ffmpeg conversion OK (in " . $mins_taken . " mins)";
	}

	return $err;
}

// Sample rate correction: to match codec requirements
function doSampleRateCorrectionForProfile($samplerate, $profile) {

	if ( ( $samplerate == 22050 ) or ( $samplerate == 44100 ) or ( ( $samplerate == 48000 ) and ( $profile['audiocodec'] == "libfaac" ) ) ) {
		$sampleratenew = $samplerate;
	} else {
		// Should not occur to have different sample rate from aboves
		if ( ( $samplerate > 22050 ) && ( $samplerate <= 44100 ) ) {
			$sampleratenew = 44100;
		} else {
			if ( $samplerate <= 22050 ) {
				$sampleratenew = 22050;
			} elseif ( ( $samplerate >= 44100 ) && ( $samplerate < 48000 ) ) {
				$sampleratenew = 44100;
			} else {
				// ffmpeg only allows 22050/44100Hz sample rate mp3 with f4v, 48000Hz only possible with AAC
				if ( ( $profile['audiocodec'] == "libmp3lame" ) and ( $profile['filecontainerformat'] == "f4v" ) ) {
					$sampleratenew = 44100;
				} else {
					$sampleratenew = 48000;
				}
			}
		}
	}

	return $sampleratenew;
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

	if ( empty($recording[$c_idx . 'mastervideofps']) ) {
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_conv_video'], "[ERROR] Undefined video fps.\n", "-", "-", 0, TRUE);
		return FALSE;
	}

	// Temp directory
	$temp_directory = $recording['temp_directory'];

	// Local master file name
	$recording_info['input_file'] = $recording['master_filename'];

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
	$recording_info['source_file'] = $recording['master_filename'];
	$recording_info['format'] = $profile['format'];
	$recording_info['video_codec'] = $profile['video_codec'];
	$recording_info['playtime'] = $video_in['playtime'];
	$recording_info['fps'] = round($recording[$c_idx . 'mastervideofps']);
	$recording_info['interlaced'] = $video_in['interlaced'];
	//// BPP value check and update: use input BPP if lower than profile BPP
	$recording_info['video_bpp'] = $profile['video_bpp'];
	if ( $video_in['bpp'] < $recording_info['video_bpp'] ) $recording_info['video_bpp'] = $video_in['bpp'];
	//// FPS: check if we exceed profile limit
	if ( $recording_info['fps'] > $profile['video_maxfps'] ){
		switch ($recording_info['fps']) {
			case 60:
				$recording_info['fps'] = 30;
				break;
			case 50:
				$recording_info['fps'] = 25;
				break;
			default:
				log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_conv_video'], "WARNING: Strange video FPS? Will not apply video_maxfps profile value. Info:\n\nInput FPS: " . $recording_info['fps'] . "\nProfile limit: " . $profile['video_maxfps'], "-", "-", 0, TRUE);
		}
	}

	//// Display Aspect Ratio (DAR): check and update if not square pixel
	$recording_info['res_x_dar'] = $video_in['res_x'];
	$recording_info['res_y_dar'] = $video_in['res_y'];
	if ( !empty($recording[$c_idx . 'mastervideodar'] ) ) {
		// Display Aspect Ratio: M:N
		$tmp = explode(":", $recording[$c_idx . 'mastervideodar'], 2);
		if ( count($tmp) == 1 ) $tmp[1] = 1;
		if ( !empty($tmp[0]) and !empty($tmp[1]) ) {
			$DAR_M = $tmp[0];
			$DAR_N = $tmp[1];
			// Pixel Aspect Ratio: square pixel?
			$PAR = ( $video_in['res_y'] * $DAR_M ) / ( $video_in['res_x'] * $DAR_N );
			if ( $PAR != 1 ) {
				// No square pixel, add ARs to logs
				// SAR: Source Aspect Ratio = Width/Height
				$recording_info['SAR'] = $video_in['res_x'] / $video_in['res_y'];
				// PAR
				$recording_info['PAR'] = $PAR;
				// DAR
				$recording_info['DAR'] = $DAR_M / $DAR_N;
				$recording_info['DAR_MN'] = $recording[$c_idx . 'mastervideodar'];
				// Y: keep fixed, X: recalculate
				$recording_info['res_x_dar'] = round($recording_info['res_y_dar'] * $recording_info['DAR']);
			}
		}
	}

	//// New resolution/scaler according to profile bounding box
	$tmp = calculate_video_scaler($recording_info['res_x_dar'], $recording_info['res_y_dar'], $profile['video_bbox']);
	$recording_info['scaler'] = $tmp['scaler'];
	$recording_info['res_x'] = $tmp['x'];
	$recording_info['res_y'] = $tmp['y'];
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

	$app->watchdog();

	$err = ffmpeg_qtfaststart($recording_info['output_file']);
	if ( !$err['code'] ) {
		log_recording_conversion($recording['id'], $jconf['jobid_media_convert'], $jconf['dbstatus_conv_video'], $err['message'] . "\nSource file: " . $recording_info['output_file'] . "\nDestination file: " . $recording_info['output_file'], $err['command'], $err['command_output'], $err['duration'], TRUE);
//		return FALSE;
	}

	$global_log .= "\nqt-faststart result: " . $err['message'] . "\n";

	// Update watchdog timer
	$app->watchdog();

	$global_log .= "\n";

	return TRUE;
}

function getEncodingProfile($encodingprofileid) {
global $db, $jconf, $debug, $myjobid;

	$db = db_maintain();

	$query = "
		SELECT
			id,
			parentid,
			name,
			shortname,
			type,
			mediatype,
			isdesktopcompatible,
			isioscompatible,
			isandroidcompatible,
			filenamesuffix,
			filecontainerformat,
			videocodec,
			videopasses,
			videobboxsizex,
			videobboxsizey,
			videomaxfps,
			videobpp,
			ffmpegh264profile,
			ffmpegh264preset,
			audiocodec,
			audiomaxchannels,
			audiobitrateperchannel,
			audiomode,
			pipenabled,
			pipcodecprofile,
			pipposx,
			pipposy,
			pipalign,
			pipsize
		FROM
			encoding_profiles
		WHERE
			id = " . $encodingprofileid . " AND
			disabled = 0";

	try {
		$profile = $db->getArray($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "[ERROR] Cannot query encoding profile. SQL query failed." . trim($query), $sendmail = true);
		return false;
	}

	// Check if any record returned
	if ( count($profile) < 1 ) {
		return false;
	}

	return $profile[0];
}

function getRecordingCreator($recordingid) {
global $db, $jconf, $debug, $myjobid;

	$db = db_maintain();

	$query = "
		SELECT
			a.userid,
			b.nickname,
			b.email,
			b.language,
			b.organizationid,
			c.domain,
			c.supportemail
		FROM
			recordings as a,
			users as b,
			organizations as c
		WHERE
			a.userid = b.id AND
			a.id = " . $recordingid . " AND
			a.organizationid = c.id";

	try {
		$user = $db->getArray($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot query recording creator. SQL query failed." . trim($query), $sendmail = true);
		return false;
	}

	// Check if any record returned
	if ( count($user) < 1 ) {
		return false;
	}

	return $user[0];
}

function getRecordingVersions($recordingid, $status, $type = "recording") {
global $db, $jconf, $debug, $myjobid;

	if ( ( $type != "recording" ) and ( $type != "content" ) and ( $type != "all" ) ) return false;

	$iscontent_filter = " AND iscontent = 0";
	if ( $type == "content" ) $iscontent_filter = " AND iscontent = 1";
	if ( $type == "all" ) $iscontent_filter = "";

	$db = db_maintain();

	$query = "
		SELECT
			id,
			recordingid,
			qualitytag,
			iscontent,
			status,
			resolution,
			filename,
			bandwidth,
			isdesktopcompatible,
			ismobilecompatible
		FROM
			recordings_versions
		WHERE
			recordingid = " . $recordingid . $iscontent_filter . "
		ORDER BY
			id";

	try {
		$recordings_versions = $db->Execute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed.\n" . trim($query), $sendmail = true);
		return false;
	}

	// Check if any record returned
	if ( count($recordings_versions) < 1 ) {
		return false;
	}

	return $recordings_versions;
}

?>