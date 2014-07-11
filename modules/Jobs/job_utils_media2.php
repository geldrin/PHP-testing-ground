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
		$command  = $profile['nice'] . " ffmpeg -y -i " . $media_info['master_filename'] . " -v " . $jconf['ffmpeg_loglevel'] . " " . $jconf['ffmpeg_flags'] . " ";
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
		$encodingparams['audiobitrate'] = $audiobitrate * 1000;

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
		$tmp = calculate_video_scaler($encodingparams['resxdar'], $encodingparams['resydar'], $profile['videobboxsizex'], $profile['videobboxsizey']);
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


///////////////////////////////////////////////////////////////////////////////////////////////////
	function ffmpegPrep($rec, $profile) {
///////////////////////////////////////////////////////////////////////////////////////////////////
// Description
// goes
// here.
// 
// iscontent(bool), master_basename, master_remote_filename, master_filename,master_ssh_filename
// encoding_profiles.type:       recording/pip/content
// encoding_profiles.mediatype:  video/audio
//
// > a 'videoonly' kulcsokat le kell cserelni jconf-os verziora!
// > erdemes volna a DAR/SAR/PAR-os benazas helyett a scale filtert hasznalni -force_aspect_ratio
//  kapcsoloval? (https://www.ffmpeg.org/ffmpeg-filters.html#Options-1)
//  out_w, out_h -t kell hasznalni a PIP eseten => Le kell tesztelni!!!                       -> OK
//
// > converzio utani duration check-et bele kell tenni!                                       -> OK
//
// > DEBUG:
//  ki kell deriteni, miert lep ki az ffmpeg process hq felvetelek konvertalasakor!
//  => ffmpeg output loggolasa: '-report' kapcsolo
//    sysvar: FFREPORT="file=<filename>:level=<#int>"
//  => mi legyen a reportfile-okkal?
//
// PIP eseten:
// > az alacsonyabb vagy a magasabb fps legyen az alap? -> magasabb!
///////////////////////////////////////////////////////////////////////////////////////////////////
global $jconf, $debug;
	// Encoding paramteres: an array for recording final parameters used for encoding
	$encpars = array();
	$encpars['name'] = $profile['name'];
	$encpars['hasaudio'] = true;
	$encpars['hasvideo'] = true;
	
	$idx = ($rec['iscontent'] ? 'content' : '');
	
	// Audio parameters
	if (($rec[$idx . 'mastermediatype'] == "videoonly" ) || ( empty($profile['audiocodec']))) {
		// No audio channels to be encoded
		$encpars['hasaudio'] = false;
	} else {
		// Bitrate settings for audio
		if ( $rec['masteraudiochannels'] < $profile['audiomaxchannels'] ) $audiochannels = $rec[$idx . 'masteraudiochannels'];
		$audiobitrate = $profile['audiomaxchannels'] * $profile['audiobitrateperchannel'];

		$encpars['audiochannels'] = $profile['audiomaxchannels'];
		// Samplerate correction according to encoding profile
		$encpars['audiosamplerate'] = doSampleRateCorrectionForProfile($rec[$idx . 'masteraudiofreq'], $profile);
		$encpars['audiobitrate'] = $audiobitrate * 1000;
	}
	
	// Video parameters
	if ($rec[$idx .'mastermediatype'] == 'audio' || empty($profile['videocodec'])) { // audio VAGY audioonly
		$encpars['hasvideo'] = false;
	} else {
		// pip eseten ossze kell hasonlitani az fps-t es a nagyobbat kell valasztani (vidx valtoztatas)
		$encpars['videofps'] = $rec[$idx .'mastervideofps'];
	
		if ( empty($rec[$idx . 'mastervideofps']) ) {
			$encpars['videofps'] = $jconf['video_default_fps'];
		} else {
			// Max fps check
			if ( $rec[$idx . 'mastervideofps'] > $profile['videomaxfps'] ) {
				switch ($rec[$idx . 'mastervideofps']) {
					case 60:
						$encpars['videofps'] = 30;
						break;
					case 50:
						$encpars['videofps'] = 25;
						break;
				}
			}
		}

		// Max resolution check (fraud check)
		$videores = explode('x', strtolower($rec[$idx .'mastervideores']), 2);
		$maxres   = explode('x', strtolower($jconf['video_max_res']), 2);
		if (($videores[0] > $maxres[0]) || ($videores[1] > $maxres[1])) {
			$msg = "[ERROR] Invalid video resolution: ". $rec[$idx .'mastervideores'];
			$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] .'.log', $msg, $sendmail = false);
			return false;
		}

		//// Scaling 1: Display Aspect Ratio (DAR)
		// Display Aspect Ratio (DAR): check and update if not square pixel
		$encpars['resxdar'] = $videores[0];
		$encpars['resydar'] = $videores[1];
		if ( !empty($rec[$idx . 'mastervideodar'] ) ) {
			// Display Aspect Ratio: M:N
			$tmp = explode(':', $rec[$idx . 'mastervideodar'], 2);
			if ( count($tmp) == 1 ) $tmp[1] = 1;
			if ( !empty($tmp[0]) and !empty($tmp[1]) ) {
				$DAR_M = $tmp[0];
				$DAR_N = $tmp[1];
				// Pixel Aspect Ratio: square pixel?
				$PAR = ( $videores[1] * $DAR_M ) / ( $videores[0] * $DAR_N );
				if ( $PAR != 1 ) {
					// No square pixel, add ARs to logs
					// SAR: Source Aspect Ratio = Width/Height
					$encpars['SAR'] = $videores[0] / $videores[1];
					// PAR
					$encpars['PAR'] = $PAR;
					// DAR
					$encpars['DAR'] = $DAR_M / $DAR_N;
					$encpars['DAR_MN'] = $rec[$idx . 'mastervideodar'];
					// Y: keep fixed, X: recalculate
					$encpars['resxdar'] = round($encpars['resydar'] * $encpars['DAR']);
				}
			}
		}

		//// Scaling 2: profile bounding box
		$tmp = calculate_video_scaler($encpars['resxdar'], $encpars['resydar'], $profile['videobboxsizex'], $profile['videobboxsizey']);
		$encpars['scaler'] = $tmp['scaler'];
		$encpars['resx'] = $tmp['x'];
		$encpars['resy'] = $tmp['y'];
		// ffmpeg scaling parameter

		//$ffmpeg_filter_resize = " scale=w=". $encpars['resx'] .":h=". $encpars['resy'] .":sws_flags=bilinear "; // kell ez a PIP-hez??

		//// Video bitrate calculation
		// Source Bit Per Pixel
		$encpars['videobpp_source'] = $rec[$idx . 'mastervideobitrate'] / ( $videores[0] * $videores[1] * $rec[$idx . 'mastervideofps'] );
		// BPP check and update: use input BPP if lower than profile BPP
		$encpars['videobpp'] = $profile['videobpp'];
		if ( $encpars['videobpp_source'] < $profile['videobpp'] ) $encpars['videobpp'] = $encpars['videobpp_source'];
		$encpars['videobitrate'] = $encpars['videobpp'] * $encpars['resx'] * $encpars['resy'] * $encpars['videofps'];
		if ( $encpars['videobitrate'] > $jconf['video_max_bw'] ) $encpars['videobitrate'] = $jconf['video_max_bw'];

		// Deinterlace
		$encpars['deinterlace'] = ($rec[$idx .'mastervideoisinterlaced'] > 0) ? true : false;
	}

	if (!($encpars['hasvideo'] || $encpars['hasaudio'])) {
		$msg  = "[ERROR] Conflict encountered while parsing database values! (hasaudio/hasvideo false)\n";
		$msg .= $idx ."mastermediatype = '". $rec[$idx . 'mastermediatype'] ."'\nprofile.name=' ". $profile['name'] ." ';";
		$msg .= " profile.videocodec='". ($profile['videocodec'] ? $profile['videocodec'] : "null" ) ."';";
		$msg .= " profile.audiocodec='". ($profile['audiocodec'] ? $profile['audiocodec'] : "null" ) ."'\n";
		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] .'.log', $msg, $sendmail = false);
		return false;
	}

	return $encpars;
}

