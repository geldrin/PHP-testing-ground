<?php
// Media conversion job v0 @ 2012/02/??

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
$jconf = $app->config['config_jobs'];
$myjobid = $jconf['jobid_live_thumb'];

// Check operating system - exit if Windows
if ( iswindows() ) {
	echo "ERROR: Non-Windows process started on Windows platform\n";
	exit;
}

// Start an infinite loop - exit if any STOP file appears
if ( is_file( $app->config['datapath'] . 'jobs/' . $myjobid . '.stop' ) or is_file( $app->config['datapath'] . 'jobs/all.stop' ) ) exit;

// Log init
$debug = Springboard\Debug::getInstance();

// Already running. Not finished a tough job?
$run_filename = $jconf['temp_dir'] . $myjobid . ".run";
if  ( file_exists($run_filename) ) {
	if ( ( time() - filemtime($run_filename) ) < 15 * 60 ) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] " . $myjobid . " is already running. Not finished previous run? See: " . $run_filename . " (created: " . date("Y-m-d H:i:s", filemtime($run_filename)) . ")", $sendmail = true);
	}
	exit;
} else {
	$content = "Running. Started: " . date("Y-m-d H:i:s");
	$err = file_put_contents($run_filename, $content);
	if ( $err === false ) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot write run file " . $run_filename, $sendmail = true);
	}
}

$debug->log($jconf['log_dir'], $myjobid . ".log", "*************************** Job: Live thumbnail started ***************************", $sendmail = false);

if ( !is_writable($jconf['livestreams_dir']) ) {
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Temp directory " . $jconf['livestreams_dir'] . " is not writeable.", $sendmail = false);
	exit;
}

// Thumb sizes
$res_wide   = explode("x", $jconf['thumb_video_medium'], 2);
$res_43     = explode("x", $jconf['thumb_video_small'], 2);
$res_high	= explode("x", $jconf['thumb_video_large'], 2);

clearstatcache();

$app->watchdog();

// Establish database connection
$db = db_maintain();

$converter_sleep_length = $jconf['sleep_media'];

// Watchdog
$app->watchdog();

// Query active channels
$channels = getActiveChannels();
if ( $channels === false ) {
	// Close DB connection if open
	if ( is_resource($db->_connectionID) ) $db->close();
	// Remove run file
	unlink($run_filename);
	exit;
}

//var_dump($channels);

