<?php

namespace Videosquare\Job;

define('BASE_PATH',	realpath(__DIR__ .'/../../..') . '/');
define('DEBUG', false);

include_once(BASE_PATH . 'libraries/Springboard/Application/Cli.php');
include_once(BASE_PATH . 'libraries/Videosquare/Modules/RunExt.php');

//-----------------------------------------------

die( Watcher::process() );

//-----------------------------------------------

/**
 * Watcher class.
 *
 * @author glukacsy
 */
class Watcher {
  private static $app, $ldir, $lfile, $jconf, $debug,  $debug_mode;
  private static $initialized = false;
  private static $panicfile  = null;
  private static $needsSleep = false;
  private static $currentSleepSeconds = 60;
  // Job specific variables
  private static $msg              = null;
  private static $mailbody         = null;
  private static $jobs_start_queue = [];
  private static $jobs_stop_queue  = [];
  private static $jobs_duplicated  = [];
  private static $jobs_timeout     = [];
  
  //---------------------------------------------
  
  private function __construct() { /* Constructor disabled   */ }
  private function __clone()     { /* Cloning disabled       */ }
  private function __wakeup()    { /* Serialization disabled */ }
  
  /**
   * Main function.
   * Checks running, stopped or duplicated jobs, and reports them via e-mail / logging.
   * 
   * It also attempts to (re)start stopped jobs.
   * 
   * @throws \Exception
   */
  public static function process() {
    self::init();
    
    if (self::$debug_mode) { self::log(str_pad(' STARTING WATCHER ', 50, '-', STR_PAD_BOTH)); }
    // do stuff
    try {
      self::$mailbody = "--- Watcher report ---" . PHP_EOL;

      self::watchTower();

      if (self::$msg && self::$debug_mode) {
        self::log(self::$msg);
        self::$mailbody .= PHP_EOL . self::$msg;
        self::$msg = null;
      }

      // process start queue
      if (!empty(self::$jobs_start_queue)) {
        $msg = sprintf("Some job(s) needs restart: %s", implode(', ', array_column(self::$jobs_start_queue, 'name')));
        self::$mailbody .= $msg . PHP_EOL;

        if (self::$debug_mode) { self::log($msg); }

        foreach (self::$jobs_start_queue as $job2start) {
          $result = self::startJob($job2start);
          
          self::$mailbody .= $job2start['message'] . PHP_EOL;
          
          if ($result === false) {
            self::log($job2start['message']);
          } elseif (self::$debug_mode) {
            self::log($job2start['message']);
          }
        }
        self::$mailbody .= PHP_EOL;
      }

      // process stop queue
      if (!empty(self::$jobs_stop_queue)) {
        $msg = sprintf("The following jobs are marked to stop: %s", implode(', ', self::$jobs_stop_queue));
        self::$mailbody .= $msg . PHP_EOL . PHP_EOL;
        
        if (self::$debug_mode) { self::log($msg); }
      }

      // process duplicates
      if (!empty(self::$jobs_duplicated)) {
        $msg = "[WARNING] Duplicated job(s) detected!\n";
        
        foreach (self::$jobs_duplicated as $name => $dupe) {
          $msg .= sprintf(" > Job: %s | running instances:\n", $name);
          foreach ($dupe as $d) {
            $msg .= sprintf(" - PID: %d | user: %s | %s\n", $d['pid'], $d['user'], $d['cmd']);
          }
        }
        self::$mailbody .= $msg . PHP_EOL . PHP_EOL;
        self::log($msg);
      }

      // process jobs with timeout
      if (!empty(self::$jobs_timeout)) {
        $msg = sprintf("[WARNING] Watchdog timeout detected: %s", implode(', ', array_column(self::$jobs_timeout, 'job')));

        foreach (self::$jobs_timeout as $dead) {
          $msg .= sprintf("\nJob: %s| Lastmod: %s | timeout: %.1f mins", $dead['job'], date('Y-m-d H:i:s', $dead['lastmod']), ($dead['timeout'] / 60));
        }

        self::$mailbody .= $msg . PHP_EOL . PHP_EOL;
        self::log($msg);
      }

      if (!empty(self::$jobs_start_queue) || !empty(self::$jobs_stop_queue) || !empty(self::$jobs_duplicated) || !empty(self::$jobs_timeout)) {
        self::log(self::$mailbody, true);
        // clear message body
        self::$mailbody = null;
      }

    } catch ( \Exception $err ) {
      if ( $err->getCode() == 100 ) {
        self::log("[EXCEPTION] Fatal Error (100) is detected. Stop processing.", false);
        throw $err;
      } else {
        self::log("[EXCEPTION] ". $err->getMessage(), false);
      }
    }
    
    if (self::$debug_mode) { self::log("Processing finished."); }
    
    // We did not do anything, sleep longer and longer. Reset sleep seconds only if $needSleep = false;
    if ( !self::$needsSleep ) {
      if ( self::$debug_mode ) { self::log("[INFO] Will sleep: ". self::$currentSleepSeconds); }
      sleep(self::$currentSleepSeconds);
    }
  }
  
