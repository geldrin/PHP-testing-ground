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

$temp_dir = "/tmp/";

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

		// RTMP URL
		$rtmp_server = $app->config['fallbackstreamingserver'];

		$wowza_app = "vsqlive";
		if ( $app->config['baseuri'] == "dev.videosquare.eu/" ) $wowza_app = "dev" . $wowza_app;

		$rtmp_url = sprintf("rtmp://%s/" . $wowza_app . "/", $rtmp_server) . $channels[$i]['wowzastreamid'];

		$thumb_filename = $temp_dir . $channels[$i]['locationid'] . ".png";
		$ffmpeg_command = 'rm -f ' . $temp_dir . $channels[$i]['locationid'] . '*.png ' . $temp_dir . $channels[$i]['locationid'] . '*.jpg > /dev/null 2>&1 ; ffmpeg -i ' . $rtmp_url . ' -vf "thumbnail" -frames:v 1 ' . $thumb_filename;
echo $ffmpeg_command . "\n";

		// Run ffmpeg
		$err = runExternal($ffmpeg_command);

		if ( is_readable($thumb_filename) and ( filesize($thumb_filename) > 0 ) ) {
			$debug->log($jconf['log_dir'], $myjobid . ".log", "[OK] ffmpeg live thumb created. Error code = " . $err['code'] . ", lifefeed id = " . $channels[$i]['locationid'] . ", ffmpeg command = " . $ffmpeg_command . ". Full output:\n" . $err['cmd_output'], $sendmail = false);

		} else {
			// ffmpeg error: default logo
			$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] ffmpeg cannot get live thumb. Error code = " . $err['code'] . ", lifefeed id = " . $channels[$i]['locationid'] . ", ffmpeg command = " . $ffmpeg_command . ". Full output:\n" . $err['cmd_output'], $sendmail = false);
// def logo hogyan?

			continue;
		}

		$filename_wide = $temp_dir . $channels[$i]['locationid'] . "_wide.png";
		$filename_wide_jpg = $temp_dir . $channels[$i]['locationid'] . "_wide.jpg";
		$filename_43 = $temp_dir . $channels[$i]['locationid'] . "_sm.png";
		$filename_highres = $temp_dir . $channels[$i]['locationid'] . "_high.png";

		if ( copy($thumb_filename, $filename_wide) && copy($thumb_filename, $filename_43) && copy($thumb_filename, $filename_highres) ) {

			// Wide thumb
			try {
				\Springboard\Image::resizeAndCropImage($filename_wide, $res_wide[0], $res_wide[1], 'top');
			} catch (exception $err) {
				$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot copy " . $err['code'] . ", lifefeed id = " . $channels[$i]['locationid'] . ", ffmpeg command = " . $ffmpeg_command . ". Full output:\n" . $err['cmd_output'], $sendmail = false);
			}

			\Springboard\Image::resizeImageMagick($filename_wide, $filename_wide_jpg, $res_wide[0], $res_wide[1], "jpeg" );
exit;

			// 4:3 thumb
			try {
				\Springboard\Image::resizeAndCropImage($filename_43, $res_43[0], $res_43[1]);
			} catch (exception $err) {
				$errors['messages'] .= "[ERROR] File " . $filename_43 . " resizeAndCropImage() failed to " . $res_43[0] . "x" . $res_43[1] . ".\n";
				$iserror = $is_error_now = TRUE;
			}
			// High resolution thumb
			try {
				\Springboard\Image::resizeAndCropImage($filename_highres, $res_high[0], $res_high[1]);
			} catch (exception $err) {
				$errors['messages'] .= "[ERROR] File " . $filename_highres . " resizeAndCropImage() failed to " . $res_high[0] . "x" . $res_high[1] . ".\n";
				$iserror = $is_error_now = TRUE;
			}

		} else {
echo "error\n";
exit;
		}

		// Resize
$size = getimagesize($thumb_filename);
var_dump($size);

// Resize images according to thumbnail requirements. If one of them fails we cancel all the others.


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
AND lfs.keycode = 215278
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


?>
