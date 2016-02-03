<?php

///////////////////////////////////////////////////////////////////////////////////////////////////
class runExt {
///////////////////////////////////////////////////////////////////////////////////////////////////
//
// RunExternal v5 ( runExtV ):
// Ugly, mutated version of the previous runExt4() function.
//
// Additions:
//   - configurable timeout with a default value of 10s
//   - callbacks can be defined
//   - support for retrieving regular bash command's return values
//
///////////////////////////////////////////////////////////////////////////////////////////////////
	var $command     = null;
	var $envvars     = null; // not implemented yet!!
	var $timeoutsec  = null;
	var $close_stdin = false;
	
	private $start        = 0;
	private $duration     = 0;
	private $code         = -1;
	private $output       = array();
	private $pid          = null;
	private $masterpid    = null;
	private $msg          = null;
	private $callback     = null;
	private $polling_usec = 50000;
	
///////////////////////////////////////////////////////////////////////////////////////////////////
	function __construct($command = null, $timeoutsec = null, $callback = null, $envvar = null) {
///////////////////////////////////////////////////////////////////////////////////////////////////
		$this->masterpid  = intval(posix_getpid());
		$this->command    = $command;
		$this->timeoutsec = 10.0;
		
		if ($timeoutsec !== null && is_numeric($timeoutsec)) $this->timeoutsec = floatval($timeoutsec);
		if ($callback !== null && is_callable($callback)) $this->callback = $callback;
		if (is_array($envvar)) $this->env = $envvar;
	}
	
///////////////////////////////////////////////////////////////////////////////////////////////////
	function run($command = null, $timeoutsec = null, $callback = null) {
///////////////////////////////////////////////////////////////////////////////////////////////////
		$pipes   = array();
		$process = null;
		$write   = null;
		$excl    = null;
		$ready   = null;
		$EOF     = false;
		$timeout = false;
		$lastactive = 0;
		
		$this->clearVariables();
		
		if ($command !== null) $this->command = $command;
		if ($timeoutsec !== null && is_numeric($timeoutsec)) $this->timeoutsec = floatval($timeoutsec);
		if ($callback !== null && is_callable($callback)) $this->callback = $callback;
		
		if (empty($this->command)) {
			$this->msg = "[ERROR] no command to be executed!";
			return false;
		}
		
		$desc = array(
			0 => array('pipe', 'r'),
			1 => array('pipe', 'w'),
			2 => array('pipe', 'w'),
		);
		
		$this->start = microtime(true);
		$process = proc_open($this->command, $desc, $pipes);
		
		if ($process === false || !is_resource($process)) {
			$this->msg = "[ERROR] Failed to open process!";
			return false;
		}
		
		if ($this->close_stdin) {
			fclose($pipes[0]);
			unset($pipes[0]);
		}

		$proc_status = proc_get_status($process);
		$this->pid = $proc_status['pid'];
		
		foreach($pipes as $p) { stream_set_blocking($p, 0); }
		
		$lastactive = microtime(true);
		
		do {
			$read = $pipes;
			$tmp  = null;
			
			$ready = stream_select($read, $write, $excl, 0, $this->polling_usec);
			$proc_status = proc_get_status($process);

			if ($ready === false ) { // error
				$err = error_get_last();
				$this->msg = $err['message'];
				restore_error_handler();
				break;
			} elseif ($ready > 0) {
				foreach($read as $r) {
					$tmp .= stream_get_contents($r);
					if (feof($r)) $EOF = true;
				}
				
				if (!empty($tmp)) {
					$lastactive = microtime(true);
					$this->output[] = $tmp;
					if ($this->callback) call_user_func($this->callback, $tmp);
					continue;
				}
			} else {
				$timeout = ((microtime(true) - $lastactive) > $this->timeoutsec);
				usleep($this->polling_usec);
			}
		} while($proc_status['running'] && !$timeout && !$EOF);
		
		if ($timeout) {
			$this->msg = "[WARN] Timeout Exceeded, sending SIGKILL to process (pid=". $this->pid .")";
			$this->duration = (microtime(true) - $this->start);
			
			if (posix_kill($this->pid, SIGKILL) === false && $proc_status['running']) {
				throw new Exception("[ERROR] Failed to shut down process!");
			}
			
			return false;
			
		} else {
			
			if ($proc_status['running']) $proc_status = proc_get_status($process);
			$this->code = $proc_status['exitcode'];
			posix_kill($this->pid, SIGKILL);
			foreach ($pipes as $p) { fclose($p); }
			proc_close($process);
			
			if (gettype($process) == "resource") { /*ERROR - process wasn't closed properly*/ }
			$this->duration = (microtime(true) - $this->start);
			
			if ($proc_status['signaled']) {
				$this->msg = "[WARN] Process has been terminated by an uncaught signal(". $proc_status['termsig'] .").";
				return false;
			} elseif ($proc_status['stopped']) {
				$this->msg = "[WARN] Process stopped after recieving signal(". $proc_status['stopsig'] .").";
			} else {
				if ($this->code === 0) {
					$this->msg = "[OK]";
				} else {
					$this->msg = "[WARN] Process failed (exitcode = ". $this->code .").";
					return false;
				}
			}
		}
		return true;
	}
	
///////////////////////////////////////////////////////////////////////////////////////////////////
	private function clearVariables() {
///////////////////////////////////////////////////////////////////////////////////////////////////
		$this->start        = 0;
		$this->duration     = 0;
		$this->code         = -1;
		$this->output       = array();
		$this->pid          = null;
		$this->msg          = null;
		$this->callback     = null;
		$this->polling_usec = 50000;
	}
	
///////////////////////////////////////////////////////////////////////////////////////////////////

