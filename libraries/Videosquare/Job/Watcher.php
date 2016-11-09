<?php

namespace Videosquare\Job;

define('BASE_PATH',	realpath(__DIR__ .'/../../..') . '/');
define('PRODUCTION', false);
define('DEBUG', false);

include_once(BASE_PATH . 'libraries/Springboard/Application/Cli.php');
include_once('../Modules/RunExt.php');

//-----------------------------------------------

die( Watcher::process() );

//-----------------------------------------------

/**
 * Description of Watcher
 *
 * @author glukacsy
 */
class Watcher {
  private static $app, $db, $ldir, $lfile, $jconf, $debug,  $debug_mode;
  private static $initialized = false;
  private static $panicfile  = null;
  private static $currentSleepSeconds = 60;
  private static $lookupdirs = []; // folder list in which the actual job files can be found.
  private static $jobpaths   = []; // list of job files
  // Job specific variables
  private static $msg              = null;
  private static $mailbody         = null;
  private static $jobs_start_queue = [];
  private static $jobs_stop_queue  = [];
  private static $jobs_duplicated  = [];
  private static $jobs_timeout     = [];
  
  //---------------------------------------------
  
  private function __construct() { /* Constructor disabled */ }
  
  private function __clone() { /* Cloning disabled */ }
  
  /**
   * 
   * @throws \Exception
   */
  public static function process() {
    self::init();
    
    if (array_key_exists('watcherdebug', self::$app->config)) {
      self::$debug_mode = (bool) self::$app->config['watcherdebug'];
    }
    
    if (self::$debug_mode) { self::log(str_pad(' STARTING WATCHER ', 50, '-', STR_PAD_BOTH)); }
    // do stuff
    try {
      self::$mailbody = "--- Watcher report ---";

      self::watchTower();

      if (self::$msg && self::$debug_mode) {
        self::log(self::$msg);
        self::$mailbody .= PHP_EOL . self::$msg;
        self::$msg = null;
      }

      // process start queue
      if (!empty(self::$jobs_start_queue)) {
        $msg = sprintf("\nSome job(s) needs restart: %s", implode(', ', self::$jobs_start_queue));
        self::$mailbody .= $msg . PHP_EOL;

        if (self::$debug_mode) { self::debugLog($msg); }

        foreach (self::$jobs_start_queue as $job2start) {
          self::$mailbody .= "\n > Starting job '$job2start'";
          $result = self::startJob($job2start);

          if ($result === false) {
            self::log(self::$msg);
            self::$mailbody .= self::$msg . PHP_EOL . PHP_EOL;
          } elseif (self::$debug_mode) {
            self::log(self::$msg);
          }

          self::$msg = null;
        }
      }

      // process stop queue
      if (!empty(self::$jobs_stop_queue)) {
        $msg = sprintf("The following jobs are marked to stop: %s", implode(', ', self::$jobs_stop_queue));
        self::$mailbody .= $msg . PHP_EOL . PHP_EOL;

        if (self::$debug_mode) { self::log($msg); }
      }

      // process duplicates
      if (!empty(self::$jobs_duplicated)) {
        $msg = sprintf("[WARNING] Duplicate job(s) found: %s", implode(', ', self::$jobs_duplicated));
        self::$mailbody .= $msg . PHP_EOL . PHP_EOL;
        self::log($msg);
      }

      // process jobs with timeout
      if (!empty(self::$jobs_timeout)) {
        $msg  = sprintf("[WARNING] Watchdog timeout detected: %s", implode(', ', array_column(self::$jobs_timeout, 'job')));

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
        self::log("[EXCEPTION] " . $err->getMessage(), false);
      }
    }
    
    if (self::$debug_mode) { self::log("Processing finished."); }
    
    // We did not do anything, sleep longer and longer. Reset sleep seconds only if $needSleep = false;
//    self::$needsSleep = true;
//    if ( !self::$needsSleep ) { self::$currentSleepSeconds = 1; }
    if ( self::$debug_mode ) { self::log("[INFO] Will sleep: " . self::$currentSleepSeconds); }
    
//    $this->updateLock();
//    $this->handlePanic();s
  }
  
