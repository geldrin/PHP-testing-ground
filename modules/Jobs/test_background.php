<?php

function runExternal_vlc($cmd) {

	$descriptorspec = array(
		0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
		1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
		2 => array("pipe", "w")   // stderr is a file to write to
	);

	$pipes = array();
	$process = proc_open($cmd, $descriptorspec, $pipes);

	$output = "";

	if (!is_resource($process)) return false;

	// close child's input imidiately
	fclose($pipes[0]);

	stream_set_blocking($pipes[1], false);
	stream_set_blocking($pipes[2], false);

/*	$todo = array($pipes[1], $pipes[2]);

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
			$output .= $s;
		}
	} */

	fclose($pipes[1]);
	fclose($pipes[2]);

	$return_array = array();

	// Get process ID (proc_get_status() gives wrong PID)
	$ps = `ps -C vlc -o pid=`;
	$PID = (int)trim($ps);
	if ( !is_numeric($PID) ) $PID = -1;

	$return_array['pid'] = $PID;
	$return_array['code'] = proc_close($process);
	$return_array['cmd_output'] = $output;

	return $return_array;
}

function is_process_running($PID) {

	exec("ps $PID", $ProcessState);
	return(count($ProcessState) >= 2);
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

echo "aaaa\n";

$vlc_command = "cvlc -I dummy";
$command = $jconf['nice'] . " " . $vlc_command . " 2> /dev/null &";

echo $command . "\n";

$err = runExternal_vlc($command);

var_dump($err);

$PID = $err['pid'];

echo "PID = $PID\n";

if ( is_process_running($PID) ) {
echo "run\n";
} else {
echo "norun\n";
}

if ( posix_kill($PID, SIGQUIT) ) {
echo "sigquit sent\n";
} else {
echo "sigquit not sent\n";
}

sleep(2);

if ( is_process_running($PID) ) {
echo "run\n";
} else {
echo "norun\n";
}

exit;

?>
