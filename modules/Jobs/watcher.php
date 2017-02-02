<?php

define('BASE_PATH', realpath( __DIR__ . '/../..' ) . '/' );
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

// Exit if all.stop file exists
if ( is_file( $app->config['datapath'] . 'jobs/all.stop' ) ) exit;

// Log related init
$debug = Springboard\Debug::getInstance();

if ( iswindows() ) {
  echo "ERROR: Non-Windows process started on Windows platform\n";
  exit;
}

$kill = '';

clearstatcache();

// Watchdog
$app->watchdog();

// ## Dump OS/HW related information
// Processes
$processes = `ps uax | egrep "job_|watcher" | grep -v "grep" 2>&1`;
$command = $uptime = null;

if ( file_exists($jconf['temp_dir']) ) {
  // CPU information
  $command = "cat /proc/cpuinfo > " . $jconf['temp_dir'] . "cpuinfo";
  exec($command, $output, $result);
  // Memory information
  $command = "cat /proc/meminfo > " . $jconf['temp_dir'] . "meminfo";
  exec($command, $output, $result);
  // Uptime
  $uptime = `uptime 2>&1`;
  file_put_contents($jconf['temp_dir'] . 'uptime', $uptime);
  // Processes
  file_put_contents($jconf['temp_dir'] . 'processids', $processes);
}

// Jobs configuration
$jobs = $app->config['jobs'];
$node_role = $app->config['node_role'];

// Send alert of stopped jobs between 06:00:00 - 06:04:59
$date = date("Y-m-d");
$alert_time_start = strtotime($date . " " . "06:00:00");
$alert_time_end = strtotime($date . " " . "06:04:59");
$now_time = time();

// Mail message header
$head  = "SITE: " . $app->config['baseuri'] . "\n";
$head .= "NODE: " . $app->config['node_sourceip'] . "\n";
$head .= "NODE ROLE: " . $app->config['node_role'] . "\n";
$head .= "NODE UPTIME: " . $uptime;
$head .= "WATCHER PROCESSID: " . PROCESSID . "\n";
$head .= "TIME: " . date("Y-m-d G:i:s");
$body = "";

// ## Check if all.stop file is present blocking the jobs
$stop_file = $app->config['datapath'] . 'jobs/all.stop';
if ( file_exists($stop_file) ) {
  $msg = "WARNING: jobs are not running. See stop file:\n\n" . $stop_file . "\n\nRemove it to start all jobs. This message is sent once every day.";
  // Send mail once a day to warn admin
  if ( ( $now_time > $alert_time_start ) and ( $now_time < $alert_time_end ) ) {
    // Log: log to file using no DB, then send mail
    $debug->log($jconf['log_dir'], $jconf['jobid_watcher'] . ".log", $head . "\n\n" . $msg, $sendmail = false);
    sendHTMLEmail_errorWrapper('Watcher report [' . $app->bootstrap->config['siteid'] . ']: ' . substr($msg, 0, 100), $head . "\n\n" . $msg, false);
  }
  exit;
}

// ## Check job stop files one by one and report if any exists
$jobs_isstopped = false;
$jobs_stopped = "";
$jobs_toskip = array();

foreach ( $jobs[$node_role] as $job => $job_info ) {
  // Is job enabled?
  if ( !$job_info['enabled'] ) { continue; }
  // Check if any stop file is present
  $jobs_toskip[$job] = false;
  $stop_file = $app->config['datapath'] . 'jobs/' . $job . '.stop';
  if ( file_exists($stop_file) ) {
    $jobs_isstopped = true;
    if ( $job_info['supresswarnings'] == false ) { $jobs_stopped .= $job . ".php: " . $stop_file . "\n"; }
    $jobs_toskip[$job] = true;
  }
}

// Report jobs with enabled .stop file
if ( $jobs_isstopped && !empty($jobs_stopped) && ( $now_time > $alert_time_start ) && ( $now_time < $alert_time_end ) ) {
  $body .= "WARNING: some jobs may not be running. See stop file(s):\n\n" . $jobs_stopped . "\nRemove them to restart jobs. This message is sent once a day.\n\n";
}

