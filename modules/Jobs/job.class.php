<?php

include_once('ssh.class.php');

class Job {
    public $app;
    public $debug;

    public $config = null;
    public $config_jobs = null;
    public $timejobstarted = null;
    public $jobid = null;
    public $jobpath = null;
   
    public $debug_mode = false;
    public $node_role = null;
    
    // Init Job class: connect to DB, get debug class, load configuration, set init values
    public function __construct() {
    
        // Start time
        $this->timejobstarted = time();

        // Init Videosquare application
        $this->app = new Springboard\Application\Cli(BASE_PATH, false);

        // Debug class
        $this->debug = Springboard\Debug::getInstance();        

        // Load configuration
        $this->app->loadConfig('modules/Jobs/config_jobs.php');
        $this->config = $this->app->config;
        $this->config_jobs = $this->app->config['config_jobs'];
        
        // Job ID
        $job_file = JOB_FILE;
        if ( empty($job_file) ) return false;
        $pathinfo = pathinfo($job_file);
        $this->jobid = $pathinfo['filename'];
        
        // Job script path
        $this->jobpath = $this->config_jobs['job_dir'] . $this->jobid . ".php";
        
        // Debug mode
        if ( isset($this->config['jobs'][$this->config['node_role']][$this->jobid]) ) {
            $this->debug_mode = $this->config['jobs'][$this->config['node_role']][$this->jobid]['debug_mode'];
        }
        
        return true;
    }

    // Config changed?
    public function configChangeOccured() {

        // Have this job or config changed?
        if ( ( filemtime($this->jobpath) > $this->timejobstarted ) or ( filemtime(BASE_PATH . "config.php" ) > $this->timejobstarted ) or ( filemtime(BASE_PATH . "config_local.php" ) > $this->timejobstarted ) ) return true;
        
        return false;
    }
    
    // Check if this job already running. Most jobs should not run over each other.
    public function runOverControl() {

        $goahead = true;

        $processes = checkProcessStartTime("php.*" . $this->config_jobs['job_dir'] . $this->jobid . ".php");
        if ( count($processes) > 1 ) {
        
            $process_longest = 0;
            $msg = "";
        
            for ( $i = 0; $i < count($processes); $i++ ) {
                if ( $process_longest < $processes[$i][0] ) $process_longest = $processes[$i][0];
                $msg .= floor($processes[$i][0] / ( 24 * 3600 ) ) . "d " . secs2hms($processes[$i][0] % ( 24 * 3600 )) . " " . $processes[$i][1] . "\n";
            }
            
            // Do not send alarm if DB is down
            if ( !file_exists($this->config['dbunavailableflagpath']) ) {
                $this->debugLog("[WARN] Job " . $this->jobid . " runover was detected. Job info (running time, process):\n" . $msg, false);
            }
            
            $goahead = false;
        }

        return $goahead;
    }

    // Log debug message for this specific job's log file
    public function debugLog($msg, $sendmail = false) {
 
        $this->debug->log($this->config_jobs['log_dir'], $this->jobid . ".log", $msg, $sendmail); 

        return true;
    }
    
    // Watchdog timer update for this job
    public function watchdog() {
    
        $this->app->watchdog();
    
        return true;
    }
}

?>