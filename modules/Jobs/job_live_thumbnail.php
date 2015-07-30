<?php
// Videosquare live thumbnail job

define('BASE_PATH',	realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');

// Utils
include_once('job_utils_base.php');
include_once('job_utils_log.php');
include_once('job_utils_status.php');
include_once('job_utils_media2.php');

set_time_limit(0);

// Init
$app = new Springboard\Application\Cli(BASE_PATH, PRODUCTION);

// Load jobs configuration file
$app->loadConfig('modules/Jobs/config_jobs.php');
$jconf   = $app->config['config_jobs'];
$myjobid = $jconf['jobid_live_thumb'];
$logdir  = $jconf['log_dir'];
$logfile = $myjobid .'.log';

// Log init
$debug = Springboard\Debug::getInstance();
$debug_mode = false;

// Exit if any STOP file is present
$stopfile = $app->config['datapath'] . 'jobs/' . $myjobid . '.stop';
$globalstopfile = $app->config['datapath'] . 'jobs/all.stop';
if ($debug_mode === false && (is_file($stopfile) || is_file($globalstopfile))) exit("Stopfile detected! Terminating.\n");

///////////////////////////////////////////////////////////////////////////////////////////////////
// Runover check. Is this process already running? If yes, report and exit
if ( !runOverControl($myjobid) ) exit("Runover detected! Terminating.");

// Run main loop
$myexitcode = Main();
die($myexitcode);

///////////////////////////////////////////////////////////////////////////////////////////////////
function Main() {
///////////////////////////////////////////////////////////////////////////////////////////////////
	global $app, $db, $debug, $debug_mode, $jconf, $logdir, $logfile, $myjobid;

	if ( !is_writable($jconf['livestreams_dir']) ) {
		$debug->log($logdir, $logfile, "[ERROR] Temp directory " . $jconf['livestreams_dir'] . " is not writeable.", $sendmail = false);
		return (1);
	}

	clearstatcache();
	$app->watchdog();

	// Establish database connection
	$db = db_maintain();

	$converter_sleep_length = $app->config['sleep_media'];

	// Watchdog
	$app->watchdog();

	// Query active channels
	$channels = getActiveChannels();
	if ( $channels === false ) {
		// Close DB connection if open
		if ( is_resource($db->_connectionID) ) $db->close();
		return 0;
	}

	for ( $i = 0; $i < count($channels); $i++ ) {
		$filename = $ffmpeg_output = $ffmpeg_loglevel = $ffmpeg_filter = $ffmpeg_globals = $ffmpeg_load = null;
		// Temp directory
		$temp_dir = $jconf['livestreams_dir'] . $channels[$i]['streamid'] . "/";

		// Destination directories
		$path_43      = $temp_dir . $app->config['videothumbnailresolutions']['4:3'] . "/";
		$path_wide    = $temp_dir . $app->config['videothumbnailresolutions']['wide'] . "/";
		$path_highres = $temp_dir . $app->config['videothumbnailresolutions']['player'] . "/";

		// RTMP URL - Use fallback server always
		$rtmp_server = $app->config['fallbackstreamingserver']['server'];

		$wowza_app = "vsqlive";
		if ( isset($app->config['production']) && $app->config['production'] === false ) $wowza_app = "dev" . $wowza_app;

		$filename        = $channels[$i]['streamid'] . "_" . date("YmdHis") . ".jpg";
		$ffmpeg_loglevel = ($debug_mode) ? (null) : (' -v '. $app->config['ffmpeg_loglevel']);
		$ffmpeg_globals  = $app->config['ffmpeg_alt'] . $ffmpeg_loglevel .' -y';
		$ffmpeg_load     = ' -i '. sprintf("rtmp://%s/" . $wowza_app . "/", $rtmp_server) . $channels[$i]['wowzastreamid'];

		$tmp = array();
		$tmp[] = 'fps=fps=1, split=3[0][1][2]';
		foreach (array_values($app->config['videothumbnailresolutions']) as $j => $res) {
			$dim = explode('x', $res, 2);
			$dst = $temp_dir . $res . DIRECTORY_SEPARATOR;
			$tmp[] =  "[$j] scale=w=". $dim[0] .":h=". $dim[1] .":force_original_aspect_ratio=increase, crop=". $dim[0] .':'. $dim[1] ." [". $j ."_out]";
			$ffmpeg_output .=  " -map [". $j ."_out] -frames:v 1 -an -f image2 ". $dst . $filename;
		}
		$ffmpeg_filter  = ' -filter_complex "'. implode(';', $tmp) .'"';
		$ffmpeg_command = $ffmpeg_globals . $ffmpeg_load . $ffmpeg_filter . $ffmpeg_output;

		try {
			$cmd = $code = null;
			$fatal = false;

			// Prepare working directories
			$directories = array($temp_dir, $path_wide, $path_43, $path_highres);

			foreach($directories as $d) {
				$err = create_remove_directory($d);
				if ( !$err['code'] ) {
					$cmd  = $err['command'];
					$code = $err['result'] ? 1 : 0;
					throw new Exception($err['message']);
				}
			}

			// Chmod local directory
			$errChmod = runExt4("chmod -f -R ". $jconf['directory_access'] ." ". $temp_dir);
			$cmd  = $errChmod['cmd'];
			$code = $errChmod['code'];

			if ( $errChmod['code'] != 0 ) throw new Exception("Chmod failed. (". $errChmod['cmd_output'] .")");

			// Run ffmpeg
			$err  = runExt4($ffmpeg_command);
			$cmd  = $err['cmd'];
			$code = $err['code'];

			if ($err['code'] != 0) {
				if (strpos($err['cmd_output'], 'StreamNotFound') !== false) {
					// there's no live stream available, don't complain about it
					continue;
				} else {
					// ffmpeg error
					throw new Exception("FFmpeg cannot get live thumbnail. (". $err['cmd_output'] .")");
				}
			} else {
				if (!is_readable($path_43 . $filename) || !(filesize($path_43 . $filename) > 0))
					throw new Exception("File is not readable.");
			}
			unset($err);

			if ($debug_mode) {
				$msg  = "[INFO] FFmpeg live thumbnail attempt for feed#". $channels[$i]['locationid'];
				$msg .= " / stream#". $channels[$i]['streamid'] ." - OK.\nCommand: '$cmd' / return code: $code\n";
				$debug->log($logdir, $logfile, $msg, false);
			}
		} catch (Exception $e) {
			$msg  = "[ERROR] FFmpeg live thumbnail attempt for feed#". $channels[$i]['locationid'];
			$msg .= " / stream#". $channels[$i]['streamid'] ." - Failed!\n". $e->getMessage() ."\nCommand: '$cmd' / return code: $code\n";
			$debug->log($logdir, $logfile, $msg, false);
			unset($cmd, $msg);

			if ($fatal)	return $code; // which errors should be considered fatal?
			
			continue;
		}

		try {
			// Copy images to server
			$remote_path = $app->config['livestreampath'];
			$err = ssh_filecopy2($app->config['fallbackstreamingserver']['server'], $temp_dir, $remote_path, false);
			
			if ($debug_mode) $debug->log($logdir, $logfile, "[INFO] Copying folder '". $temp_dir ."' > '". $remote_path ."'", false);			
			if ($err['code'] === false)	throw new Exception($err['message'] ."\nCommand: ". $err['command'] ."\nResult: ". $err['result']);

			// Chmod remote files
			foreach ($app->config['videothumbnailresolutions'] as $res) {
				$remote_file = $remote_path . $channels[$i]['streamid'] ."/". $res ."/". $filename;
				if ($debug_mode) {
					$msg = "[INFO] Setting file permission (". $jconf['file_owner'] ."/". $jconf['file_access'] .") on '". $remote_file ."'";
					$debug->log($logdir, $logfile, $msg, false);
				}

				$err = sshMakeChmodChown($app->config['fallbackstreamingserver']['server'], $remote_file, false);
				if ($err['code'] === false) throw new Exception("[ERROR] Failed to set permissions on: '". $remote_file ."'\nMSG: ". $err['message']);
			}
			unset($err);
		} catch (Exception $e) {
			$debug->log($logdir, $logfile, $e->getMessage(), false);
			continue;
		}

		// Update index photo filename
		$tmp = explode("/", $app->config['livestreampath']);
		$indexphotofilename = $tmp[count($tmp) - 2] ."/". $channels[$i]['streamid'] ."/". $app->config['videothumbnailresolutions']['4:3'] ."/". $filename;
		$err = @updateLiveFeedStreamIndexPhoto($channels[$i]['streamid'], $indexphotofilename);

		if ($debug_mode) {
			$msg  = "[OK] Updated live thumbs published for livefeed_stream.id = ". $channels[$i]['streamid'] ." at ". $app->config['fallbackstreamingserver']['server'] .":". $indexphotofilename ."\n";
			$debug->log($logdir, $logfile, $msg, $sendmail = false);
		}

		$app->watchdog();
	} // END OF MAIN LOOP //

	if (is_resource($db->_connectionID)) $db->close();
}

///////////////////////////////////////////////////////////////////////////////////////////////////
function getActiveChannels() {
///////////////////////////////////////////////////////////////////////////////////////////////////
//
// Returns all current live channels' livestream IDs as an array, and 
// FALSE when encountering an error or no livestreams available.
//
///////////////////////////////////////////////////////////////////////////////////////////////////
	global $jconf, $debug, $logdir, $logfile, $db, $app;

	if (is_resource($db->_connectionID) === false ) $db = db_maintain();
	
	$now = date("Y-m-d H:i:s");
	$query = "
		SELECT
			ch.id,
			ch.starttimestamp,
			ch.endtimestamp,
			ch.title,
			ch.isliveevent,
			lf.id AS locationid,
			lf.name AS locationname,
			lf.issecurestreamingforced,
			lf.indexphotofilename,
			lfs.id AS streamid,
			lfs.qualitytag AS streamname,
			lfs.keycode AS wowzastreamid,
			lfs.contentkeycode AS wowzacontentstreamid
		FROM
			channels AS ch,
			livefeeds AS lf,
			livefeed_streams AS lfs
		WHERE
			ch.starttimestamp <= '" . $now . "' AND
			ch.endtimestamp >= '" . $now . "' AND
			ch.id = lf.channelid AND
			lf.id = lfs.livefeedid AND
			lf.issecurestreamingforced = 0
		ORDER BY
			ch.id";

	try {
		$channels = $db->getArray($query);
	} catch (exception $err) {
		$debug->log($logdir, $logfile, "[ERROR] SQL query failed." . trim($query), $sendmail = false);
		return false;
	}

	// Check if any record returned
	if ( count($channels) < 1 ) return false;

	return $channels;
}

?>