  /**
   * Performs a scan across configured jobs and 
   */
  private static function watchTower() {
    self::$jobs_duplicated  = [];
    self::$jobs_start_queue = [];
    self::$jobs_stop_queue  = [];
    self::$jobs_timeout     = [];
    self::$msg = '';

    $msg  = null;
    $jobs = [];
    $jobs_running = [];
    
    $jobs = self::getJobConfig();
    $jobs_running = self::getRunningJobs();
    
    if ($jobs_running !== false && count($jobs_running) >= 1) {
      $msg .= sprintf("Currently running process(es):\n%s", implode("\n", array_map(function($j) { return(" > CMD: {$j['cmd']} / PID={$j['pid']}"); }, $jobs_running)));
    } else {
      $msg .= "There are no running jobs currently.\n";
    }
    
    self::$msg = $msg . PHP_EOL;
    unset($msg);
    
    foreach ($jobs as $job_name => $job_data) {
      $timeout      = false;
      $timeout_diff = null;
      $last_mod     = null;

      $running_instances = self::getRunnningProcessCount($job_name, $jobs_running);
      
      //echo "Running instances of $job_name: $running_instances\n";
      //echo "Job data: ". var_export($job_data, 1) ."\n";
      
      if ($running_instances < 1) {
        // job not running
        if (!self::isCronJob($job_data) && $job_data['enabled'] && !self::hasStopFile($job_name, BASE_PATH .'data/jobs/')) {
          self::$jobs_start_queue[] = [
            'name' => $job_name,
            'path' => $job_data['path'],
          ];
        }
      } elseif ($running_instances == 1) {
        // job running
        $dog = BASE_PATH ."data/watchdog/{$job_name}.php.watchdog"; // check watchdog state

        if (file_exists($dog)) {
          $last_mod = filemtime($dog);
          $timeout_diff = time() - $last_mod;
          
          if ($timeout_diff >= $job_data['watchdogtimeoutsecs']) { $timeout = true; }
          
        } else {
          self::log("[WARNING] Can't locate watchdog file for {$job_name} ('{$dog}').\n");
        }
        
        if (!self::isCronJob($job_data) && (!$job_data['enabled'] || self::hasStopFile($job_name, BASE_PATH .'data/jobs/'))) {
          self::$jobs_stop_queue[] = "{$job_name}.php";
        } elseif ($timeout) {
          self::$jobs_timeout[] = [
            'job'     => "{$job_name}.php",
            'lastmod' => $last_mod,
            'timeout' => $timeout_diff,
          ];
        }

        unset($dog);

      } elseif ($running_instances > 1) {
        // job is duplicated
        $dupes = self::getDuplicates($job_name, $jobs_running);
        if (!empty($dupes)) {
          self::$jobs_duplicated[$job_name] = $dupes;
        } else {
          // err msg
        }
      }
    }
  }
  
  /**
   * Starts a given job.
   * It writes back a small log message in the supplied input array with the results.
   * 
   * @param array $job Job config array
   * @return boolean
   */
  private static function startJob(&$job) {
    $msg = null;
    
    $command = "/usr/bin/php -f {$job['path']} &";
    $msg = "Starting {$job['name']}... ";
    
    $j = new RunExt($command);
    $success = $j->runDetached();
    usleep(500000);

    if ($success && self::isStringOccursInUnixProcessList($command)) {
      // A little bit more sophisticated method would be great than the currently used "grepping ps output"
      $msg .= "> DONE!";
    } else {
      $msg .= "> FAILED!";
    }
    
    if (!$success || self::$debug_mode) {
      $msg .= sprintf(
        "\n - return code: %d\n - command: '%s'". (!empty($j->getOutput()) ? "\n - output: '%s'" : null),
        $j->getCode(),
        $j->command,
        $j->getOutput()
      );
    }
    
    $job['message'] = $msg;
    unset($msg);

    if ($j->getCode() != 0) { return false; }
    return true;
  }
  
