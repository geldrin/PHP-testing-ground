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
    protected $needsRunOverControl      = true;
    protected $needsConfigChangeExit    = true;
    
    // REWRITE this function to implement job processing
    protected function process() {
        
        echo $this->getMyName() . " is processing...\n";

        // Get model
        $model = $this->bootstrap->getVSQModel("Recordings");
        
        // Get status of recording id#12
        $recid = 12;
        $model->selectRecording($recid);
        echo "Recording status (id#" . $recid . "): " . $model->getRecordingStatus() . "\n";
            
        $this->debugLog("[DEBUG] This is a debug message.", false);
        
        // We did not do anything, sleep longer and longer. Reset sleep seconds only if $needSleep = false;
        $this->needsSleep = true;
        if ( !$this->needsSleep ) $this->currentSleepSeconds = 1;
        
        echo "sleep: " . $this->currentSleepSeconds . "\n";
        
        return true;
    }

}

// Remove watchdog
unlink("/home/conv/dev.videosquare.eu/data/watchdog/JobSkeleton.php.watchdog");

$job = new JobSkeleton(BASE_PATH, PRODUCTION);

try {
    $job->run();
} catch( Exception $err ) {
    $job->debugLog( '[EXCEPTION] run(): ' . $err->getMessage(), false );
    throw $err;
}