  /**
   * 
   */
  private static function watchTower() {
    self::$jobs_duplicated  = [];
    self::$jobs_start_queue = [];
    self::$jobs_stop_queue  = [];
    self::$jobs_timeout     = [];
    self::$msg = '';

    $msg  = null;
    $jobs = self::$app->config['jobs'][self::$app->config['node_role']];

    $jobs_running = [];
    $jobs_running = self::getRunningJobs();
    
    if (count($jobs_running) >= 1) {
      $msg .= sprintf("Currently running job(s): %s\n", implode('\n', $jobs_running));
    } else {
      $msg .= "There are no running jobs currently.\n";
    }
    
    if (self::$debug_mode) { self::log($msg); }
    
    self::$msg = $msg;
    unset($msg);
    
    foreach ($jobs as $job_name => $job_data) {
      $timeout      = false;
      $timeout_diff = null;
      $last_mod     = null;

      $running_instances = self::getRunnningProcessCount($job_name, $jobs_running);
      $stop = file_exists(self::$panicfile);

      if ($running_instances < 1) {
        // job not running
        if ($job_data['enabled']) { self::$jobs_start_queue[] = "{$job_name}.php"; }

      } elseif ($running_instances == 1) {
        // job running
        // check watchdog state
        $dog = BASE_PATH ."data/watchdog/{$job_name}.php.watchdog";

        if (file_exists($dog)) {
          $last_mod = filemtime($dog);
          $timeout_diff = time() - $last_mod;

          if ($timeout_diff >= $job_data['watchdogtimeoutsecs']) { $timeout = true; }

        } else {
          self::$msg .= "Can't locate watchdog file for {$job_name} ('{$dog}').\n";
        }

        if (!$job_data['enabled'] || $stop) {
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
        self::$jobs_duplicated[] = "{$job_name}.php";

      }
    }
  }
  
  /**
   * 
   * @param type $job
   * @return boolean
   */
  private static function startJob($job) {
    $msg = null;
    
    $command = "nohup /usr/bin/php -f ". self::$app->config['libpath'] ."Videosquare/Job/{$job}";
    if (self::$debug_mode === false) { $command .= " &>/dev/null"; }
    $command .= " &";
    
    $job = new RunExt($command);
    if ($job->run() === false) {
      $msg .= "> FAILED!\n";
      $msg .= "return code: {$job->getCode()}\n";
      $msg .= "command: {$job->command}\n";
      $msg .= "outout: {$job->getOutput()}\n";
    } else {
      $msg .= "> done.\n";
    }
    
    $msg .= sprintf("duration: %.02fs\n", $job->getDuration());
    self::$msg = $msg;
    unset($msg);
    var_dump($job); //debug
    if ($job->getCode() != 0) { return false; }
    return true;
    
//    $time = microtime(1);
//    exec($command, $output, $result);
//    $time = microtime(1) - $time;
//    $output = implode(PHP_EOL, $output);
//
//    $msg  = "Restarting job: '". $job ."'\n";
//    if ($result != 0) {
//      $msg .= "> FAILED!\n";
//      $msg .= "return code: ". $result ."\n";
//      $msg .= "command: '". $command ."'\n";
//      $msg .= "output: ". $output ."\n";
//    } else {
//      $msg .= "> done.\n";
//    }
//
//    $msg .= sprintf("duration: %.02fs", $time);
//    $this->msg = $msg;
//    unset($msg, $time, $command, $output);
//
//    if ($result != 0) return false;

  }
  
  /**
   * SHITTY LEGACY CODE
   * 
   *   --- !REPLACE! ---
   * 
   * @return array/bool
   */
  private function _getRunningJobs() {
    $wrkr = new RunExt('ps uax | egrep "Job*|Watcher" | grep -v "grep"');
    
    if ($wrkr->getCode() == 0) { return $wrkr->getOutput(); }

    return null;
  }
  
  /**
   * New code
   */
  private static function getRunningJobs() {
    $pattern   = '';
    $job_paths = [];
    
    $job_paths = self::getJobPaths();
    array_walk($job_paths, function(&$path) { $path .= '[jJ]ob*'; });
    $job_paths[] = __FILE__;
    $pattern = implode('|', $job_paths);
    
    $shell = new RunExt();
    $shell->command = "ps uax | egrep \"{$pattern}\"|grep -v \"grep\"";
    
    if ($shell->run() === true) { return $shell->getOutputArr(); }
    
    return false;
  }
  
  /**
   * 
   * @param string $processname Name of the process
   * @param array $runningjobs String array with the running process list
   * @return boolean
   */
  private static function getRunnningProcessCount($processname, $runningjobs) {
//    exec("ps uax | grep -i '$processname' | grep -v grep", $pids);
//    return count($pids);
    $cnt = 0;
    if (is_array($runningjobs) && !empty($runningjobs)) {
      foreach ($runningjobs as $rj) {
        if (strpos($rj, $processname)) { $cnt++; }
      }
      return $cnt;
    }
    return -1;
  }
  
  /**
   * Returns a list of directories where job files are stored.
   * 
   * @return array
   */
  private static function getJobPaths() {
    // currently hardcoded values only (Were do we get the exact list from??)
    $jobpaths = [
//      BASE_PATH .'modules/Jobs/',
//      self::$app->config['jobpath'],
      '/home/conv/dev.videosquare.eu/modules/Jobs/', //debug
      '/home/conv/dev.videosquare.eu/libraries/Videosquare/Job/', //debug
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
   * Initializes
   * @return NULL
   */
  private static function init() {
    if (self::$initialized) { return; }
    
    self::$app = new \Springboard\Application\Cli(BASE_PATH, DEBUG);
    self::$app->loadConfig('/modules/Jobs/config_jobs.php');
    self::$debug = \SpringBoard\Debug::getInstance();
    self::$jconf = self::$app->config['config_jobs'];
    self::$ldir  = self::$jconf['log_dir'];
    self::$lfile = substr(__CLASS__, strrpos(__CLASS__ , '\\') + 1) .'.log';
    
    self::$db    = self::$app->bootstrap->getAdoDB();
    
    self::$panicfile = BASE_PATH ."data/". __FILE__ .".stop";
    
    if(!function_exists("array_column")) {
      // PHP 5.4 compatibility
      function array_column($array,$column_name) {
        return array_map(function($element) use($column_name){ return $element[$column_name]; }, $array);
      }
    }
  }
}