  /**
   * Check if given string occurs in the output of "ps uax" listing.
   * 
   * @param string $str String to be searched.
   * @param array $result If result param is supplemented, the function will put the RunExt object into it.
   * @return boolean
   */
  private static function isStringOccursInUnixProcessList($str, &$result = null) {
    $chk = new RunExt('ps uax | grep "'. $str .'"');
    $run = $chk->run();
    
    if (is_array($result)) { $result = $chk; }
    if ($run) { return count(explode("\n", $chk->getOutput())) > 1; }
    
    return false;
  }
  
  /**
   * Returns an array with currently running jobs.
   * The function identifies processes by their absolute path and it cannot differenciate other jobs
   * started with relative path!
   * 
   * @return boolean|array
   */
  private static function getRunningJobs() {
    $pattern   = '';
    $job_paths = [];
    $jobs      = [];
    
    $job_paths = self::getJobPaths();
    
    array_walk($job_paths, function(&$path) {
      $path = preg_quote($path, DIRECTORY_SEPARATOR);
      $path .= '[jJ]ob';
    });
    
    $job_paths[] = preg_quote(__FILE__, DIRECTORY_SEPARATOR);
    $pattern = implode('|', $job_paths);
    $pattern = "/{$pattern}/";
    
    $shell = new RunExt();
    $shell->command = 'ps uax | grep -v "grep"';
    
    if ($shell->run() === true) {
      $data = self::parseUnixProcessList(implode(null, $shell->getOutputArr()));
      
      if ($data === false) { return false; }

      $col = preg_grep($pattern, array_column($data, 'cmd'));
      array_walk($col, function ($item, $key) use($data, &$jobs) { $jobs[] = $data[$key]; });
      
      return $jobs;
    } else {
      self::log($msg = "Failed to query process list!");
      trigger_error($msg, E_NOTICE);
      return false;
    }
  }
  
  /**
   * Merges legacy and new job config array ($app->config), and also inserts paths for the jobs.
   * Only jobs conforming to the server's node role are returned this way.
   * 
   * @return array The merged config array.
   */
  private static function getJobConfig() {
    $jobs = null;
    
    $jobpaths    = self::getJobPaths();
    $job_config = [
      'default' => self::$app->config['library_jobs'][self::$app->config['node_role']],
      'legacy'  => self::$app->config['jobs'][self::$app->config['node_role']],
    ];
    
    foreach ($job_config as $type => $config) {
      // insert paths into legacy and default jobs
      if (!empty($config)) {
        array_walk(
          $job_config[$type],
          function(&$j, $name) use($jobpaths, $type) {
            $j['path'] = "{$jobpaths[$type]}{$name}.php";
          }
        );
      }
    }
    
    $jobs = array_merge($job_config['default'], $job_config['legacy']);
    return $jobs;
  }
  
