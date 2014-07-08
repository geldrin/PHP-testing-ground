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
if ( $app->config['node_role'] == 'converter' ) {
	// Converter node jobs
	$jobs = array(
		$jconf['jobid_media_convert']	=> array(
			'watchdogtimeoutsecs'	=> 15 * 60,
			'supresswarnings'		=> false
		),
		$jconf['jobid_content_convert']	=> array(
			'watchdogtimeoutsecs'	=> 15 * 60,
			'supresswarnings'		=> true
		),
		$jconf['jobid_vcr_control']		=> array(
			'watchdogtimeoutsecs'	=> 15 * 60,
			'supresswarnings'		=> false
		),
		$jconf['jobid_document_index']	=> array(
			'watchdogtimeoutsecs'	=> 5 * 60,
			'supresswarnings'		=> false
		),
/*		$jconf['jobid_media_convert2']	=> array(
			'watchdogtimeoutsecs'	=> 15 * 60,
			'supresswarnings'		=> false
		)
*/
	);
} else {
	// Front-end jobs
	$jobs = array(
		$jconf['jobid_upload_finalize']	=> array(
			'watchdogtimeoutsecs'	=> 60,
			'supresswarnings'		=> false
		)
	);
}

// Send alert of stopped jobs between 06:00:00 - 06:05:00
$alert_time_start = 6 * 3600 + 0 * 60 + 0;
$alert_time_end = 6 * 3600 + 5 * 60 + 0;
$now_time = date('G') * 3600 + date('i') * 60 + date('s');
// Check if all.stop file is present blocking all the jobs
$stop_file = $app->config['datapath'] . 'jobs/all.stop';
if ( file_exists($stop_file) ) {
	$msg = "WARNING: jobs are not running. See stop file:\n\n" . $stop_file . "\n\nRemove it to start all jobs. This message is sent once every day.";
	// Send mail once every hour to warn admin
	if ( ( $now_time > $alert_time_start ) and ( $now_time < $alert_time_end ) ) {
		// Log: log to file using no DB, then send mail
		$debug->log($jconf['log_dir'], $jconf['jobid_watcher'] . ".log", $msg, $sendmail = false);
		sendHTMLEmail_errorWrapper('[' . $app->bootstrap->config['siteid'] . ' log]: ' . substr( $msg, 0, 100 ), $msg, false);
	}
	exit;
}

// Check job stop files one by one and report if any exists
$jobs_isstopped = false;
$jobs_stopped = "";
$jobs_toskip = array();
foreach ( $jobs as $job => $job_info ) {
	// Check if any stop file is present
	$jobs_toskip[$job] = false;
	$stop_file = $app->config['datapath'] . 'jobs/' . $job . '.stop';
	if ( file_exists($stop_file) ) {
		$jobs_isstopped = true;
		if ( $job_info['supresswarnings'] == false ) $jobs_stopped .= $stop_file . "\n";
		$jobs_toskip[$job] = true;
	}
}

// Report jobs stopped
if ( $jobs_isstopped and !empty($jobs_stopped) ) {
	$msg = "WARNING: some jobs may not running. See stop file(s):\n\n" . $jobs_stopped . "\nRemove them to restart jobs. This message is sent once every hour.";
	if ( ( $now_time > $alert_time_start ) and ( $now_time < $alert_time_end ) ) {
		// Log: log to file using no DB, then send mail
		$debug->log($jconf['log_dir'], $jconf['jobid_watcher'] . ".log", $msg, $sendmail = false);
		sendHTMLEmail_errorWrapper('[' . $app->bootstrap->config['siteid'] . ' log]: ' . substr($msg, 0, 100), $msg, false);
	}
}

//// Is any conversion running?
$isrunning_mediaconv = false;
$isrunning_contentconv = false;
// Media conversion/ffmpeg: check if running
$output = array();
$check_string = $jconf['media_dir'];
$cmd = 'ps uax | grep "ffmpeg.*' . $check_string . '" | grep -v "grep" | wc -l 2>&1';
exec($cmd, $output, $result);
if ( isset($output[0]) ) if ( $output[0] > 0 ) $isrunning_mediaconv = true;
// Content conversion/ffmpeg
$output = array();
$check_string = $jconf['content_dir'];
$cmd = 'ps uax | grep "ffmpeg.*' . $check_string . '" | grep -v "grep" | wc -l 2>&1';
exec($cmd, $output, $result);
if ( isset($output[0]) ) if ( $output[0] > 0 ) $isrunning_contentconv = true;
// Content conversion/VLC: check if running
// !!! NEW CONV: REMOVE !!!
$output = array();
$cmd = 'ps uax | grep "cvlc" | grep -v "grep" | wc -l 2>&1';
exec($cmd, $output, $result);
if ( isset($output[0]) ) if ( $output[0] > 0 ) $isrunning_contentconv = true;
// !!! NEW CONV: REMOVE !!!

