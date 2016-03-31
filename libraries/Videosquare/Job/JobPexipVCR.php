<?php
namespace Videosquare\Job;

define('BASE_PATH',	realpath( __DIR__ . '/../../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

include_once('Job.php');
include_once('../../../modules/Jobs/job_utils_base.php');

class PexipJob extends Job {

    // Job level config
    protected $needsLoop                = true;
    protected $signalReceived           = false;
    protected $needsSleep               = true;

    // Videosquare job specific config options
    protected $removeLockOnStart        = true;
            
    // Process job task
    protected function process() {

        $vcrObj = $this->bootstrap->getVSQModel("VCR");

        // Get recordings to start (with 'ready' recording links)
        $err = $vcrObj->getPendingLiveRecordings($this->bootstrap->config['config_jobs']['dbstatus_vcr_start'], $this->bootstrap->config['config_jobs']['dbstatus_vcr_ready']);
    
    //var_dump($err);
    
        echo "kaka\n";
        exit;
        
    }

}

$job = new PexipJob(BASE_PATH, PRODUCTION);

try {
    $job->run();
} catch( Exception $err ) {
    $job->debugLog( '[EXCEPTION] run(): ' . $err->getMessage(), false );
    throw $err;
}

?>
