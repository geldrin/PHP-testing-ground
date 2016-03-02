<?php
namespace Videosquare\Job;

define('BASE_PATH',	realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

include_once('./Job/Job.php');

class JobSkeleton extends \Videosquare\Job\Job {

    // Job level config
    protected $needsLoop            = true;     // Looped job?

    protected $signalReceived       = false;    // Watch for signals?

    protected $needsSleep          = true;      // Do we sleep?
    protected $closeDbOnSleep      = true;      // Close DB connection on sleep?

    protected $sleepSeconds         = 1;        // Sleep start duration
    protected $maxSleepSeconds      = 30;       // Sleep max duration (sleeps sleepSeconds * 2 in every round)

    // Videosquare job specific config options
    protected $isWindowsJob             = false;    // Running on Windows?
    protected $needsRunOverControl      = true;
    protected $needsConfigChangeExit    = true;
    
    // REWRITE this function to implement job processing
    protected function process() {
        
        // Get my name and send a message to log file
        $this->debugLog($this->getMyName() . " is processing...");
        
        $recid = "";
        
        try {
        
            // Get model
            $model = $this->bootstrap->getVSQModel("Recordings");
            
            // Get status of recording id#12
            $model->selectRecording($recid);
            $this->debugLog("Recording status (id#" . $recid . "): " . $model->getRecordingStatus());
            
        } catch ( \Videosquare\Model\Exception $err ) {
            
            if ( $err->getCode() == 100 ) {
                $this->debugLog("[EXCEPTION] Fatal Error (100) is detected. Stop processing.", false);
                throw $err;
            } else {
                $this->debugLog("[EXCEPTION] CAUGHT IT DURING PROCESSING. But let's go on, I have to process remaining tasks!");
                $this->debugLog("[EXCEPTION] " . $err->getMessage(), false );
            }
            
        }
        
        // We did not do anything, sleep longer and longer. Reset sleep seconds only if $needSleep = false;
        $this->needsSleep = true;
        if ( !$this->needsSleep ) $this->currentSleepSeconds = 1;
        
        $this->debugLog("[INFO] Will sleep: " . $this->currentSleepSeconds);
        
        return true;
    }

}

// Remove watchdog
unlink("/home/conv/dev.videosquare.eu/data/watchdog/JobSkeleton.php.watchdog");

$job = new JobSkeleton(BASE_PATH, PRODUCTION);

try {
    $job->run();
} catch( \Videosquare\Model\Exception $err ) {
    $job->debugLog( '[FATAL ERROR] ' . $err->getMessage(), false );
    throw $err;
}

