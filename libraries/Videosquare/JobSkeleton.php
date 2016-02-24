<?php
namespace Videosquare\Job;

define('BASE_PATH',	realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

include_once('./Job/Job.php');

class JobSkeleton extends \Videosquare\Job\Job {

    protected $needsLoop        = true;

    protected $signalReceived   = false;
    
    protected $sleepSeconds     = 10;
    protected $maxSleepSeconds  = 180;

    protected function process() {
        echo "myJob is processing...\n";
        
        try {
            $model = $this->bootstrap->getVSQModel("Recordings");
            //var_dump($model);
            $model->selectRecording(12);
            echo $model->getRecordingStatus() . "\n";
        } catch( Exception $err ) {
            $this->log( 'run() exception: ' . $err->getMessage(), false );
        }

        $this->debugLog("[DEBUG] This is a debug message.", false);
        
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
  $job->log( 'run() exception: ' . $err->getMessage(), false );
  throw $err;
}

?>