for ( $i = 0; $i < count($channels); $i++ ) {

	// Temp directory
	$temp_dir = $jconf['livestreams_dir'] . $channels[$i]['streamid'] . "/";

	// RTMP URL - Use fallback server always
	$rtmp_server = $app->config['fallbackstreamingserver'];

	$wowza_app = "vsqlive";
	if ( $app->config['baseuri'] == "dev.videosquare.eu/" ) $wowza_app = "dev" . $wowza_app;

	$datetime = date("YmdHis");

	$rtmp_url = sprintf("rtmp://%s/" . $wowza_app . "/", $rtmp_server) . $channels[$i]['wowzastreamid'];

	$thumb_filename = "/tmp/" . $channels[$i]['streamid'] . "_" . rand(100000,999999) . ".png";
	$ffmpeg_command = 'ffmpeg -v 0 -i ' . $rtmp_url . ' -vf "thumbnail" -frames:v 1 ' . $thumb_filename;
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] ffmpeg live thumb atempt for feed#" . $channels[$i]['locationid'] . "/stream#" . $channels[$i]['streamid'], $sendmail = false);

	$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] ffmpeg command to be executed: " . $ffmpeg_command, $sendmail = false);

	// Run ffmpeg
	$err = runExt($ffmpeg_command);

	if ( is_readable($thumb_filename) and ( filesize($thumb_filename) > 0 ) ) {

		$debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] ffmpeg live thumb created. Error code = " . $err['code'] . ", feed#" . $channels[$i]['locationid'] . "/stream#" . $channels[$i]['streamid'] . ", ffmpeg command = \"" . $ffmpeg_command . "\". Full output:\n" . $err['cmd_output'], $sendmail = false);

		// ## Prepare working directories
		// Base working directory
		$err = create_remove_directory($temp_dir);
		if ( !$err['code'] ) {
			$debug->log($jconf['log_dir'], $myjobid . ".log", $err['message'] . "\n\nCOMMAND: " . $err['command'] . "\n\nRESULT: " . $err['result'], $sendmail = true);
			exit -1;
		}
		// Wide frames
		$err = create_remove_directory($temp_dir . $jconf['thumb_video_medium'] . "/");
		if ( !$err['code'] ) {
			$debug->log($jconf['log_dir'], $myjobid . ".log", $err['message'] . "\n\nCOMMAND: " . $err['command'] . "\n\nRESULT: " . $err['result'], $sendmail = true);
			exit -1;
		}
		// 4:3 frames
		$err = create_remove_directory($temp_dir . $jconf['thumb_video_small'] . "/");
		if ( !$err['code'] ) {
			$debug->log($jconf['log_dir'], $myjobid . ".log", $err['message'] . "\n\nCOMMAND: " . $err['command'] . "\n\nRESULT: " . $err['result'], $sendmail = true);
			exit -1;
		}
		// High resolution wide frame
		$err = create_remove_directory($temp_dir . $jconf['thumb_video_large'] . "/");
		if ( !$err['code'] ) {
			$debug->log($jconf['log_dir'], $myjobid . ".log", $err['message'] . "\n\nCOMMAND: " . $err['command'] . "\n\nRESULT: " . $err['result'], $sendmail = true);
			exit -1;
		}

		// Chmod local directory
		$command = "chmod -f -R " . $jconf['directory_access'] . " " . $temp_dir . " 2>&1";
		exec($command, $output, $result);
		$output_string = implode("\n", $output);
		if ( $result != 0 ) {
			$debug->log($jconf['log_dir'], $myjobid . ".log", "[WARN] Chmod failed. Command: " . $command, $sendmail = false);
		}

	} else {
		// ffmpeg error: default logo
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] ffmpeg cannot get live thumb. Error code = " . $err['code'] . ", lifefeed_stream.id = " . $channels[$i]['streamid'] . ", ffmpeg command = " . $ffmpeg_command . ". Full output:\n" . $err['cmd_output'], $sendmail = false);
		// No index photo update, keep existing
		continue;
	}

	// Thumbnail OK: resize it
	$filename = $channels[$i]['streamid'] . "_" . $datetime . ".png";
	$filename_jpg = $channels[$i]['streamid'] . "_" . $datetime . ".jpg";

	$path_wide = $temp_dir . $jconf['thumb_video_medium'] . "/";
	$path_43 = $temp_dir . $jconf['thumb_video_small'] . "/";
	$path_highres = $temp_dir . $jconf['thumb_video_large'] . "/";

	if ( copy($thumb_filename, $path_wide . $filename) && copy($thumb_filename, $path_43 . $filename) && copy($thumb_filename, $path_highres . $filename) ) {

		// Wide thumb
		try {
			\Springboard\Image::resizeAndCropImage($path_wide . $filename, $res_wide[0], $res_wide[1], 'top');
			png2jpg($path_wide . $filename, $path_wide . $filename_jpg, 95);
			unlink($path_wide . $filename);
		} catch (exception $err) {
			$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot resize image. File = " . $path_wide . $filename . ".", $sendmail = false);
		}

		// 4:3 thumb
		try {
			\Springboard\Image::resizeAndCropImage($path_43 . $filename, $res_43[0], $res_43[1]);
			png2jpg($path_43 . $filename, $path_43 . $filename_jpg, 95);
			unlink($path_43 . $filename);
		} catch (exception $err) {
			$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot resize image. File = " . $path_43 . $filename . ".", $sendmail = false);
		}

		// High resolution thumb
		try {
			\Springboard\Image::resizeAndCropImage($path_highres . $filename, $res_high[0], $res_high[1]);
			png2jpg($path_highres . $filename, $path_highres . $filename_jpg, 95);
			unlink($path_highres . $filename);
		} catch (exception $err) {
			$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot resize image. File = " . $path_highres . $filename . ".", $sendmail = false);
		}

		unlink($thumb_filename);

	} else {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot copy image " . $thumb_filename . " to temp directories.\n", $sendmail = false);
	}

	// Copy images to server
	$remote_path = $app->config['livestreampath'];
	$err = ssh_filecopy2($app->config['fallbackstreamingserver'], $temp_dir, $remote_path, false);
	if ( !$err['code'] ) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "MSG: " . $err['message'] . "\nCOMMAND: " . $err['command'] . "\nRESULT: " . $err['result'], $sendmail = false);
		continue;
	}

	// SSH chmod/chown
	$err = sshMakeChmodChown($app->config['fallbackstreamingserver'], $remote_path . $channels[$i]['streamid'] . "/" .$jconf['thumb_video_small'] . "/" . $filename_jpg, false);
	if ( !$err['code'] ) $debug->log($jconf['log_dir'], $myjobid . ".log", "MSG: " . $err['message'] . "\nCOMMAND: " . $err['command'] . "\nRESULT: " . $err['result'], $sendmail = false);
	$err = sshMakeChmodChown($app->config['fallbackstreamingserver'], $remote_path . $channels[$i]['streamid'] . "/" .$jconf['thumb_video_medium'] . "/" . $filename_jpg, false);
	if ( !$err['code'] ) $debug->log($jconf['log_dir'], $myjobid . ".log", "MSG: " . $err['message'] . "\nCOMMAND: " . $err['command'] . "\nRESULT: " . $err['result'], $sendmail = false);
	$err = sshMakeChmodChown($app->config['fallbackstreamingserver'], $remote_path . $channels[$i]['streamid'] . "/" .$jconf['thumb_video_large'] . "/" . $filename_jpg, false);
	if ( !$err['code'] ) $debug->log($jconf['log_dir'], $myjobid . ".log", "MSG: " . $err['message'] . "\nCOMMAND: " . $err['command'] . "\nRESULT: " . $err['result'], $sendmail = false);

	// Update index photo filename
	$tmp = explode("/", $app->config['livestreampath']);
	$indexphotofilename = $tmp[count($tmp)-2] . "/" . $channels[$i]['streamid'] . "/" . $jconf['thumb_video_small'] . "/" . $filename_jpg;
	updateLiveFeedStreamIndexPhoto($channels[$i]['streamid'], $indexphotofilename);
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] Updated live thumbs published for livefeed_stream.id = " . $channels[$i]['streamid'] . " at " . $app->config['fallbackstreamingserver'] . ":" . $remote_path, $sendmail = false);

	// Watchdog
	$app->watchdog();
}

// Close DB connection if open
if ( is_resource($db->_connectionID) ) $db->close();

// Remove run file
unlink($run_filename);

exit;


function getActiveChannels() {
global $jconf, $debug, $db, $app, $myjobid;

	$db = db_maintain();

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
			lfs.name AS streamname,
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
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = false);
		return false;
	}

	// Check if any record returned
	if ( count($channels) < 1 ) return false;

	return $channels;
}

function png2jpg($originalFile, $outputFile, $quality) {
	$image = imagecreatefrompng($originalFile);
	imagejpeg($image, $outputFile, $quality);
	imagedestroy($image);;
}

?>
