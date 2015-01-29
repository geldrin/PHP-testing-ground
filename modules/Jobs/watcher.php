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

$debug->log($jconf['log_dir'], $jconf['jobid_watcher'] . ".log", "********************* Job: " . $jconf['jobid_watcher'] . " started *********************", $sendmail = false);

$app->watchdog();

$command = "cat /proc/cpuinfo > " . $jconf['temp_dir'] . "cpuinfo";
exec($command, $output, $result);
$command = "cat /proc/meminfo > " . $jconf['temp_dir'] . "meminfo";
exec($command, $output, $result);

$uptime = `uptime 2>&1`;
file_put_contents($jconf['temp_dir'] . 'uptime', $uptime);

$processes = `ps uax | egrep "job_|watcher" | grep -v "grep" 2>&1`;
file_put_contents($jconf['temp_dir'] . 'processids', $processes);

$node_role = $app->config['node_role'];

// Jobs configuration
$jobs = $app->config['jobs'];

// Send alert of stopped jobs between 06:00:00 - 06:04:59
$date = date("Y-m-d");
$alert_time_start = strtotime($date . " " . "06:00:00");
$alert_time_end = strtotime($date . " " . "06:04:59");
$now_time = time();

// Check if all.stop file is present blocking all the jobs
$stop_file = $app->config['datapath'] . 'jobs/all.stop';
if ( file_exists($stop_file) ) {
	$msg = "WARNING: jobs are not running. See stop file:\n\n" . $stop_file . "\n\nRemove it to start all jobs. This message is sent once every day.";
	// Send mail once a day to warn admin
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
foreach ( $jobs[$node_role] as $job => $job_info ) {

	// Is job enabled?
	if ( !$job_info['enabled'] ) continue;

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
	$msg = "WARNING: some jobs may not running. See stop file(s):\n\n" . $jobs_stopped . "\nRemove them to restart jobs. This message is sent once a day.";
	if ( ( $now_time > $alert_time_start ) and ( $now_time < $alert_time_end ) ) {
		// Log: log to file using no DB, then send mail
		$debug->log($jconf['log_dir'], $jconf['jobid_watcher'] . ".log", $msg, $sendmail = false);
		sendHTMLEmail_errorWrapper('[' . $app->bootstrap->config['siteid'] . ' log]: ' . substr($msg, 0, 100), $msg, false);
	}
}

//// Is any conversion running?
$isrunning_mediaconv = false;
// Media conversion/ffmpeg: check if running
$output = array();
$check_string = $jconf['media_dir'];
$cmd = 'ps uax | grep "ffmpeg.*' . $check_string . '" | grep -v "grep" | wc -l 2>&1';
exec($cmd, $output, $result);
if ( isset($output[0]) ) if ( $output[0] > 0 ) $isrunning_mediaconv = true;

// List and log all running jobs
$grep_target = "php.*" . $jconf['job_dir'];
$processes = `ps uax | egrep "$grep_target(job_|watcher)" | grep -v "grep" 2>&1`;
file_put_contents($jconf['temp_dir'] . 'processids', $processes);
$debug->log($jconf['log_dir'], $jconf['jobid_watcher'] . ".log", "[INFO] All running jobs:\n" . $processes, $sendmail = false);

foreach ( $jobs[$node_role] as $job => $job_info ) {

	// Is job enabled?
	if ( !$job_info['enabled'] ) continue;

	// Is there stop file for this job?
	if ( $jobs_toskip[$job] == true ) continue;

	$output = array();

	// Check if this job is running
    $num_jobs = checkProcessExists($grep_target . $job . ".php");
    $isrunningjob = "NOT RUNNING";
    if ( $num_jobs > 0 ) $isrunningjob = "RUNNING (" . $num_jobs . ")";
    $debug->log($jconf['log_dir'], $jconf['jobid_watcher'] . ".log", "[INFO] " . $job . ".php status: " . $isrunningjob, $sendmail = false);
    
	$body  = "";
	$body .= "SITE: " . $app->config['baseuri'] . "\n";
	$body .= "NODE: " . $app->config['node_sourceip'] . "\n";
	$body .= "NODE UPTIME: " . $uptime . "\n";
	$body .= "JOB: " . $job . ".php\n";
	$body .= "WATCHER PROCESSID: " . PROCESSID . "\n";
	$body .= "TIME: " . date("Y-m-d G:i:s.u");

	switch ( $num_jobs ) {
		case '0':

			// Problem: sometime ps does not give result while the process is running. Maybe some strange transient is occured? As a workaround, let's wait for some time and double check if process is running.
			sleep(1);
            $num_jobs_recheck = checkProcessExists($grep_target . $job . ".php");
            $isrunningjob = "NOT RUNNING";
            if ( $num_jobs_recheck > 0 ) {
                $isrunningjob = "RUNNING (" . $num_jobs_recheck . ")";
                $debug->log($jconf['log_dir'], $jconf['jobid_watcher'] . ".log", "[INFO] " . $job . ".php RECHECK status: " . $isrunningjob, $sendmail = false);
                exit;
                break;
            }

            $debug->log($jconf['log_dir'], $jconf['jobid_watcher'] . ".log", "[INFO] " . $job . ".php RECHECK status: " . $isrunningjob, $sendmail = false);
            
			$cmd_start  = microtime(true);
			$command = "/usr/bin/php -f " . $jconf['job_dir'] . $job . ".php > /dev/null &";
			exec($command, $output, $result);
			$cmd_end = microtime(true);
			$output_string = implode("\n", $output);

			$msg  = "Did not find " . $job . ".php process.\n\nDetailed information:\n\n";
			$msg .= $body . "\n\n";
			$msg .= "Job reinit, information:\n";
			$msg .= "COMMAND: " . $command . "\n";
			$msg .= "OUTPUT: " . $output_string . "\n";
			$msg .= "RESULT: ";
			if ( $result != 0 ) {
				$msg .= "Cannot restart PHP script\n";
			} else {
				$msg .= "OK\n";
			}
			$msg .= "COMMAND DURATION: " . ($cmd_end - $cmd_start) . "usec\n";

			// Log: log to file using no DB, then send mail
			$debug->log($jconf['log_dir'], $jconf['jobid_watcher'] . ".log", $msg, $sendmail = false);
			sendHTMLEmail_errorWrapper('[' . $app->bootstrap->config['siteid'] . ' log]: ' . substr($msg, 0, 100), $msg, false);

			break;
		case '1':
			// Running: check watchdog time difference (if larger and ffmpeg is not running...)
			$time = @filemtime( $app->config['datapath'] . 'watchdog/' . $job . '.php.watchdog' );

			$isrunning = false;
			if ( stripos($job, "media") !== false ) $isrunning = $isrunning_mediaconv;
			if ( ( ( time() - $time ) >= $job_info['watchdogtimeoutsecs'] ) && ( $isrunning === false ) ) {
				$msg  = "Job " . $job . ".php is stalled?\n\nDetailed information:\n\n";
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
