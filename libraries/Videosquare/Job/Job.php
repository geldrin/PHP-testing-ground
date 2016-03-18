<?php
namespace Videosquare\Job;

include_once( BASE_PATH . 'libraries/Springboard/Application/Job.php');

class Job extends \Springboard\Application\Job {

    // Extra config
    protected $isWindowsJob           = false;      // Running on Windows?
    protected $needsRunOverControl    = true;       // Needs to check if the process is running? (in addition to watchdog file)
    protected $watchConfigChangeExit  = true;       // If config or job file has changed then exit
    protected $removeLockOnStart      = false;      // Remove lock file on start? Be aware it can cause problems!
    
    // Class variables
    protected $job_id = null;
    protected $job_filename = null;

    protected $debug_mode = false;
    
    public function __construct() {
        
        // Call parrent's (Springboard Job class) constructor
        parent::__construct(BASE_PATH, PRODUCTION);

        // Load job configuration
        $this->loadConfig('modules/Jobs/config_jobs.php');
        
        // Job id and path
        $this->job_id = basename($_SERVER["PHP_SELF"], ".php");
        $this->job_filename = $this->bootstrap->config['jobpath'] . basename($_SERVER["PHP_SELF"]);
        
        // Debug mode
        if ( isset($this->bootstrap->config['jobs'][$this->bootstrap->config['node_role']][$this->job_id]) ) {
            $this->debug_mode = $this->bootstrap->config['jobs'][$this->bootstrap->config['node_role']][$this->job_id]['debug_mode'];
        }
        
    }

    // Springboard Job redefined preRun()
    protected function preRun() {

        if ( $this->removeLockOnStart ) $this->releaseLock();
    
        $this->checkOS();
    
        clearstatcache();
    
        $this->handleLock();
        $this->startTimestamp = time();
        
        $this->isConfigChangeOccured();
        
    }
    
    protected function checkOS() {

        $retval = true;
        
        if ( $this->isWindowsJob ) $retval = false;
    
        if ( stripos(PHP_OS, "WIN") === false ) {
            if ( $this->isWindowsJob ) throw new \Videosquare\Model\Exception('[EXCEPTION] Windows OS is required.', 100);
        } else {
            if ( !$this->isWindowsJob ) throw new \Videosquare\Model\Exception('[EXCEPTION] Linux OS is required.', 100);
        }

        return true;
    }
    
    public function isLibreOfficeRunning() {

        $command = "ps uax | grep \"^" . $this->bootstrap->config['ssh_user'] . "\" | grep \"soffice.bin\" | grep -v \"grep\"";
        exec($command, $output, $result);
        if ( isset($output[0]) ) return true;

        return false;
    }

    
    // Wrapper for debug log
    public function debugLog($msg, $sendmail = false) {

        $this->d->log($this->logDir, $this->getLogFile(), $msg, $sendmail);
        
    }
    
    public function getMyName() {
        
        return $this->job_id;
    }
    
    protected function process() {
        // Abstract method and must therefore be declared...
        return true;
    }

    // Number of processes by name (regexp)
    protected function checkProcessExists($processName) {
        exec("ps uax | grep -i '$processName' | grep -v grep", $pids);
        return count($pids);
    }

    // Check if this job already running. Most jobs should not run over each other.
    protected function runOverControl() {

        if ( $this->needsRunOverControl ) return true;
    
        $processes = $this->checkProcessExists("php.*" . $this->job_filename);
        
        if ( count($processes) <= 1 ) return true;
        
        $process_longest = 0;
        $msg = "";
    
        for ( $i = 0; $i < count($processes); $i++ ) {
            if ( $process_longest < $processes[$i][0] ) $process_longest = $processes[$i][0];
            $msg .= floor($processes[$i][0] / ( 24 * 3600 ) ) . "d " . secs2hms($processes[$i][0] % ( 24 * 3600 )) . " " . $processes[$i][1] . "\n";
        }
        
        // Do not send alarm if DB is down
        if ( !file_exists($this->bootstrap->config['dbunavailableflagpath']) ) {
            $this->d("[WARN] Job " . $this->job_id . " runover was detected. Job info (running time, process):\n" . $msg, false);
        }
            
        return false;
    }
    
    // Config changed?
    public function isConfigChangeOccured() {

        if ( !$this->watchConfigChangeExit ) return false; 
        
        // Have this job or config changed?
        if ( ( filemtime($this->job_filename) > $this->startTimestamp ) or ( filemtime(BASE_PATH . "config.php" ) > $this->startTimestamp ) or ( filemtime(BASE_PATH . "config_local.php" ) > $this->startTimestamp ) ) return true;
        
        return false;
    }

}
