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
include_once('job_utils_media.php');

set_time_limit(0);

// Init
$app = new Springboard\Application\Cli(BASE_PATH, PRODUCTION);

// Load jobs configuration file
$app->loadConfig('modules/Jobs/config_jobs.php');
$jconf = $app->config['config_jobs'];
$myjobid = $jconf['jobid_live_thumb'];

// Log related init
$debug = Springboard\Debug::getInstance();
$debug->log($jconf['log_dir'], $myjobid . ".log", "Live thumbnail job started", $sendmail = false);

// Check operating system - exit if Windows
if ( iswindows() ) {
	echo "ERROR: Non-Windows process started on Windows platform\n";
	exit;
}

// Thumb sizes
$res_wide   = explode("x", $jconf['thumb_video_medium'], 2);
$res_43     = explode("x", $jconf['thumb_video_small'], 2);
$res_high	= explode("x", $jconf['thumb_video_large'], 2);

// Start an infinite loop - exit if any STOP file appears
while( !is_file( $app->config['datapath'] . 'jobs/' . $myjobid . '.stop' ) and !is_file( $app->config['datapath'] . 'jobs/all.stop' ) ) {

	clearstatcache();

	$app->watchdog();

	// Establish database connection
	$db = null;
	$db = db_maintain();

	$converter_sleep_length = $jconf['sleep_media'];

	// Watchdog
	$app->watchdog();

	// Query active channels
	$channels = getActiveChannels();
	if ( $channels === false ) break;

	for ( $i = 0; $i < count($channels); $i++ ) {

var_dump($channels);

		//// Prepare temp directories
		$temp_dir = "/tmp/" . $channels[$i]['streamid'] . "/";
		// Base working directory
		$err = create_remove_directory($temp_dir);
		if ( !$err['code'] ) {
			$debug->log($jconf['log_dir'], $myjobid . ".log", $err['message'] . "\n\nCOMMAND: " . $err['command'] . "\n\nRESULT: " . $err['result'], $sendmail = true);
			return false;
		}
		// Wide frames
		$err = create_remove_directory($temp_dir . $jconf['thumb_video_medium'] . "/");
		if ( !$err['code'] ) {
			$debug->log($jconf['log_dir'], $myjobid . ".log", $err['message'] . "\n\nCOMMAND: " . $err['command'] . "\n\nRESULT: " . $err['result'], $sendmail = true);
			return false;
		}
		// 4:3 frames
		$err = create_remove_directory($temp_dir . $jconf['thumb_video_small'] . "/");
		if ( !$err['code'] ) {
			$debug->log($jconf['log_dir'], $myjobid . ".log", $err['message'] . "\n\nCOMMAND: " . $err['command'] . "\n\nRESULT: " . $err['result'], $sendmail = true);
			return false;
		}
		// High resolution wide frame
		$err = create_remove_directory($temp_dir . $jconf['thumb_video_large'] . "/");
		if ( !$err['code'] ) {
			$debug->log($jconf['log_dir'], $myjobid . ".log", $err['message'] . "\n\nCOMMAND: " . $err['command'] . "\n\nRESULT: " . $err['result'], $sendmail = true);
			return false;
		}

		// RTMP URL - Use fallback always
		$rtmp_server = $app->config['fallbackstreamingserver'];

		$wowza_app = "vsqlive";
		if ( $app->config['baseuri'] == "dev.videosquare.eu/" ) $wowza_app = "dev" . $wowza_app;

		$rtmp_url = sprintf("rtmp://%s/" . $wowza_app . "/", $rtmp_server) . $channels[$i]['wowzastreamid'];

		$thumb_filename = "/tmp/" . $channels[$i]['streamid'] . ".png";
		$ffmpeg_command = 'rm -f ' . $temp_dir . $channels[$i]['streamid'] . '*.png ' . $temp_dir . $channels[$i]['streamid'] . '*.jpg > /dev/null 2>&1 ; ffmpeg -i ' . $rtmp_url . ' -vf "thumbnail" -frames:v 1 ' . $thumb_filename;
echo $ffmpeg_command . "\n";

		// Run ffmpeg
		$err = runExternal($ffmpeg_command);

		if ( is_readable($thumb_filename) and ( filesize($thumb_filename) > 0 ) ) {
			$debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] ffmpeg live thumb created. Error code = " . $err['code'] . ", lifefeed_stream.id = " . $channels[$i]['streamid'] . ", ffmpeg command = " . $ffmpeg_command . ". Full output:\n" . $err['cmd_output'], $sendmail = false);

		} else {
			// ffmpeg error: default logo
			$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] ffmpeg cannot get live thumb. Error code = " . $err['code'] . ", lifefeed_stream.id = " . $channels[$i]['streamid'] . ", ffmpeg command = " . $ffmpeg_command . ". Full output:\n" . $err['cmd_output'], $sendmail = false);
// def logo hogyan?
echo "no adas bazdmeg\n";
exit;
			continue;
		}

		$filename = $channels[$i]['streamid'] . ".png";
		$filename_jpg = $channels[$i]['streamid'] . ".jpg";

		$path_wide = $temp_dir . $jconf['thumb_video_medium'] . "/";
		$path_43 = $temp_dir . $jconf['thumb_video_small'] . "/";
		$path_highres = $temp_dir . $jconf['thumb_video_large'] . "/";

var_dump($path_wide);

var_dump($path_43);

var_dump($path_highres);

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

		} else {
			$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot copy image " . $thumb_filename . " to temp directories.\n", $sendmail = false);
		}

$size = getimagesize($thumb_filename);
var_dump($size);

		// Copy images to server

		$remote_path = $app->config['livestreampath'];
		$err = ssh_filecopy2($app->config['fallbackstreamingserver'], $temp_dir, $remote_path, false);
		if ( !$err['code'] ) {
			$debug->log($jconf['log_dir'], $myjobid . ".log", "MSG: " . $err['message'] . "\nCOMMAND: " . $err['command'] . "\nRESULT: " . $err['result'], $sendmail = false);
			return false;
		} else {
			$debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] Updated live thumbs published for livefeed_stream.id = " . $channels[$i]['streamid'] . " at " . $app->config['fallbackstreamingserver'] . ":" . $remote_path, $sendmail = false);
		}

// ALTER TABLE  `livefeed_streams` ADD  `indexphotofilename` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER  `status`;

exit;

	}

	// Watchdog
	$app->watchdog();

	// Close DB connection if open
	if ( is_resource($db->_connectionID) ) $db->close();

	$app->watchdog();

	sleep($converter_sleep_length);
	
}	// End of outer while

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
AND lfs.keycode = 420767
		GROUP BY
			lf.id
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
