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
		$ffmpeg_audio = "-async " . $jconf['ffmpeg_async_frames'] . " -acodec " . $profile['audio_codec'] . " -ac " . $profile['audio_ch'] . " -ab " . $media_info['audio_bitrate'] . "k -ar " . $media_info['audio_srate'] . " ";
	}

	if ( empty($profile['video_codec']) || empty($media_info['res_x']) || empty($media_info['res_y']) || empty($media_info['video_bitrate']) ) {
		$ffmpeg_video = " -vn ";
	} else {

		// Video bitrate
		$ffmpeg_bw = " -b " . 10 * ceil($media_info['video_bitrate'] / 10000) . "k";

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

		$ffmpeg_video = "-vcodec libx264 " . $profile['codec_profile'] . $resize . $deint . $fps . $ffmpeg_bw;

	}

	// 1 pass encoding
	if ( $profile['passes'] < 2 ) {
		// Execute ffmpeg command
		$command  = $jconf['nice'] . " ffmpeg -y -i " . $media_info['source_file'] . " -v " . $jconf['ffmpeg_loglevel'] . " " . $jconf['ffmpeg_flags'] . " ";
		$command .= $ffmpeg_audio;
		$command .= $ffmpeg_video;
		$command .= " -threads " . $jconf['ffmpeg_threads'] . " -f " . $profile['format'] . " " . $media_info['output_file'] . " 2>&1";

echo $command . "\n";

		$time_start = time();
		$output = runExternal($command);
		$err['duration'] = time() - $time_start;
		$mins_taken = round( $err['duration'] / 60, 2);
		$err['command'] = $command;
		$err['command_output'] = $output['cmd_output'];
		$err['result'] = $output['code'];

		// ffmpeg terminated with error
		if ( $err['result'] != 0 ) {
			$err['code'] = FALSE;
			$err['message'] = "[ERROR] ffmpeg conversion FAILED";
			return $err;
		}

		$err['code'] = TRUE;
		$err['message'] = "[OK] ffmpeg conversion OK (in " . $mins_taken . " mins)";
	}

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



?>