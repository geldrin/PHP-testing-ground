<?php

class Job {
    public  $app;
    public  $debug;

    public  $config = null;
    private $timejobstarted = null;
    public $jobid = null;
    
    // Init Job class: connect to DB, get debug class, load configuration, set init values
    public function __construct($jobid) {

        if ( empty($jobid) ) return false;
    
        // Start time
        $this->timejobstarted = time();

        // Init Videosquare application
        $this->app = new Springboard\Application\Cli(BASE_PATH, false);

        // Debug class
        $this->debug = Springboard\Debug::getInstance();        

        // Load configuration
        $this->app->loadConfig('modules/Jobs/config_jobs.php');
        $this->config = $this->app->config;
        $this->config['jobs'] = $this->app->config['config_jobs'];
        
        // Job ID
        if ( !isset($this->config['jobs'][$jobid]) ) {
            return false;
        } else {
            $this->jobid = $this->config['jobs'][$jobid];
        }
        
        return true;
    }
    
    // Check if this job already running. Most jobs should not run over each other.
    public function runOverControl() {

        $goahead = true;

        $processes = checkProcessStartTime("php.*" . $this->config['jobs']['job_dir'] . $this->jobid . ".php");
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
 
        $this->debug->log($this->config['jobs']['log_dir'], $this->jobid . ".log", $msg, $sendmail); 

        return true;
    }
    
    // Watchdog timer update for this job
    public function watchdog() {
    
        $this->app->watchdog();
    
        return true;
    }
}

?>