<?php
namespace Videosquare\Job;

define('BASE_PATH',	realpath( __DIR__ . '/../../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

include_once('Job.php');
include_once('../Modules/pexip.php');
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
        $recLinks = $vcrObj->getPendingLiveRecordings("pexip", $this->bootstrap->config['config_jobs']['dbstatus_vcr_start'], $this->bootstrap->config['config_jobs']['dbstatus_vcr_ready']);
    
var_dump($recLinks);
exit;
    
        if ( $recLinks !== false ) {
            
            foreach ( $recLinks as $recLink ) {

                $pexip = new Pexip($recLink['apiserver'], $recLink['apiport'], $recLink['apiuser'], $recLink['apipassword'], $recLink['apiishttpsenabled'], $recLink['pexiplocation']);

                $pexip->addStreamingParticipant($recLink['alias'], $recLink['livestreamtranscoderingressurl'] . '/' . 'pexipp', $recLink['livestreamtranscoderingressurl'] . '/' . 'pexipc');
                
                sleep(10);
                
                $err = $pexip->getStreamingParticipantStatus();
                if ( !$err ) echo "Participant does not exist...\n";
                
                sleep(60);

                echo "DISCONNECT...\n";

                $err = $pexip->disconnectStreamingParticipant();
                if ( !$err ) echo "Participant does not exists...\n";

                
/*    ["istranscoderencoded"]=>
    string(1) "1"
    ["transcoderid"]=>
    string(1) "1"
    ["livestreamtranscoderid"]=>
    string(1) "1"
    ["livestreamtranscodername"]=>
    string(16) "NGINX transcoder"
    ["livestreamtranscodertype"]=>
    string(5) "nginx"
    ["livestreamtranscoderserver"]=>
    string(25) "transcoder.videosquare.eu"
    ["livestreamtranscoderingressurl"]=>
    string(53) "rtmp://transcoder.videosquare.eu:1935/devvsqlivetrans"
*/
            
            }
            
        }

    
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
