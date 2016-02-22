<?php
namespace Videosquare;

define('BASE_PATH',	realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

include_once('job.class.php');

class myJob extends VSQJob {

    protected $needsLoop        = true;

    protected $signalReceived   = false;
    
    protected $sleepSeconds     = 10;
    protected $maxSleepSeconds  = 180;

    protected function process() {
        echo "myJob is processing...\n";
        
        try {
            $model = $this->bootstrap->getModel("\\Videosquare\\Job_Recordings");
            //$model = $this->bootstrap->getModel("Job_Recordings");
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
unlink("/home/conv/dev.videosquare.eu/data/watchdog/job_skeleton.php.watchdog");

//$err = class1("\\Videosquare\Job_Recordings");
//var_dump($err);

$job = new \Videosquare\myJob(BASE_PATH, PRODUCTION);

try {
  $job->run();
} catch( Exception $err ) {
  $job->log( 'run() exception: ' . $err->getMessage(), false );
  throw $err;
}

function class1($class) {

    if ( strpos( $class, '\\' ) === 0 )
      $class = substr( $class, 1 );

    $file  = str_replace('\\', '/', $class ) . '.php';

    // not supported
    if ( strpos( $class, '\\' ) === false )
      return false;
    elseif (
             strpos( $class, 'Visitor\\' ) === 0 or
             strpos( $class, 'Admin\\' ) === 0
           )
      return "modules/" . $file;
    elseif ( strpos( $class, 'Model\\' ) === 0 ) {

      $file =
        "Model/" .
        str_replace('Model/', '', $file )
      ;

      return $file;

    } else
      return "libraries/" . $file; 
 
}

?>