	function getCode()      { return (int) $this->code; }

	function getDuration()  { return (double) $this->duration; }

	function getMessage()   { return $this->msg; }

	function getOutput()    { return implode(PHP_EOL, $this->output); }

	function getOutputArr() { return $this->output; }

	function getPid()       { return $this->pid; }

} // end of RunExtV class

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
				if ((round($rec[$idx .'mastervideofps']) % 50) == 0) $encpars['videofps'] = 50;
				if ((round($rec[$idx .'mastervideofps']) % 60) == 0) $encpars['videofps'] = 60;
			}
		}
		if ($encpars['videofps'] > $profile['videomaxfps']);

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
		
		// Keyframe distance
		$encpars['goplength'] = null;
		if (array_key_exists('fixedgopenabled', $profile) && $profile['fixedgopenabled'] && $profile['fixedgoplengthms']) {
			$encpars['goplength'] = intval($encpars['videofps'] * ($profile['fixedgoplengthms'] / 1000));
			if (($profile['fixedgoplengthms'] / 1000) > $rec[$idx .'masterlength']) {
				$encpars['goplength'] = intval($rec[$idx .'masterlength'] * $encpars['videofps']);
			}
		}
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
	$ffmpeg_gop         = null; // fixed keyframe distance options
	$ffmpeg_pass_prefix = null; // used with multipass encoding (passlogfiles will be written here, with the given prefix)
	$ffmpeg_globals     = $app->config['encoding_nice'] ." ". $app->config['ffmpeg_alt'] ." -v ". $app->config['ffmpeg_loglevel'] ." -y";
	$ffmpeg_output      = " -threads ". $app->config['ffmpeg_threads'] ." -f ". $profile['filecontainerformat'] ." ". $rec['output_file'];

	$audio_filter = null;
	$video_filter = null;
	
	$filter_resize = (array_key_exists('ffmpeg_resize_filter', $app->config) && $app->config['ffmpeg_resize_filter'] === true) ? (true) : (false); 
	
	$gop_length = null;
	$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] .'.log', var_export($profile, true), false); // DEBUG
	if (array_key_exists('goplength', $main)) {
		$msg = "goplength main = ". (is_null($main['goplength']) ? "NULL" : print_r($main['goplength'], true));
		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] .'.log', $msg, false);
		if (!is_null($overlay) && array_key_exists('goplength', $overlay)) {
			$gop_length = min($main['goplength'], $overlay['goplength']);
			$msg  = "goplength overlay = ". (is_null($overlay['goplength']) ? "NULL" : print_r($overlay['goplength'], true));
			$msg .= " - min = ". (is_null($gop_length) ? "NULL" : $gop_length);
			$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] .'.log', $msg, false);
		} else {
			$gop_length = $main['goplength'];
		}
		unset($msg);
	}
	if (!is_null($gop_length)) $ffmpeg_gop = " -g ". $gop_length ." -keyint_min ". $gop_length ." -sc_threshold 0";

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
			$ffmpeg_profile = " -profile:v " . $profile['ffmpegh264profile'] ." -pix_fmt yuv420p";
			$ffmpeg_preset  = " -preset:v ". $profile['ffmpegh264preset'];
			$ffmpeg_resize  = null;
			$ffmpeg_deint   = null;
			$ffmpeg_aspect  = null;
			
			if ($filter_resize) {
				$video_filter .= "[0:v] ". ($main['deinterlace'] ? "yadif, " : "");
				$video_filter .= "scale=w=". $main['resx'] .":h=". $main['resy'] .":force_original_aspect_ratio=decrease:sws_flags=bicubic";
			} else {
					$ffmpeg_resize = " -s ". $main['resx'] ."x". $main['resy'];
					if ( !empty($main['DAR']) ) $ffmpeg_aspect = " -aspect " . $main['DAR'];
					$ffmpeg_deint  = ($main['deinterlace'] === true) ? " -deinterlace " : null;
			}
			
			$ffmpeg_fps     = " -r ". $main['videofps'];
			$ffmpeg_bitrate = " -b:v ". (10 * ceil($main['videobitrate'] / 10000)) . "k";
			// ffmpeg video encoding parameters
			$ffmpeg_video   = " -c:v libx264" . $ffmpeg_profile . $ffmpeg_resize . $ffmpeg_aspect . $ffmpeg_deint . $ffmpeg_fps . $ffmpeg_gop . $ffmpeg_bitrate;
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
		
		$ffmpeg_video = " -c:v libx264". $ffmpeg_profile . $ffmpeg_preset . $ffmpeg_fps . $ffmpeg_gop . $ffmpeg_bitrate;
		
		// ASSEMBLE PICTURE-IN-PICTURE FILTER FOR FFMPEG
		$target_length = max($rec['masterlength'], $rec['contentmasterlength']); // Bele kellene szamitani a offset-et!
		$pip = array();
		$pip = calculate_mobile_pip($rec['mastervideores'], $rec['contentmastervideores'], $profile);
		$err['encodingparams'] = array_merge($err['encodingparams'], $pip);
		
		$ffmpeg_input .= " -f lavfi -i color=c=0x000000:size=". $main['resx'] ."x". $main['resy'] .":duration=". $target_length;
		$ffmpeg_input .= " -i ". $rec['contentmaster_filename'] ." -i ". $rec['master_filename'];
		
		$video_filter .= "[1:v]". ($main['deinterlace'] ? " yadif," : null) ." scale=w=". $main['resx'] .":h=". $main['resy'] .":sws_flags=bicubic [main];";
		$video_filter .= " [2:v]". ($overlay['deinterlace'] ? " yadif," : null) ." scale=w=". $pip['pip_res_x'] .":h=". $pip['pip_res_y'] .":sws_flags=bicubic [pip];";
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
		$ffmpeg_pass_prefix = $rec['master_path'] . $rec['id'] ."_". $profile['type'] ."_passlog_". getHashFromProfileParams($profile, 6);
		$ffmpeg_passlogfile = $ffmpeg_pass_prefix ."-0.log"; // <prefix>-<#pass>-<N>.log (N=output-stream specifier)

		// first-pass
		if (!file_exists($ffmpeg_passlogfile .".mbtree")) {
			$cmd  = $ffmpeg_globals . $ffmpeg_input . $ffmpeg_payload ." -pass 1 -passlogfile ". $ffmpeg_pass_prefix . $ffmpeg_video . $ffmpeg_audio;
			$cmd .= " -threads ". $app->config['ffmpeg_threads'] ." -f ". $profile['filecontainerformat'] ." /dev/null";
			$command[1] = $cmd;
		}
		
		// second-pass
		$cmd  = $ffmpeg_globals . $ffmpeg_input . $ffmpeg_payload ." -pass 2 -passlogfile ". $ffmpeg_pass_prefix . $ffmpeg_video .  $ffmpeg_audio . $ffmpeg_output;
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
		$conv = new runExt($c, 14400);
		$conv->run();
		
		$err['duration'      ]  = $conv->getDuration();
		$err['command'       ]  = $c;
		$err['result'        ]  = $conv->getCode();
		$err['command_output'] .= $conv->getOutput();
		if ($app->config['ffmpeg_loglevel'] == 0 || $app->config['ffmpeg_loglevel'] == 'quiet')
			$err['command_output']= "( FFmpeg output was suppressed - loglevel: ". $app->config['ffmpeg_loglevel'] .". )";
		$mins_taken = round( $err['duration'] / 60, 2);
		
		// Check results
		if ($conv->getCode() !== 0) {
			// FFmpeg returned with a non-zero error code
			$err['message'] = "[ERROR] FFmpeg conversion FAILED!";
			$msg = $err['message'] ."\nExit code: ". $conv->getCode() .".\nConsole output:\n". $conv->getOutput();
			$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] .".log", $msg, false);
			unset($conv, $msg);
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
		
		$msg = " [OK] FFmpeg encoding was successful! Exit code: ". $conv->getCode();
		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", $msg, $sendmail = false);
		unset($conv, $msg);
	}
	////////////////////////////////////////////////////////////////////// END OF ENCODING PROCESS //
	// METADATA CHECK ///////////////////////////////////////////////////////////////////////////////
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

			unset($msg);
			return $err;
		}
		
		if ($profile['mediatype'] != "audio" && $filter_resize === true) {
			$tmp = explode("x", $r->metadata['mastervideores'], 2);

			if (!empty($tmp) && (abs($tmp[0] - $main['resx']) > 0 || abs($tmp[1] - $main['resy']) > 0)) {
				$msg = "[INFO] Updating framesize on recording_version #". $rec['recordingversionid'] ." | resolution => ". $r->metadata['mastervideores'];
				$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] .'.log', $msg, $sendmail = false);
				
				$values = array('resolution' => ($r->metadata['mastervideores']));
				$recVersion = $app->bootstrap->getModel('recordings_versions');
				$recVersion->select($rec['recordingversionid']);
				$recVersion->updateRow($values);
				
				unset($msg, $values, $recVersion);
			}
			unset($tmp);
		}
		
		$msg = "[INFO] Metadata check finished on ". $rec['output_file'];
		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] .'.log', $msg, $sendmail = false);

		unset($msg, $r);
	} catch (Exception $e) {
		$msg = "[WARN] Metadata check failed after conversion.\nError message: ". $e->getMessage() ."\n";

		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] .'.log', $msg, $sendmail = false);
		unset($msg);
	} ////////////////////////////////////////////////////////////////////// END OF METADATA CHECK //

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
function getHashFromProfileParams($profile, $length = 32) {
///////////////////////////////////////////////////////////////////////////////////////////////////
// Generate md5 hash from encoding profile parameters - useful to detect differencies between two
// encoding parameter sets.
///////////////////////////////////////////////////////////////////////////////////////////////////
	$keys = array(
		'mediatype',
		'videocodec',
		'ffmpegh264profile',
		'ffmpegh264preset',
		'pipenabled',
		'pipcodecprofile',
		'pipposx',
		'pipposy',
		'pipalign',
		'pipsize',
		'goplength',
	);
	$dataset = null;
	$tmp = array();
	
	foreach ($keys as $k) {
		if (array_key_exists($k, $profile)) $tmp[] = $k ."=>". ($profile[$k]);
	}
	$dataset = implode(",", $tmp);
	
	return substr(md5($dataset), 0, ($length > 32 ? 32 : intval($length)));
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
global $app, $jconf, $debug, $myjobid;

	//$db = db_maintain();

	$query = "
		SELECT
			id,
			parentid,
			name,
			shortname,
			type,
			mediatype,
            generatethumbnails,
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
			fixedgopenabled,
			fixedgoplengthms,
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
		//$rs_array = $db->getArray($query);
        $model = $app->bootstrap->getModel('encoding_profiles');
        $rs = $model->safeExecute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $jconf['jobid_media_convert'] . ".log", "[ERROR] Cannot query encoding profile. SQL query failed." . trim($query), $sendmail = true);
		return false;
	}

    // Convert AdoDB resource to array
    $rs_array = adoDBResourceSetToArray($rs);

	return $rs_array[0];
}

