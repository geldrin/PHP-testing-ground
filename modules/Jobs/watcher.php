<?php

define('BASE_PATH',	realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );
define('PROCESSID', posix_getpid() );

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');

// Utils
include_once('job_utils_base.php');

set_time_limit(0);

// Init
$app = new Springboard\Application\Cli(BASE_PATH, PRODUCTION);

// Load jobs configuration file
$app->loadConfig('modules/Jobs/config_jobs.php');
$jconf = $app->config['config_jobs'];

// Log related init
$debug = Springboard\Debug::getInstance();

if ( iswindows() ) {
	echo "ERROR: Non-Windows process started on Windows platform\n";
	exit;
}

$kill = '';

clearstatcache();

$app->watchdog();

$command = "cat /proc/cpuinfo > " . $jconf['temp_dir'] . "cpuinfo";
exec($command, $output, $result);
$command = "cat /proc/meminfo > " . $jconf['temp_dir'] . "meminfo";
exec($command, $output, $result);

$uptime = `uptime 2>&1`;
file_put_contents($jconf['temp_dir'] . 'uptime', $uptime);

$processes = `ps uax | egrep "job_|watcher" | grep -v "grep" 2>&1`;
file_put_contents($jconf['temp_dir'] . 'processids', $processes);

// Job list and watchdog timeouts
if ( $jconf['node_role'] == 'converter' ) {
	// Converter node jobs
	$jobs = array(
		$jconf['jobid_media_convert']	=> 15*60,	// 15 minutes (if no update or ffmpeg is not running)
		$jconf['jobid_content_convert']	=> 15*60,	// 15 minutes (if no update or ffmpeg is not running)
	);
} else {
	// Front-end jobs
	$jobs = array(
	  'haho.php'						=> 60,
	);
}

$now_minutes = date('i');

// Check if all.stop file is present blocking all the jobs
$stop_file = $app->config['datapath'] . 'jobs/all.stop';
if ( file_exists($stop_file) ) {
	$msg = "WARNING: jobs are not running. See stop file:\n\n" . $stop_file . "\n\nRemove it to start all jobs. This message is sent once every hour.";
	// Send mail once every hour to warn admin
	if ( ( $now_minutes > 0 ) and ( $now_minutes < 6 ) ) {
		$debug->log($jconf['log_dir'], $jconf['jobid_watcher'] . ".log", $msg, $sendmail = true);
	}
	exit;
}

$jobs_isstopped = FALSE;
$jobs_stopped = "";
$jobs_toskip = array();

// Check job stop files one by one and report if any exists
foreach ( $jobs as $job => $difference ) {
	// Check if any stop file is present
	$jobs_toskip[$job] = FALSE;
	$stop_file = $app->config['datapath'] . 'jobs/' . $job . '.stop';
	if ( file_exists($stop_file) ) {
		$jobs_isstopped = TRUE;
		$jobs_stopped .= $stop_file . "\n";
		$jobs_toskip[$job] = TRUE;
	}
}

// Report jobs stopped
if ( $jobs_isstopped ) {
	$msg = "WARNING: some jobs may not running. See stop file(s):\n\n" . $jobs_stopped . "\nRemove them to restart jobs. This message is sent once every hour.";
	if ( ( $now_minutes > 0 ) and ( $now_minutes < 6 ) ) {
		$debug->log($jconf['log_dir'], $jconf['jobid_watcher'] . ".log", $msg, $sendmail = true);
	}
}

// Is ffmpeg running?
$ffmpeg_running = FALSE;
$output = array();
$cmd = 'ps uax | grep "ffmpeg" | grep -v "grep" | wc -l 2>&1';
exec($cmd, $output, $result);

if ( $output[0] > 0 ) $ffmpeg_running = TRUE;

foreach ( $jobs as $job => $difference ) {

	if ( $jobs_toskip[$job] == TRUE ) continue;

	$output = array();
	$grep_target = "php -f " . $jconf['job_dir'];

	$cmd = 'ps uax | grep "' . $grep_target . $job . '.php" | grep -v "grep" | wc -l 2>&1';
	exec($cmd, $output, $result);

	$processes = `ps uax | egrep "$grep_target(job_|watcher)" | grep -v "grep" 2>&1`;
	file_put_contents($jconf['temp_dir'] . 'processids', $processes );

	$num_jobs = $output[0];

	$body  = "";
	$body .= "SITE: " . $app->config['baseuri'] . "\n";
	$body .= "NODE: " . $jconf['node'] . "\n";
	$body .= "NODE UPTIME: " . $uptime . "\n";
	$body .= "JOB: " . $job . ".php\n";
	$body .= "WATCHER PROCESSID: " . PROCESSID . "\n";
	$body .= "COMMAND: " . $cmd . "\n";
	$body .= "COMMAND OUTPUT:\n" . var_export($output, 1) . "\n";
	$body .= "RESULT: " . $result . "\n";
	$body .= "PROCESSES:\n\n" . $processes . "\n";
	$body .= "TIME: " . date("G:i:s.u");

	switch ( $num_jobs ) {
		case '0':
			// Not running: execute script and send alert message
			$inditas  = microtime(true);
			$command = "/usr/bin/php -f " . $jconf['job_dir'] . $job . ".php >/dev/null &";
			exec($command, $output, $result);
			$megallas = microtime( true );
			$output_string = implode("\n", $output);

			$msg  = "Did not find " . $job . ".php process.\n\nDetailed information:\n\n";
			$msg .= $body . "\n\n";
			$msg .= "Job restarted, information:\n";
			$msg .= "COMMAND: " . $command . "\n";
			$msg .= "OUTPUT: " . $output_string . "\n";
			$msg .= "RESULT: ";
			if ( $result != 0 ) {
				$msg .= "Cannot restart PHP script\n";
			} else {
				$msg .= "OK\n";
			}
			$msg .= "COMMAND DURATION: " . ($megallas - $inditas) . "usec\n";

			$debug->log($jconf['log_dir'], $jconf['jobid_watcher'] . ".log", $msg, $sendmail = true);
			break;
		case '1':
			// Running: check watchdog time difference (if larger and ffmpeg is not running...)
			$time = @filemtime( $app->config['datapath'] . 'watchdog/' . $job . '.php.watchdog' );
			if ( ( ( time() - $time ) >= $difference ) && ( $ffmpeg_running === FALSE ) ) {
				$msg  = "Job " . $job . ".php is stalled.\n\nDetailed information:\n\n";
				$msg .= $body . "\n\n";
				$debug->log($jconf['log_dir'], $jconf['jobid_watcher'] . ".log", $msg, $sendmail = true);
			}
			break;
		default:
			// Running in more than 1 instances: undefined (send alert)
			$msg  = "Job " . $job . ".php is running in more instances.\n\nDetailed information:\n\n";
			$msg .= $body . "\n\n";
			$debug->log($jconf['log_dir'], $jconf['jobid_watcher'] . ".log", $msg, $sendmail = true);
			break;
	}

}

$app->watchdog();

exit;

?>