  /**
   * Check if there's any stop-files present for the given job.
   * 
   * @param string $job the name of the job
   * @param string $path the folder in which the the check should be performed
   * @return boolean TRUE if stopped, FALSE otherwise
   */
  private static function hasStopFile($job, $path) {
    $path = pathinfo($path, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR;
    if (file_exists("{$path}all.stop") || file_exists("{$path}{$job}.stop")) { return true; }
    
    return false;
  }
  
  /**
   * Checks 'iscronjob' parameter in config data.
   * 
   * @param array $jobdata sub-element of the array returned by Watcher::getJobConfig()
   * @param string $jobname Name of the job (matching with the array keys of (jobs/library_jobs sub-array in config.php)
   * @return boolean
   */
  private static function isCronJob($jobdata) {
    if (array_key_exists('cronjob', $jobdata)) {
      if ($jobdata['cronjob'] == true ) { return true; }
    }
    
    return false;
  }
  
  /**
   * Parses the output of a 'ps aux' command into an associative array.
   * Retured elements contain username, PID and command.
   * 
   * @param array|string $data array or string containing the complete, uncut console output of 'ps uax' command line
   * @return array|boolean
   */
  private static function parseUnixProcessList($data) {
    $parsed = [];
    
    if (empty($data)) { return false; }
    if (is_string($data)) { $data = explode("\n", trim($data)); }
    
    $re = '/[\S]+/';
    $columns = null;
    $header  = reset($data);
    $data = array_slice($data, 1);
    
    preg_match_all($re, $header, $columns);
    
    $column_no_command = array_search('COMMAND', $columns[0]);
    $column_no_user    = array_search('USER',    $columns[0]);
    $column_no_pid     = array_search('PID',     $columns[0]);
    
    foreach($data as $row) {
      $parts = [];
      
      if (preg_match_all($re, $row, $parts) == false) { continue; }
      
      $parsed[] = [
        'user' => $parts[0][$column_no_user],
        'pid'  => $parts[0][$column_no_pid],
        'cmd'  => implode(' ', array_slice($parts[0], $column_no_command)),
        //'name' => pathinfo(end($parts[0], PATHINFO_FILENAME)), // unreliable, a single command line argument can make it fail
      ];
    }
    
    return $parsed;
  }
  
  /**
   * Counts how many processes are running with the same name.
   * 
   * @param string $processname Name of the process
   * @param array $runningjobs String array with the running process list
   * @return int number of instances or -1 on error
   */
  private static function getRunnningProcessCount($processname, $runningjobs) {
    $cnt = 0;
    if (is_array($runningjobs) && !empty($runningjobs)) {
      foreach ($runningjobs as $rj) {
        if (strpos($rj['cmd'], $processname)) { $cnt++; }
      }
      return $cnt;
    }
    return -1;
  }
  
  /**
   * Provides a list of processids with the same process name.
   * 
   * @param string $processname Name of the process
   * @param array $runningjobs An array returned by getRunningJobs()
   * @return array
   */
  private static function getDuplicatedPIDs($processname, $runningjobs) {
    $pids = [];
    if (is_array($runningjobs) && !empty($processname)) {
      foreach ($runningjobs as $id => $rj) {
        if (strrpos($rj['cmd'], $processname)) { $pids[$id] = $rj['pid']; }
      }
    }
    
    return $pids;
  }
  
  /**
   * Provides a list of duplicated processes.
   * 
   * @param string $processname
   * @param array $runningjobs
   * @return array
   */
  private static function getDuplicates($processname, $runningjobs) {
    $duplicates = [];
    if (is_array($runningjobs) && !empty($processname)) {
      foreach ($runningjobs as $id => $rj) {
        if (strrpos($rj['cmd'], $processname)) { $duplicates[$id] = $rj; }
      }
    }
    
    return $duplicates;
  }

  /**
   * Returns a list of directories where job files are stored.
   * 
   * @return array
   */
  private static function getJobPaths() {
    // currently hardcoded values only (Were do we get the exact list from??)
    $jobpaths = [
      'default' => self::$app->config['jobpath'],
      'legacy'  => self::$app->config['modulepath'] .'Jobs/',
    ];
    
    return $jobpaths;
  }

  /**
   * Wrapper for SpringBoard logger for easier use.
   * 
   * @param string $message The message string to logged.
   * @param bool $sendmail TRUE to send a notification in email (default is FALSE)
   * @return NULL
   */
  private static function log($message, $sendmail = false) {
    $sendmail = (bool) $sendmail;
    if (!$message) { return; }
    self::$debug->log(self::$ldir, self::$lfile, $message, $sendmail);
  }
  
  /**
   * Initializes Springboard components, such as app and debug objects.
   * 
   * @return NULL
   */
  private static function init() {
    if (self::$initialized) { return; }
    
    self::$needsSleep = true; //debug
    self::$app = new \Springboard\Application\Cli(BASE_PATH, false);
    self::$app->loadConfig('modules/Jobs/config_jobs.php');
    self::$debug = \SpringBoard\Debug::getInstance();
    self::$jconf = self::$app->config['config_jobs'];
    self::$ldir  = self::$jconf['log_dir'];
    self::$lfile = substr(__CLASS__, strrpos(__CLASS__ , '\\') + 1) .'.log';
    
    self::$panicfile = BASE_PATH ."data/". pathinfo(__FILE__, PATHINFO_FILENAME) .".stop";
    if (array_key_exists('watcherdebug', self::$app->config)) {
      self::$debug_mode = (bool) self::$app->config['watcherdebug'];
    }
    
    if(!function_exists("array_column")) {
      // PHP 5.4 compatibility
      function array_column($array,$column_name) {
        return array_map(function($element) use($column_name) { return $element[$column_name]; }, $array);
      }
    }
  }
}