// ## Is any conversion running? (ffmpeg check)
$isrunning_mediaconv = false;
$output = array();
$check_string = $jconf['media_dir'];
$cmd = 'ps uax | grep "ffmpeg.*' . $check_string . '" | grep -v "grep" | wc -l 2>&1';
exec($cmd, $output, $result);

if ( isset($output[0]) && $output[0] > 0 ) { $isrunning_mediaconv = true; }

// ## List and log all running jobs
$grep_target = "php.*" . $jconf['job_dir'];
$processes = `ps uax | egrep "$grep_target(job_|watcher)" | grep -v "grep" 2>&1`;

if ( file_exists($jconf['temp_dir']) ) { file_put_contents($jconf['temp_dir'] . 'processids', $processes); }
if ( empty($processes) ) { $processes = "-"; }

foreach ( $jobs[$node_role] as $job => $job_info ) {
  // Is job enabled?
  if ( !$job_info['enabled'] ) { continue; }

  // Is there stop file for this job?
  if ( $jobs_toskip[$job] == true ) { continue; }

  $output = array();

  // Check if this job is running
  $num_jobs = checkProcessExists($grep_target . $job . ".php");

  switch ( $num_jobs ) {
    case '0':
      // Problem: sometime ps does not give result while the process is running. Maybe some strange transient is occured? As a workaround, let's wait for some time and double check if process is running.
      sleep(1);
      $num_jobs_recheck = checkProcessExists($grep_target . $job . ".php");
      $isrunningjob = "NOT RUNNING";

      if ( $num_jobs_recheck > 0 ) {
        $isrunningjob = "RUNNING (" . $num_jobs_recheck . ")";
        $debug->log($jconf['log_dir'], $jconf['jobid_watcher'] . ".log", "[INFO] " . $job . ".php RECHECK status: " . $isrunningjob, $sendmail = false);
        break;
      }

      $cmd_start  = microtime(true);
      $command = "/usr/bin/php -f " . $jconf['job_dir'] . $job . ".php > /dev/null &";
      exec($command, $output, $result);
      $cmd_end = microtime(true);
      $output_string = implode("\n", $output);

      $body .= $job . ".php: NOT RUNNING\n";
      $body .= "\tCOMMAND: " . $command . "\n";
      
      if ( $result != 0 ) {
        $body .= "\tRESULT: " . $result . "\n";
        $body .= "\tOUTPUT: " . $output_string . "\n";
        $body .= "\tREINIT STATUS: FAILED. CHECK!\n";
      } else {
        $body .= "\tREINIT STATUS: OK\n";
      }
      
      $cmd_duration = round( ($cmd_end - $cmd_start) / 1000000, 2);
      $body .= "\tDURATION: " . $cmd_duration . "sec\n\n";

      break;

    case '1':
      // Running: check watchdog time difference (if larger and ffmpeg is not running...)
      $time = @filemtime( $app->config['datapath'] . 'watchdog/' . $job . '.php.watchdog' );
      $isrunning = false;

      if ( stripos($job, "media") !== false ) { $isrunning = $isrunning_mediaconv; }

      $time_running = time() - $time;

      if ( ( $time_running >= $job_info['watchdogtimeoutsecs'] ) && ( $isrunning === false ) ) {
        $body .= $job . ".php: STALLED\n";
        $body .= "\tWATCHDOG TIMEOUT: " . seconds2DaysHoursMinsSecs($time_running) . "\n\n";
      }

      break;

    default:
      // Running in more than 1 instances: undefined (send alert)
      $body .= $job . ".php: MULTIPLE INSTANCE\n";
      $body .= "\tWATCHDOG TIMEOUT: " . seconds2DaysHoursMinsSecs($time_running) . "\n\n";
      break;
  }
}

// Log if any error occured, send HTML e-mail
if ( !empty($body) ) {
  $debug->log($jconf['log_dir'], $jconf['jobid_watcher'] . ".log", $head . "\n\n" . $body, $sendmail = false);
  $debug->log($jconf['log_dir'], $jconf['jobid_watcher'] . ".log", "[INFO] All running jobs:\n" . $processes, $sendmail = false);
  sendHTMLEmail_errorWrapper('Watcher report [' . $app->bootstrap->config['siteid'] . ']: ' . substr($body, 0, 100), $head . "\n\n" . $body, false);
}

// Watchdog
$app->watchdog();

exit;