///////////////////////////////////////////////////////////////////////////////////////////////////
function getRecordingCreator($recordingid) {
global $app, $jconf, $debug, $myjobid;

	//$db = db_maintain();

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
			recordings AS a,
			users AS b,
			organizations AS c
		WHERE
			a.userid = b.id AND
			a.id = " . $recordingid . " AND
			a.organizationid = c.id";

	try {
		//$user = $db->getArray($query);
        $model = $app->bootstrap->getModel('recordings');
        $rs = $model->safeExecute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot query recording creator. SQL query failed." . trim($query), $sendmail = true);
		return false;
	}

    // Convert AdoDB resource to array
    $rs_array = adoDBResourceSetToArray($rs);

	return $rs_array[0];
}

///////////////////////////////////////////////////////////////////////////////////////////////////
function getRecordingVersions($recordingid, $status, $type = "recording") {
global $app, $jconf, $debug, $myjobid;

	if ( ( $type != "recording" ) and ( $type != "content" ) and ( $type != "all" ) ) return false;

	$iscontent_filter = " AND iscontent = 0";
	if ( $type == "content" ) $iscontent_filter = " AND iscontent = 1";
	if ( $type == "all" ) $iscontent_filter = "";

	//$db = db_maintain();

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
		//$rs = $db->Execute($query);
        $model = $app->bootstrap->getModel('recordings_versions');
        $rs = $model->safeExecute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed.\n" . trim($query), $sendmail = true);
		return false;
	}

    if ( $rs->RecordCount() < 1 ) return false;

	return $rs;
}

?>