foreach ( $jobs as $job => $job_info ) {

	if ( $jobs_toskip[$job] == true ) continue;

	$output = array();
	$grep_target = "php -f " . $jconf['job_dir'];

	// List all running jobs
	$processes = `ps uax | egrep "$grep_target(job_|watcher)" | grep -v "grep" 2>&1`;
	file_put_contents($jconf['temp_dir'] . 'processids', $processes);
	$debug->log($jconf['log_dir'], $jconf['jobid_watcher'] . ".log", "[INFO] All running jobs:\n" . $processes, $sendmail = false);

	// Check if job is running
	$cmd = 'ps uax | grep "' . $grep_target . $job . '.php" | grep -v "grep" | wc -l 2>&1';
	exec($cmd, $output, $result);
	$debug->log($jconf['log_dir'], $jconf['jobid_watcher'] . ".log", "[INFO] Command executed: " . $cmd . "\nResult: " . $result . "\nOutput: " . var_export($output, 1), $sendmail = false);

	$num_jobs = $output[0];

	$body  = "";
	$body .= "SITE: " . $app->config['baseuri'] . "\n";
	$body .= "NODE: " . $app->config['node_sourceip'] . "\n";
	$body .= "NODE UPTIME: " . $uptime . "\n";
	$body .= "JOB: " . $job . ".php\n";
	$body .= "WATCHER PROCESSID: " . PROCESSID . "\n";
	$body .= "COMMAND: " . $cmd . "\n";
	$body .= "COMMAND OUTPUT:\n" . var_export($output, 1) . "\n";
	$body .= "RESULT: " . $result . "\n";
	$body .= "PROCESSES:\n\n" . $processes . "\n";
	$body .= "TIME: " . date("Y-m-d G:i:s.u");

	switch ( $num_jobs ) {
		case '0':
			// Not running: execute script and send alert message

			// Problem: sometime ps does not give result while the process is running. Maybe some strange transient is occured? As a workaround, let's wait for some time and double check if process is running.
			sleep(1);
			$cmd = 'ps uax | grep "' . $grep_target . $job . '.php" | grep -v "grep" | wc -l 2>&1';
			exec($cmd, $output, $result);
			$debug->log($jconf['log_dir'], $jconf['jobid_watcher'] . ".log", "[INFO] Process recheck command executed: " . $cmd . "\nResult: " . $result . "\nOutput: " . var_export($output, 1), $sendmail = false);

			if ( $output[0] > 0 ) {
				$debug->log($jconf['log_dir'], $jconf['jobid_watcher'] . ".log", "[OK] Process recheck: process is running. No restart.", $sendmail = false);
				break;
			}

			$inditas  = microtime(true);
			$command = "/usr/bin/php -f " . $jconf['job_dir'] . $job . ".php > /dev/null &";
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

			// Log: log to file using no DB, then send mail
			$debug->log($jconf['log_dir'], $jconf['jobid_watcher'] . ".log", $msg, $sendmail = false);
			sendHTMLEmail_errorWrapper('[' . $app->bootstrap->config['siteid'] . ' log]: ' . substr( $msg, 0, 100 ), $msg, false);
			break;
		case '1':
			// Running: check watchdog time difference (if larger and ffmpeg is not running...)
			$time = @filemtime( $app->config['datapath'] . 'watchdog/' . $job . '.php.watchdog' );

			$isrunning = false;
			if ( stripos($job, "media") !== false ) $isrunning = $isrunning_mediaconv;
			if ( stripos($job, "content") !== false ) $isrunning = $isrunning_contentconv;
			if ( ( ( time() - $time ) >= $job_info['watchdogtimeoutsecs'] ) && ( $isrunning === false ) ) {
				$msg  = "Job " . $job . ".php is stalled.\n\nDetailed information:\n\n";
				$msg .= $body . "\n\n";
				// Log: log to file using no DB, then send mail
				$debug->log($jconf['log_dir'], $jconf['jobid_watcher'] . ".log", $msg, $sendmail = false);
				sendHTMLEmail_errorWrapper('[' . $app->bootstrap->config['siteid'] . ' log]: ' . substr( $msg, 0, 100 ), $msg, false);
			}

			break;
		default:
			// Running in more than 1 instances: undefined (send alert)
			$msg  = "Job " . $job . ".php is running in more instances.\n\nDetailed information:\n\n";
			$msg .= $body . "\n\n";
			// Log: log to file using no DB, then send mail
			$debug->log($jconf['log_dir'], $jconf['jobid_watcher'] . ".log", $msg, $sendmail = false);
			sendHTMLEmail_errorWrapper('[' . $app->bootstrap->config['siteid'] . ' log]: ' . substr( $msg, 0, 100 ), $msg, false);
			break;
	}

}

$app->watchdog();

exit;

?>