///////////////////////////////////////////////////////////////////////////////////////////////////
  function advancedFFmpegConvert($rec, $profile, $main, $overlay = null) {
///////////////////////////////////////////////////////////////////////////////////////////////////
// Description
// goes
// here.
//
// $main - $overlay:
// Az ffmpeg preparalo fuggveny altal visszaadott encoding parameter tombok, az overlay csak pip
// eseten hasznalatos.
//
// Uj jconf index-ek:
//  - max_duration_error: a mester- es a szolgaltatasi peldany hosszanak megengedett elterese (s)
//
// Kerdesek:
// - report-kornyezeti_valtozok vagy stderr>logfile ?                           -> INKABB AZ UTOBBI
// - mi legyen a logfajllal?                    -> NINCS KULON LOGFAJL, AZ EGESZ MEGY A SIMA LOG-BA
// - error reportba beleirodjon a logfile? (tul nagy fajlok generalodhatnak)
// - pip-nel kell az overlayre interlace-t beallitani? (side-by-side uzzemmod eseten szukseg lehet
//   ra, de ahhoz be kell vezetni valamilyen pip uzemmod valasztasi mechanizmust.)          -> IGEN
// - Multipass encoding-nal mi legyen, ha mar megvannak a passlogfilelok?
//
// CHECKLIST:
//  - long test                - OK
// Audio:
//  - dupla audio              - OK
//  - main audio only          - OK
//  - ovrl audio only          - OK
//
// Video:
//  - egyforma hossz           - OK
//  - rovid main               - OK
//  - rovid ovrl               - OK
//  - main out-of-bounding-box - OK
//  - ovrl out-of-bounding-box - OK
//  - 2x out-of-bounding_box   - OK
//  - pozicionalas, meretezes  - OK
//  - deinterlace              - OK
//  - deinterlace filter - pip - OK
//
//  Multipass
//  - two-pass enc             - OK
//  - mobile two-pass          - OK
//  - invalid-pass             - OK
///////////////////////////////////////////////////////////////////////////////////////////////////
global $jconf, $debug, $app;
	$err = array();
	$err['code'          ] = false;
	$err['result'        ] = 0;
	$err['command'       ] = '';
	$err['command_output'] = '-';
	$err['message'       ] = '';
	$err['encodingparams'] = '';
	
	if (is_array($main)) $err['encodingparams'] = $main;
	
	// INIT COMMAND ASSEMBLY ////////////////////////////////////////////////////////////////////////
	$ffmpeg_audio       = null; // audio encoding parameters
	$ffmpeg_video       = null;	// video encoding parameters
	$ffmpeg_payload     = null; // contains input option on normal encoding OR overlay filter on pip encoding
	$ffmpeg_pass_prefix = null; // used with multipass encoding (passlogfiles will be written here, with the given prefix)
	$ffmpeg_globals     = $jconf['encoding_nice'] ." ". $jconf['ffmpeg_alt'] ." -v ". $jconf['ffmpeg_loglevel'] ." -y";
	$ffmpeg_output      = " -threads ". $jconf['ffmpeg_threads'] ." -f ". $profile['filecontainerformat'] ." ". $rec['output_file'];
	
	if ($profile['type'] == 'recording' || $profile['type'] == 'content' ) {
	// SINGLE MEDIA //
		$idx = '';
		if ($profile['type'] == 'content') $idx = 'content';
		
		$ffmpeg_payload = " -i ". $rec[$idx .'master_filename'];
		
		// Audio //
		if ($main['hasaudio'] === false) {
			$ffmpeg_audio = " -an";
		} else {
			// When using ffmpeg's built-in aac library, "-strict experimental" option is required.
			if ($profile['audiocodec'] == 'aac') $ffmpeg_audio .= " -strict experimental";
			
			$ffmpeg_audio .= " -async ". $jconf['ffmpeg_async_frames'] ." -c:a ". $profile['audiocodec'] ." -ac ". $main['audiochannels'] ." -b:a ". $main['audiobitrate'] ." -ar ". $main['audiosamplerate'];
		}

		// Video //
		if ($main['hasvideo'] === false ) {
			$ffmpeg_video = " -vn";
		} else {
			// H.264 profile
			// $ffmpeg_payload .= " -filter_complex '[0:v] scale=w=". $main['resx'] .":h=". $main['resy'] .":force_original_aspect_ratio=decrease:flags=sws_flags=bicubic"; // placeholder for filter based scaling/bounding box
			$ffmpeg_profile = " -profile:v " . $profile['ffmpegh264profile'] ." -pix_fmt yuv420p";
			$ffmpeg_preset  = " -preset:v ". $profile['ffmpegh264preset'];
			$ffmpeg_resize  = " -s ". $main['resx'] ."x". $main['resy'];
			$ffmpeg_aspect  = null;
			if ( !empty($main['DAR_MN']) ) $ffmpeg_aspect = " -aspect " . $main['DAR_MN'];
			$ffmpeg_deint   = ($main['deinterlace'] === true) ? " -deinterlace " : null;
			$ffmpeg_fps     = " -r ". $main['videofps'];
			$ffmpeg_bitrate = " -b:v ". (10 * ceil($main['videobitrate'] / 10000)) . "k";
			// ffmpeg video encoding parameters
			$ffmpeg_video   = " -c:v libx264 " . $ffmpeg_profile . $ffmpeg_resize . $ffmpeg_aspect . $ffmpeg_deint . $ffmpeg_fps . $ffmpeg_bitrate;
		}
		
	} elseif($profile['type'] === 'pip') {
	// PICTURE-IN-PICTURE //
		// Audio //

		// When using ffmpeg's built-in aac library, "-strict experimental" option is required.
		if ($profile['audiocodec'] == 'aac') $ffmpeg_audio .= " -strict experimental";
		
		if ($main['hasaudio'] === true && $overlay['hasaudio'] === true) {
			// ha ketto audiobemenet van, akkor vedd a jobb minosegu parametereket
			$values = array('audiochannels', 'audiobitrate', 'audiosamplerate');
			foreach ($values as $v) {
				$$v = ($main[$v] >= $overlay[$v]) ? $main[$v] : $overlay[$v];
				$err['encodingparams'][$v] = $$v;
			}
			unset($values);
			
			// $audio_filter = " [1:a][2:a] amix=inputs=2:duration=longest, apad"; // APAD FILTERREL NEM ALL LE A KONVERZIO. (CHECK NEEDED!)
			$audio_filter  = " [1:a][2:a] amix=inputs=2:duration=longest";
			$ffmpeg_audio .= " -async ". $jconf['ffmpeg_async_frames'] ." -c:a ". $profile['audiocodec'] ." -ac ". $audiochannels ." -b:a ". $audiobitrate ." -ar ". $audiosamplerate;
		} else {
			if ($main['hasaudio'] === true) {
				// ha csak egyetlen audio input van, akkor azt keveri be, nem kell 'amix' filter
				$audio_filter  = null;
				// vedd a main hangbeallitasait:
				$ffmpeg_audio .= " -async ". $jconf['ffmpeg_async_frames'] ." -c:a ". $profile['audiocodec'] ." -ac ". $main['audiochannels'] ." -b:a ". $main['audiobitrate'] ." -ar ". $main['audiosamplerate'];
			} elseif( $overlay['hasaudio'] === true) {
				// ha csak egyetlen audio input van, akkor azt keveri be, nem kell 'amix' filter
				$audio_filter  = null;
				// vedd az overlay hangbeallitasait:
				$ffmpeg_audio .= " -async ". $jconf['ffmpeg_async_frames'] ." -c:a ". $profile['audiocodec'] ." -ac ". $overlay['audiochannels'] ." -b:a ". $overlay['audiobitrate'] ." -ar ". $overlay['audiosamplerate'];
			} else {
				"No audiochannels\n";
				$audio_filter = null;
				$ffmpeg_audio = ' -an';
			}
		}
		
		// Video //
		$values = array('videofps', 'videobitrate');
		foreach ($values as $v) {
			$$v = ($main[$v] >= $overlay[$v] ? $main[$v] : $overlay[$v]);
			$err['encodingparams'][$v] = $$v;
		}
		
		$ffmpeg_profile = " -profile:v " . $profile['ffmpegh264profile'] ." -pix_fmt yuv420p";
		$ffmpeg_preset  = " -preset:v ". $profile['ffmpegh264preset'];
		$ffmpeg_fps     = " -r:v ". $videofps;
		$ffmpeg_bitrate = " -b:v ". (10 * ceil($videobitrate / 10000)) . "k";
		
		$ffmpeg_video = " -c:v libx264 ". $ffmpeg_profile . $ffmpeg_preset . $ffmpeg_fps . $ffmpeg_bitrate;
		
		// ASSEMBLE PICTURE-IN-PICTURE FILTER FOR FFMPEG
		$target_length = max($rec['masterlength'], $rec['contentmasterlength']); // Bele kellene szamitani a offset-et!
		$pip = array();
		$pip = calculate_mobile_pip($rec['mastervideores'], $rec['contentmastervideores'], $profile);
		$err['encodingparams'] = array_merge($err['encodingparams'], $pip);
		
		$ffmpeg_payload .= " -f lavfi -i color=c=0x000000:size=". $main['resx'] ."x". $main['resy'] .":duration=". $target_length;
		$ffmpeg_payload .= " -i ". $rec['contentmaster_filename'] ." -i ". $rec['master_filename'];
		$ffmpeg_payload .= " -filter_complex '[1:v]". ($main['deinterlace'] ? " yadif," : null) ." scale=w=". $main['resx'] .":h=". $main['resy'] .":sws_flags=bicubic [main];";
		$ffmpeg_payload .= " [2:v] ". ($overlay['deinterlace'] ? " yadif," : null) ." scale=w=". $pip['pip_res_x'] .":h=". $pip['pip_res_y'] .":sws_flags=bicubic [pip];";
		$ffmpeg_payload .= " [0:v][main] overlay=repeatlast=0 [bg];";
		$ffmpeg_payload .= " [bg][pip] overlay=x=". $pip['pip_x'] .":y=". $pip['pip_y'] .":repeatlast=0";
		$ffmpeg_payload .= ($audio_filter === null) ? ("'") : ("; ". $audio_filter ."'");
		
		unset($pip);
	}
	
	// ASSEMBLE COMMAND LIST
	$command = array(); // List of FFmpeg commands to be executed
	
	if ($profile['videopasses'] < 0 || $profile['videopasses'] > 2 ) {
		$msg = "[WARN] 'encoding_profile.videopasses' has invalid value: ". $profile['videopasses'] ."!\n -> Using single encoding.";
		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] .'.log', $msg, $sendmail = false);
		$profile['videopasses'] = 1;
		unset($msg);
	}
	
	if ($profile['videopasses'] == 1 || $profile['videopasses'] === null) {
	// Single encoding
		$cmd  = $ffmpeg_globals . $ffmpeg_payload . $ffmpeg_video . $ffmpeg_audio . $ffmpeg_output;
		$command[1] = $cmd;
		
	} elseif($profile['videopasses'] == 2) {
	// Two-pass encoding
		$ffmpeg_pass_prefix = $rec['master_path'] . $rec['id'] ."_". $profile['type'] ."_passlog";
		$ffmpeg_passlogfile = $ffmpeg_pass_prefix ."-0.log"; // <prefix>-<#pass>-<N>.log (N=output-stream specifier)

		// first-pass
		if (!file_exists($ffmpeg_passlogfile .".mbtree")) {
			$cmd  = $ffmpeg_globals . $ffmpeg_payload ." -pass 1 -passlogfile ". $ffmpeg_pass_prefix . $ffmpeg_video . $ffmpeg_audio;
			$cmd .= " -threads ". $jconf['ffmpeg_threads'] ." -f ". $profile['filecontainerformat'] ." /dev/null";
			$command[1] = $cmd;
		}
		
		// second-pass
		$cmd  = $ffmpeg_globals . $ffmpeg_payload ." -pass 2 -passlogfile ". $ffmpeg_pass_prefix . $ffmpeg_audio . $ffmpeg_video . $ffmpeg_output;
		$command[2] = $cmd;
	}
	unset($cmd);
	////////////////////////////////////////////////////////////////////// END OF COMMAND ASSEMBLY //
	// INITIATE ENCODING PROCESS ////////////////////////////////////////////////////////////////////
	$full_duration = 0;
	$start_time = time();

	$msg = "FFmpeg converter starting.\n";
	
	foreach ($command as $n => $c) {
		$msg = "";
		$search4file = false; // Not all commands will generate output file - check this variable before looking after missing files
		if (($profile['videopasses'] > 1) && ($n == count($command))) $search4file = true;

		// Log ffmpeg command
		if ($profile['videopasses'] !== null && $profile['videopasses'] > 1) {
			$msg .= "\nMultipass encoding - Converting ". $n .". pass of ". $profile['videopasses'] .".";
		}
		$msg .= "\nCommand line:\n". $c;
		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", $msg, $sendmail = false);

		// Check passlogfile's availability
		if ($search4file && (is_readable($ffmpeg_passlogfile) && is_readable($ffmpeg_passlogfile .".mbtree")) === false) {
			$msg = "Permission denied:";
			if (!file_exists($ffmpeg_passlogfile)) $msg = "File doesn't exists:";
			$msg = "[ERROR] Multipass encoding failed! ". $msg ." (". $ffmpeg_passlogfile .")";
			$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", $msg, $sendmail = false);

			$err['code'   ] = false;
			$err['message'] = $msg;	
			return $err;
		}
		
		// EXECUTE FFMPEG COMMAND
		$start 	= time();
		$output = runExt($c);
		
		$err['duration'      ]  = time() - $start;
		$err['command'       ]  = $c;
		$err['result'        ]  = $output['code'];
		$err['command_output'] .= $output['cmd_output'];
		if ($jconf['ffmpeg_loglevel'] == 0 || $jconf['ffmpeg_loglevel'] == 'quiet')
			$err['command_output']= "( FFmpeg output was suppressed - loglevel: ". $jconf['ffmpeg_loglevel'] .". )";
		$mins_taken = round( $err['duration'] / 60, 2);
		
		// Check results
		if ($output['code'] !== 0) {
			// FFmpeg returned with a non-zero error code
			$err['message'] = "[ERROR] FFmpeg conversion FAILED!";
			$err['code'   ] = false;
			return $err;
		}
		
		if ($n == count($command)) {
			if (filesize($rec['output_file']) < 1000) {
				// FFmpeg terminated with error or filesize suspiciously small
				$err['message'] = "[ERROR] Output file is too small.";
				$err['code'   ] = false;
				return $err;
			}
		}	
		
		$msg = " [OK] FFmpeg encoding was successful! Exit code: ". $output['code'] ."\n";
		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", $msg, $sendmail = false);
		unset($msg);
	}
	////////////////////////////////////////////////////////////////////// END OF ENCODING PROCESS //
	// DURATION CHECK ///////////////////////////////////////////////////////////////////////////////
	try {
		$r = $app->bootstrap->getModel('recordings');
		$r->analyze($rec['output_file']);
		// select media lenght for duration check if 'profile.type' is different than PIP.
		if (!isset($target_length)) $target_length = $rec[$idx .'masterlength'];
		if (abs($r->metadata['masterlength'] - $target_length) > $jconf['max_duration_error']) {
			$msg  = "[ERROR] Duration check failed on ". $rec['output_file'] ."!\n";
			$msg .= "Output file's duration (". $r->metadata['masterlength'] ." sec) does not match with target duration (". $target_length ." sec)!\n";
			$msg .= "Command output:\n";
			$msg .= print_r($err['command_output'], 1);
			$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] .'.log', $msg, $sendmail = false);

			$err['code'   ] = false;
			$err['message'] = "[ERROR] FFmpeg conversion failed: conversion stopped unexpectedly!\n.";

			unset($r);
			unset($msg);
			return $err;
		}
		$msg = "[INFO] Duration check finished on ". $rec['output_file'] ."\n";
		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] .'.log', $msg, $sendmail = false);

		unset($msg);
	} catch (Exception $e) {
		$msg = "[WARN] Mediainfo check failed after conversion.\nError message: ". $e->getMessage() ."\n";

		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] .'.log', $msg, $sendmail = false);
		unset($msg);
	} ////////////////////////////////////////////////////////////////////// END OF DURATION CHECK //

	$full_duration  = round((time() - $start_time) / 60, 2);
	$msg = "[OK] FFmpeg ". (
		($profile['videopasses'] > 1) ? ($profile['videopasses'] ."-pass encoding") : ("conversion")
		) ." completed in ". $full_duration ." minutes!";
	$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] .'.log', $msg, $sendmail = false);

	$err['message'] = $msg;
	$err['code'   ] = true;
	unset($msg);

	return $err;
}

