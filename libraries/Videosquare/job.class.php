<?php
namespace Videosquare;

include_once( BASE_PATH . 'libraries/Springboard/Application/Job.php');

class VSQJob extends \Springboard\Application\Job {

    private $job_id = null;
    private $job_filename = null;

    private $debug_mode = false;
    
    public function __construct() {
        
        // Call parrent's (Springboard Job class) constructor
        parent::__construct(BASE_PATH, PRODUCTION);

        // Load job configuration
        $this->loadConfig('modules/Jobs/config_jobs.php');
        
        // Job id and path
        $this->job_id = basename($_SERVER["PHP_SELF"], ".php");
        $this->job_filename = $this->bootstrap->config['config_jobs']['job_dir'] . basename($_SERVER["PHP_SELF"]);
        
        // Debug mode
        if ( isset($this->bootstrap->config['jobs'][$this->bootstrap->config['node_role']][$this->job_id]) ) {
            $this->debug_mode = $this->bootstrap->config['jobs'][$this->bootstrap->config['node_role']][$this->job_id]['debug_mode'];
        }

//var_dump($this);

    }

    // Wrapper for debug log
    public function debugLog($msg, $sendmail = false) {

        $this->d->log($this->logDir, $this->getLogFile(), $msg, $sendmail);
        
    }
    
    protected function process() {
        echo "...abstract method and must therefore be declared...\n";    
        return true;
    }

/*
    // Number of processes by name (regexp)
    private function checkProcessExists($processName) {
        exec("ps uax | grep -i '$processName' | grep -v grep", $pids);
        return count($pids);
    }

    // Check if this job already running. Most jobs should not run over each other.
    public function runOverControl() {

        $processes = $this->checkProcessStartTime("php.*" . $this->job_filename);
        
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
*/

}


?>