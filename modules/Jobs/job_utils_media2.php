<?php

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
global $app, $debug, $jconf;
	$result = array(
		'result'  => true,
		'message' => "ok!",
		'params'  => null,
	);
	// Encoding paramteres: an array for recording final parameters used for encoding
	$encpars = array();
	$encpars['name'] = $profile['name'];
	$encpars['hasaudio'] = true;
	$encpars['hasvideo'] = true;
	
	$idx = ($rec['iscontent'] ? 'content' : '');
	
	$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] .'.log', "[INFO] Preparing encoding parameters. (rec#". $rec['id'] ." - '". $profile['name'] .")" , $sendmail = false);
	
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
			$encpars['videofps'] = $app->config['video_default_fps'];
		} else {
			// Max fps check
			if ( $rec[$idx . 'mastervideofps'] > $profile['videomaxfps'] ) {
				$encpars['videofps'] = $profile['videomaxfps'];
				// Falling back to closest rate 50 or 60
				if ( ( round($rec[$idx . 'mastervideofps']) % 50 ) == 0 ) $encpars['videofps'] = 50;
				if ( ( round($rec[$idx . 'mastervideofps']) % 60 ) == 0 ) $encpars['videofps'] = 60;
			}
		}

		// Max resolution check (fraud check)
		$videores = explode('x', strtolower($rec[$idx .'mastervideores']), 2);
		$maxres   = explode('x', strtolower($app->config['video_max_res']), 2);
		if (($videores[0] > $maxres[0]) || ($videores[1] > $maxres[1])) {
			$msg = "[ERROR] Invalid video resolution: ". $rec[$idx .'mastervideores'] . " (max: " . $app->config['video_max_res'] . ")";
			$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] .'.log', $msg, $sendmail = false);
			$result['message'] = $msg;
			$result['result' ] = false;
			return $result;
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
		// if ( $encpars['videobpp_source'] < $profile['videobpp'] ) $encpars['videobpp'] = $encpars['videobpp_source'];
		// Over 30 fps, we does not calculate same bpp for all the frames. Compensate 50% for extra frames.
		$fps_bps_normalized = $encpars['videofps'];
		if ( $encpars['videofps'] > 25 ) $fps_bps_normalized = 25 + round( ( $encpars['videofps'] - 25 ) / 3 );
		$encpars['videofps_bps_normalized'] = $fps_bps_normalized;
		$encpars['videobitrate'] = $encpars['videobpp'] * $encpars['resx'] * $encpars['resy'] * $fps_bps_normalized;
		// Max bitrate check and clip
		if ( $encpars['videobitrate'] > $app->config['video_max_bw'] ) $encpars['videobitrate'] = $app->config['video_max_bw'];

		// Deinterlace
		$encpars['deinterlace'] = ($rec[$idx .'mastervideoisinterlaced'] > 0) ? true : false;
	}

	if (!($encpars['hasvideo'] || $encpars['hasaudio'])) {
		$msg  = "[ERROR] Conflict encountered while parsing database values! (hasaudio/hasvideo false)\n";
		$msg .= $idx ."mastermediatype = '". $rec[$idx . 'mastermediatype'] ."'\nprofile.name=' ". $profile['name'] ." ';";
		$msg .= " profile.videocodec='". ($profile['videocodec'] ? $profile['videocodec'] : "null" ) ."';";
		$msg .= " profile.audiocodec='". ($profile['audiocodec'] ? $profile['audiocodec'] : "null" ) ."'\n";
		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] .'.log', $msg, $sendmail = false);
		$result['message'] = $msg;
		$result['result' ] = false;
		return $result;
	}

	$result['params'] = $encpars;
	unset($encpars);
	return $result;
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
global $app, $debug, $jconf;
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
	$ffmpeg_input       = null; // input filename and options
	$ffmpeg_payload     = null; // contains input option on normal encoding OR overlay filter on pip encoding
	$ffmpeg_pass_prefix = null; // used with multipass encoding (passlogfiles will be written here, with the given prefix)
	$ffmpeg_globals     = $app->config['encoding_nice'] ." ". $app->config['ffmpeg_alt'] ." -v ". $app->config['ffmpeg_loglevel'] ." -y";
	$ffmpeg_output      = " -threads ". $app->config['ffmpeg_threads'] ." -f ". $profile['filecontainerformat'] ." ". $rec['output_file'];
	
	$audio_filter = null;
	$video_filter = null;
	
	if ($profile['type'] == 'recording' || $profile['type'] == 'content' || ($profile['type'] == 'pip' && $overlay === null)) {
	// SINGLE MEDIA //
		$idx = '';
		if ($profile['type'] == 'content') $idx = 'content';
		
		$ffmpeg_input = " -i ". $rec[$idx .'master_filename'];
		
		// Audio //
		if ($main['hasaudio'] === false) {
			$ffmpeg_audio = " -an";
		} else {
			// When using ffmpeg's built-in aac library, "-strict experimental" option is required.
			if ($profile['audiocodec'] == 'aac') $ffmpeg_audio .= " -strict experimental";

			$ffmpeg_audio .= " -c:a ". $profile['audiocodec'] ." -ac ". $main['audiochannels'] ." -b:a ". $main['audiobitrate'] ." -ar ". $main['audiosamplerate'];
		}

		// Video //
		if ($main['hasvideo'] === false ) {
			$ffmpeg_video = " -vn";
		} else {
			// H.264 profile
			// $video_filter .= "[0:v] scale=w=". $main['resx'] .":h=". $main['resy'] .":force_original_aspect_ratio=decrease:flags=sws_flags=bicubic"; // placeholder for filter based scaling/bounding box
			$ffmpeg_profile = " -profile:v " . $profile['ffmpegh264profile'] ." -pix_fmt yuv420p";
			$ffmpeg_preset  = " -preset:v ". $profile['ffmpegh264preset'];
			$ffmpeg_resize  = " -s ". $main['resx'] ."x". $main['resy'];
			$ffmpeg_aspect  = null;
			if ( !empty($main['DAR']) ) $ffmpeg_aspect = " -aspect " . $main['DAR'];
			$ffmpeg_deint   = ($main['deinterlace'] === true) ? " -deinterlace " : null;
			$ffmpeg_fps     = " -r ". $main['videofps'];
			$ffmpeg_bitrate = " -b:v ". (10 * ceil($main['videobitrate'] / 10000)) . "k";
			// ffmpeg video encoding parameters
			$ffmpeg_video   = " -c:v libx264" . $ffmpeg_profile . $ffmpeg_resize . $ffmpeg_aspect . $ffmpeg_deint . $ffmpeg_fps . $ffmpeg_bitrate;
		}
		
		// filters //
		$tmp = array();
		if ($audio_filter) $tmp[] = $audio_filter;
		if ($video_filter) $tmp[] = $video_filter;
		if (count($tmp) >= 1) $ffmpeg_payload = " -filter_complex '". implode(";", $tmp) ."'";
		unset($tmp);
		
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
			$ffmpeg_audio .= " -c:a ". $profile['audiocodec'] ." -ac ". $audiochannels ." -b:a ". $audiobitrate ." -ar ". $audiosamplerate;
		} else {
			if ($main['hasaudio'] === true) {
				// ha csak egyetlen audio input van, akkor azt keveri be, nem kell 'amix' filter
				$audio_filter  = null;
				// vedd a main hangbeallitasait:
				$ffmpeg_audio .= " -c:a ". $profile['audiocodec'] ." -ac ". $main['audiochannels'] ." -b:a ". $main['audiobitrate'] ." -ar ". $main['audiosamplerate'];
			} elseif( $overlay['hasaudio'] === true) {
				// ha csak egyetlen audio input van, akkor azt keveri be, nem kell 'amix' filter
				$audio_filter  = null;
				// vedd az overlay hangbeallitasait:
				$ffmpeg_audio .= " -c:a ". $profile['audiocodec'] ." -ac ". $overlay['audiochannels'] ." -b:a ". $overlay['audiobitrate'] ." -ar ". $overlay['audiosamplerate'];
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
		
		$ffmpeg_video = " -c:v libx264". $ffmpeg_profile . $ffmpeg_preset . $ffmpeg_fps . $ffmpeg_bitrate;
		
		// ASSEMBLE PICTURE-IN-PICTURE FILTER FOR FFMPEG
		$target_length = max($rec['masterlength'], $rec['contentmasterlength']); // Bele kellene szamitani a offset-et!
		$pip = array();
		$pip = calculate_mobile_pip($rec['mastervideores'], $rec['contentmastervideores'], $profile);
		$err['encodingparams'] = array_merge($err['encodingparams'], $pip);
		
		$ffmpeg_input .= " -f lavfi -i color=c=0x000000:size=". $main['resx'] ."x". $main['resy'] .":duration=". $target_length;
		$ffmpeg_input .= " -i ". $rec['contentmaster_filename'] ." -i ". $rec['master_filename'];
		
		$video_filter .= "[1:v]". ($main['deinterlace'] ? " yadif," : null) ." scale=w=". $main['resx'] .":h=". $main['resy'] .":sws_flags=bicubic [main];";
		$video_filter .= " [2:v] ". ($overlay['deinterlace'] ? " yadif," : null) ." scale=w=". $pip['pip_res_x'] .":h=". $pip['pip_res_y'] .":sws_flags=bicubic [pip];";
		$video_filter .= " [0:v][main] overlay=repeatlast=0 [bg];";
		$video_filter .= " [bg][pip] overlay=x=". $pip['pip_x'] .":y=". $pip['pip_y'] .":repeatlast=0";
		
		$tmp = array();
		if ($video_filter) $tmp[] = $video_filter;
		if ($audio_filter) $tmp[] = $audio_filter;
		if (count($tmp) >= 1) $ffmpeg_payload = " -filter_complex '". implode(";", $tmp) ."'";
		
		unset($pip, $tmp);
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
		$cmd  = $ffmpeg_globals . $ffmpeg_input . $ffmpeg_payload . $ffmpeg_video . $ffmpeg_audio . $ffmpeg_output;
		$command[1] = $cmd;
		
	} elseif($profile['videopasses'] == 2) {
	// Two-pass encoding
		$ffmpeg_pass_prefix = $rec['master_path'] . $rec['id'] ."_". $profile['type'] ."_passlog";
		$ffmpeg_passlogfile = $ffmpeg_pass_prefix ."-0.log"; // <prefix>-<#pass>-<N>.log (N=output-stream specifier)

		// first-pass
		if (!file_exists($ffmpeg_passlogfile .".mbtree")) {
			$cmd  = $ffmpeg_globals . $ffmpeg_input . $ffmpeg_payload ." -pass 1 -passlogfile ". $ffmpeg_pass_prefix . $ffmpeg_video . $ffmpeg_audio;
			$cmd .= " -threads ". $app->config['ffmpeg_threads'] ." -f ". $profile['filecontainerformat'] ." /dev/null";
			$command[1] = $cmd;
		}
		
		// second-pass
		$cmd  = $ffmpeg_globals . $ffmpeg_input . $ffmpeg_payload ." -pass 2 -passlogfile ". $ffmpeg_pass_prefix . $ffmpeg_audio . $ffmpeg_video . $ffmpeg_output;
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

			$err['message'] = $msg;	
			return $err;
		}
		
		// EXECUTE FFMPEG COMMAND
		$start 	= time();
		$output = runExt4($c);
		
		$err['duration'      ]  = time() - $start;
		$err['command'       ]  = $c;
		$err['result'        ]  = $output['code'];
		$err['command_output'] .= $output['cmd_output'];
		if ($app->config['ffmpeg_loglevel'] == 0 || $app->config['ffmpeg_loglevel'] == 'quiet')
			$err['command_output']= "( FFmpeg output was suppressed - loglevel: ". $app->config['ffmpeg_loglevel'] .". )";
		$mins_taken = round( $err['duration'] / 60, 2);
		
		// Check results
		if ($output['code'] !== 0) {
			// FFmpeg returned with a non-zero error code
			$err['message'] = "[ERROR] FFmpeg conversion FAILED!";
			$msg = $err['message'] ."\nExit code: ". $output['code'] .".\nConsole output:\n". $err['command_output'] ."\n";
			$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] .".log", $msg, false);
			unset($msg);
			return $err;
		}
		
		if ($n == count($command)) {
			if (!file_exists($rec['output_file'])) {
				$err['message'] = "[ERROR] No output file!";
				return $err;
			}	elseif (filesize($rec['output_file']) < 1000) {
				// FFmpeg terminated with error or filesize suspiciously small
				$err['message'] = "[ERROR] Output file is too small.";
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
		if (abs($r->metadata['masterlength'] - $target_length) > $app->config['max_duration_error']) {
			$msg  = "[ERROR] Duration check failed on ". $rec['output_file'] ."!\n";
			$msg .= "Output file's duration (". $r->metadata['masterlength'] ." sec) does not match with target duration (". $target_length ." sec)!\n";
			$msg .= "Command output:\n";
			$msg .= print_r($err['command_output'], 1);
			$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] .'.log', $msg, $sendmail = false);

			$err['message'] = "[ERROR] FFmpeg conversion failed: conversion stopped unexpectedly!\n.";

			unset($r, $msg);
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
function runExt4($cmd) {
///////////////////////////////////////////////////////////////////////////////////////////////////
//
// RunExt v4
//
// Favago modszer exec() + echo $? paranccsal.
// Az echo kiirja az elozoleg futtatott parancs exitcode-jat az utolso sorba, amit az exec()
// fuggveny ouput valtozo utolso eleme tartalmaz.
//
///////////////////////////////////////////////////////////////////////////////////////////////////
	$return_array = array(
		'code'       => -1,
		'cmd'        => null,
		'cmd_output' => null,
	);
	
	$cmd .= " 2>&1; echo $?";
	$return_array['cmd'] = $cmd;
	
	$output = array();
	$code = -1;
	
	exec($cmd, $output, $code);
	
	$return_array['code'] = intval(array_pop($output));
	$return_array['cmd_output'] = implode(PHP_EOL, $output);
	
	return $return_array;
}

///////////////////////////////////////////////////////////////////////////////////////////////////
function runExt($cmd) {
///////////////////////////////////////////////////////////////////////////////////////////////////
//
// Description...
//
// => A Job_utils_base run_external() fuggvenye ROSSZ, LE KELL CSERELNI ERRE A VERZIORA!!!!
//  TODO: ELLENORIZNI KELL FMPEG THUMBNAILER-REL!!                                           -> OK!
///////////////////////////////////////////////////////////////////////////////////////////////////
	$cmd .= "; echo $? 1>&3";	// Echo previous command's exit code to file descriptor #3.

	$return_array = array();
	$return_array['pid'] = 1;
	$return_array['code'] = -1;
	$return_array['cmd_output'] = "";

	$descriptorspec = array(
		0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
		1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
		2 => array("pipe", "w"),  // stderr is a file to write to
		3 => array("pipe", "w"),   // pipe for child process (used to capture exit code)
	);

	$pipes = array();
	$process = proc_open($cmd, $descriptorspec, $pipes);

	// Terminate if process cannot be initated.
	if ( !is_resource($process) ) return $return_array;

	// close child's input imidiately
	fclose($pipes[0]);
	
	for ($i = 1; $i < 2; $i++) {
		stream_set_blocking($pipes[$i], false);
	}
	
	$exitcode = $return_array['code'];
	$output = "";
	while( !feof($pipes[1]) || !feof($pipes[2]) || !feof($pipes[3])) {
		$read = array();
		if( !feof($pipes[1]) ) $read[1]= $pipes[1];
		if( !feof($pipes[2]) ) $read[2]= $pipes[2];
		if (!feof($pipes[3])) {
			$readcode = rtrim(fgets($pipes[3]), "\n");
			$read[3] = $pipes[3];
			if (strlen($readcode)) $exitcode = intval($readcode);
		}
		// $exitcode = stream_get_line($pipes[3], 1024, "\n"); // Does not always works :'(

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

///////////////////////////////////////////////////////////////////////////////////////////////////
function doSampleRateCorrectionForProfile($samplerate, $profile) {
///////////////////////////////////////////////////////////////////////////////////////////////////
// Sample rate correction: to match codec requirements
///////////////////////////////////////////////////////////////////////////////////////////////////

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

///////////////////////////////////////////////////////////////////////////////////////////////////
function calculate_video_scaler($resx, $resy, $bboxx, $bboxy) {
///////////////////////////////////////////////////////////////////////////////////////////////////
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
///////////////////////////////////////////////////////////////////////////////////////////////////

global $app;

	$scaler = 1;
	// Check if video is larger than bounding box
	if ( ( $resx > $bboxx ) || ( $resy > $bboxy ) ) {
		$scaler_x = $bboxx / $resx;
		$scaler_y = $bboxy / $resy;
		// Select minimal scaler to fit bounding box
		$scaler = min($scaler_x, $scaler_y);
		$resx_new = (int) $app->config['video_res_modulo'] * round(($resx * $scaler) / $app->config['video_res_modulo'], 0, PHP_ROUND_HALF_DOWN);
		$resy_new = (int) $app->config['video_res_modulo'] * round(($resy * $scaler) / $app->config['video_res_modulo'], 0, PHP_ROUND_HALF_DOWN);
	} else {
		// Recalculate resolution with codec modulo if needed (fix for odd resolutions)
		$resx_new = $resx;
		$resy_new = $resy;
		if ( ( ( $resx % $app->config['video_res_modulo'] ) > 0 ) || ( ( $resy % $app->config['video_res_modulo'] ) > 0 ) ) {
			$resx_new = (int) $app->config['video_res_modulo'] * round($resx / $app->config['video_res_modulo'], 0, PHP_ROUND_HALF_DOWN);
			$resy_new = (int) $app->config['video_res_modulo'] * round($resy / $app->config['video_res_modulo'], 0, PHP_ROUND_HALF_DOWN);
		}
	}
	$new_resolution = array (
		'scaler' => $scaler,
		'x'		 => $resx_new,
		'y'		 => $resy_new,
	);

	return $new_resolution;
}

///////////////////////////////////////////////////////////////////////////////////////////////////
function calculate_mobile_pip($mastervideores, $contentmastervideores, $profile) {
///////////////////////////////////////////////////////////////////////////////////////////////////
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
///////////////////////////////////////////////////////////////////////////////////////////////////
	global $app;
	
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
	
	$pip['pip_res_x'] = $app->config['video_res_modulo'] * round(($pip['res_x'] * $profile['pipsize']) / $app->config['video_res_modulo'], 0, PHP_ROUND_HALF_DOWN);
	$pip['pip_res_y'] = $app->config['video_res_modulo'] * round(($pip['pip_res_x'] * $scaler_pip) / $app->config['video_res_modulo'], 0, PHP_ROUND_HALF_DOWN);
	// Calculate PiP position
	$pip_align_x = ceil($pip['res_x'] * $profile['pipalign']);
	$pip_align_y = ceil($pip['res_y'] * $profile['pipalign']);
	if ( $profile['pipposx'] == "left" ) $pip['pip_x'] = 0 + $pip_align_y;
	if ( $profile['pipposx'] == "right" ) $pip['pip_x'] = $pip['res_x'] - $pip['pip_res_x'] - $pip_align_x;
	if ( $profile['pipposy'] == "up" ) $pip['pip_y'] = 0 + $pip_align_y;
	if ( $profile['pipposy'] == "down" ) $pip['pip_y'] = $pip['res_y'] - $pip['pip_res_y'] - $pip_align_y;

	return $pip;
}

///////////////////////////////////////////////////////////////////////////////////////////////////
function getEncodingProfile($encodingprofileid) {
///////////////////////////////////////////////////////////////////////////////////////////////////
//
///////////////////////////////////////////////////////////////////////////////////////////////////
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

///////////////////////////////////////////////////////////////////////////////////////////////////
function getRecordingCreator($recordingid) {
///////////////////////////////////////////////////////////////////////////////////////////////////
//
///////////////////////////////////////////////////////////////////////////////////////////////////
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

///////////////////////////////////////////////////////////////////////////////////////////////////
function getRecordingVersions($recordingid, $status, $type = "recording") {
///////////////////////////////////////////////////////////////////////////////////////////////////
//
///////////////////////////////////////////////////////////////////////////////////////////////////
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