///////////////////////////////////////////////////////////////////////////////////////////////////
	function runExt($cmd) {
///////////////////////////////////////////////////////////////////////////////////////////////////
//
// Description...
//
// => A Job_utils_base run_external() fuggvenye ROSSZ, LE KELL CSERELNI ERRE A VERZIORA!!!!
//  TODO: ELLENORIZNI KELL FMPEG THUMBNAILER-REL!!
///////////////////////////////////////////////////////////////////////////////////////////////////
	$cmd .= ";echo $? >&3";	// Echo previous command's exit code to file descriptor #3.

	$return_array = array();
	$return_array['pid'] = 0;
	$return_array['code'] = 0;
	$return_array['cmd_output'] = "";

	$descriptorspec = array(
		0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
		1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
		2 => array("pipe", "w"),  // stderr is a file to write to
		3 => array("pipe", "w")   // pipe for child process (used to capture exit code)
	);

	$pipes = array();
	$process = proc_open($cmd, $descriptorspec, $pipes);

	// Terminate if process cannot be initated.
	if ( !is_resource($process) ) return $return_array;

	// close child's input imidiately
	fclose($pipes[0]);
	
	for ($i = 1; $i <= 3; $i++) {
		stream_set_blocking($pipes[$i], false);
	}
	
	$exitcode = null;
	$output = "";
	while( !feof($pipes[1]) || !feof($pipes[2])) {
		$read = array();
		if( !feof($pipes[1]) ) $read[1]= $pipes[1];
		if( !feof($pipes[2]) ) $read[2]= $pipes[2];
		if (!feof($pipes[3])) {
			$readcode = rtrim(fgets($pipes[3], 5), "\n");
			if (!empty($readcode) || !is_null($readcode)) $exitcode = intval($readcode);
		}
		// $exitcode = stream_get_line($pipes[3], 1024, "\n"); // Does not always works :'(
		
		if (!$read) break;

		$write = NULL;
		$ex = NULL;
		// Check pipelines in array 'read', and wait until somthing apperars on them, and put them back to 'read'
		$ready = stream_select($read, $write, $ex, 1);
		if ( $ready === FALSE ) {
			break; // should never happen - something died
		}
		// Copy data from the previously selected piplines
		foreach ($read as $k => $r) {
			$s = fgets($r, 1024);
			$output .= $s;
		}
	}
	// Close all handle objects
	fclose($pipes[1]);
	fclose($pipes[2]);
	fclose($pipes[3]);
	// Get process PID
	$tmp = proc_get_status($process);
	proc_close($process);

	$return_array = array();
	$return_array['pid'] = $tmp['pid'];
	$return_array['code'] = $exitcode;
	$return_array['cmd_output'] = $output;

	return $return_array;
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
function calculate_video_scaler($resx, $resy, $bboxx, $bboxy) {
global $jconf;

	$scaler = 1;
	// Check if video is larger than bounding box
	if ( ( $resx > $bboxx ) || ( $resy > $bboxy ) ) {
		$scaler_x = $bboxx / $resx;
		$scaler_y = $bboxy / $resy;
		// Select minimal scaler to fit bounding box
		$scaler = min($scaler_x, $scaler_y);
		$resx_new = (int) $jconf['video_res_modulo'] * round(($resx * $scaler) / $jconf['video_res_modulo'], 0, PHP_ROUND_HALF_DOWN);
		$resy_new = (int) $jconf['video_res_modulo'] * round(($resy * $scaler) / $jconf['video_res_modulo'], 0, PHP_ROUND_HALF_DOWN);
	} else {
		// Recalculate resolution with codec modulo if needed (fix for odd resolutions)
		$resx_new = $resx;
		$resy_new = $resy;
		if ( ( ( $resx % $jconf['video_res_modulo'] ) > 0 ) || ( ( $resy % $jconf['video_res_modulo'] ) > 0 ) ) {
			$resx_new = (int) $jconf['video_res_modulo'] * round($resx / $jconf['video_res_modulo'], 0, PHP_ROUND_HALF_DOWN);
			$resy_new = (int) $jconf['video_res_modulo'] * round($resy / $jconf['video_res_modulo'], 0, PHP_ROUND_HALF_DOWN);
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
//	- $profile: conversion profile 
// OUTPUTS:
//	- Boolean:
//	  o FALSE: encoding failed (error cause logged in DB and local files)
//	  o TRUE: encoding OK
//	- $pip: calculated resolutions
function calculate_mobile_pip($mastervideores, $contentmastervideores, $profile) {
	global $jconf;
	
	$pip = array();
	// Content resolution
	$tmp = explode("x", $contentmastervideores, 2);
	$c_resx = $tmp[0];
	$c_resy = $tmp[1];
	$c_resnew = calculate_video_scaler($c_resx, $c_resy, $profile['videobboxsizex'], $profile['videobboxsizey']);
	$pip['scaler'] = $c_resnew['scaler'];
	$pip['res_x'] = $c_resnew['x'];
	$pip['res_y'] = $c_resnew['y'];
	// Media resolution
	$tmp = explode("x", $mastervideores, 2);
	$resx = $tmp[0];
	$resy = $tmp[1];
	$scaler_pip = $resy / $resx;
	
	$pip['pip_res_x'] = $jconf['video_res_modulo'] * round(($pip['res_x'] * $profile['pipsize']) / $jconf['video_res_modulo'], 0, PHP_ROUND_HALF_DOWN);
	$pip['pip_res_y'] = $jconf['video_res_modulo'] * round(($pip['pip_res_x'] * $scaler_pip) / $jconf['video_res_modulo'], 0, PHP_ROUND_HALF_DOWN);
	// Calculate PiP position
	$pip_align_x = ceil($pip['res_x'] * $profile['pipalign']);
	$pip_align_y = ceil($pip['res_y'] * $profile['pipalign']);
	if ( $profile['pipposx'] == "left" ) $pip['pip_x'] = 0 + $pip_align_y;
	if ( $profile['pipposx'] == "right" ) $pip['pip_x'] = $pip['res_x'] - $pip['pip_res_x'] - $pip_align_x;
	if ( $profile['pipposy'] == "up" ) $pip['pip_y'] = 0 + $pip_align_y;
	if ( $profile['pipposy'] == "down" ) $pip['pip_y'] = $pip['res_y'] - $pip['pip_res_y'] - $pip_align_y;

	return $pip;
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
	$recording_info['master_filename'] = $recording['master_filename'];
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
	$tmp = calculate_video_scaler($recording_info['res_x_dar'], $recording_info['res_y_dar'], $profile['videobboxsizex'], $profile['videobboxsizey']);
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