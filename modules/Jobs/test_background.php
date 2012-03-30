<?php

function runExternal_vlc($cmd, $output_file) {

	$descriptorspec = array(
		0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
		1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
		2 => array("pipe", "w")   // stderr is a file to write to
	);

	$pipes = array();
	$process = proc_open($cmd, $descriptorspec, $pipes);

	$output = "VLC started: " . date("Y-m-d H:i:s") . "\n";

	if (!is_resource($process)) return false;

	// close child's input imidiately
	fclose($pipes[0]);

	stream_set_blocking($pipes[1], false);
	stream_set_blocking($pipes[2], false);

	$return_array = array();

	// Get process ID (proc_get_status() gives wrong PID)
	$ps = `ps -C vlc -o pid=`;
	$PID = (int)trim($ps);
	if ( !is_numeric($PID) ) $PID = -1;
	$return_array['pid'] = $PID;

	$todo = array($pipes[1], $pipes[2]);

	while( true ) {

		$read = array();
		if( !feof($pipes[1]) ) $read[]= $pipes[1];
		if( !feof($pipes[2]) ) $read[]= $pipes[2];

		if (!$read) break;

		$write = NULL;
		$ex = NULL;
		$ready = stream_select($read, $write, $ex, 2);

		if ( $ready === FALSE ) {
			break; // should never happen - something died
		}

		foreach ($read as $r) {
			$s = fread($r, 1024);
			$tmp = trim($s);
			if ( !empty($tmp) ) {
				$output .= $tmp;
				$end_str = stripos($tmp, "kb/s:" );

//echo $date . " " . $tmp . "\n";

				$err = is_process_closedfile($output_file, $PID);

				if ( ( $end_str !== FALSE ) and ( $err['code'] == TRUE ) ) {

					if ( !posix_kill($PID, SIGQUIT) ) {
						echo "ERROR: notkilled: $PID\n";
					}
				}

				break;

			}

		}
	}

	fclose($pipes[1]);
	fclose($pipes[2]);

	$return_array['code'] = proc_close($process);
	$return_array['cmd_output'] = $output;

echo $output . "\n";

	return $return_array;
}

function is_process_running($PID) {

	exec("ps $PID", $ProcessState);
	return(count($ProcessState) >= 2);
}

function is_process_closedfile($file, $PID) {

	$err['command'] = "-";
	$err['command_output'] = "-";
	$err['result'] = 0;

	if ( !file_exists($file) ) {
		$err['code'] = FALSE;
        $err['message'] = "[ERROR] File does not exist: " . $file;
		return $err;
	}

	$command = "lsof -t " . $file;
	$lsof = `$command`;
	$err['command'] = $command;
	$lsof_output = trim($lsof);
	$err['command_output'] = $lsof_output;
	if ( empty($lsof_output) ) {
		$err['code'] = TRUE;
		return $err;
	} else {
		$err['code'] = FALSE;
        $err['message'] = "[ERROR] Unexpected command output from: " . $command;
		return $err;
	}

	// Check if PID is provided
	$PID_working = (int)$lsof_output;
	if ( is_numeric($PID_working) ) {
		$err['code'] = FALSE;
        $err['message'] = "[MSG] File is opened by process " . $PID_working;
		return $err;
	}

	return $err;
}

define('BASE_PATH',	realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');

// Utils
include_once('job_utils_base.php');
include_once('job_utils_log.php');
include_once('job_utils_status.php');
include_once('job_utils_media.php');

// Init
$app = new Springboard\Application\Cli(BASE_PATH, PRODUCTION);

// Load jobs configuration file
$app->loadConfig('modules/Jobs/config_jobs.php');
$jconf = $app->config['config_jobs'];

$smarty = $app->bootstrap->getSmarty();
$smarty->assign('content_file', "file:///home/conv/vlc/1_content.mov");
$smarty->assign('video_file', "file:///home/conv/vlc/1.flv");

$fps = 30;
$delay = 0;
$l_width = 640;
$l_height = 512;
$s_width = 128;
$s_height = 72;
$video_bw = 800;
$output_file = "/home/conv/vlc/test.mp4";
$audio_bw = 128;
$audio_ch = 2;
$audio_sr = 44100;
$background = "file:///home/conv/vlc/black.png";
$h264_profile = "baseline";

$smarty->assign('fps', $fps);
$smarty->assign('delay', $delay);
$smarty->assign('audio_bw', $audio_bw);
$smarty->assign('audio_ch', $audio_ch);
$smarty->assign('audio_sr', $audio_sr);
$smarty->assign('video_bw', $video_bw);
$smarty->assign('l_width', $l_width);
$smarty->assign('l_height', $l_height);
$smarty->assign('s_width', $s_width);
$smarty->assign('s_height', $s_height);
$smarty->assign('output_file', $output_file);
$smarty->assign('background', $background);
$smarty->assign('h264_profile', $h264_profile);

//$smarty->assign('language', "hu");
//$smarty->assign('recid', 1234);
$cfg_file = $smarty->fetch('Jobs/vlc_video.tpl');

echo $cfg_file . "\n";


exit;

$vlc_command = "cvlc -I dummy --stop-time=10 --mosaic-width=640 --mosaic-height=512 --mosaic-keep-aspect-ratio --mosaic-keep-picture --mosaic-xoffset=0 --mosaic-yoffset=0 --mosaic-position=2 --mosaic-offsets=\"0,0,10,10\" --mosaic-order=\"1,2\" --vlm-conf /home/conv/vlc/video_ok.cfg";

//$command = $jconf['nice'] . " " . $vlc_command . " 2> /dev/null &";
$command = $jconf['nice'] . " " . $vlc_command;

echo $command . "\n";

$err = runExternal_vlc($command, "/home/conv/vlc/test.mp4");

exit;

?>
