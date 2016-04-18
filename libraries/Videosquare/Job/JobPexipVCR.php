<?php
namespace Videosquare\Job;

/*
UPDATE  `devvideosquare`.`livefeeds` SET  `status` =  'ready' WHERE  `livefeeds`.`id` =43;
UPDATE  `devvideosquare`.`recording_links` SET  `status` =  'ready' WHERE  `recording_links`.`id` =3;
*/

// Issue-k:
// 1. Ha ures a konferencia, akkor connectal a stream, majd lebomlik. Hogyan kezeljuk?
// 2. Ha nincs prezi, akkor is ad egy ikont videokent.

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
    
    protected $debug_mode               = true;
            
    // Process job task
    protected function process() {
    
        $vcrObj = $this->bootstrap->getVSQModel("VCR");

        // Get recordings to start (with 'ready' recording links)
        $recLinks = $vcrObj->getPendingLiveRecordings("pexip", $this->bootstrap->config['config_jobs']['dbstatus_vcr_start'], $this->bootstrap->config['config_jobs']['dbstatus_vcr_ready']);
    
        if ( $recLinks !== false ) {
            
            foreach ( $recLinks as $recLink ) {
                
                // Select objects
                $vcrObj->selectLiveFeed($recLink['id']);
                $vcrObj->selectRecordingLink($recLink['recordinglinkid']);
                
                if ( $this->debug_mode ) $this->debugLog("[DEBUG] START Pexip recording:\n" . print_r($recLink, true), false);
                
                // Get streams for selected livefeed
                $liveStreams = $vcrObj->getStreamsForLivefeed();
                
                if ( $this->debug_mode ) $this->debugLog("[DEBUG] Live stream data for livefeed:\n" . print_r($liveStreams, true), false);
                
                // Get stream IDs for transcoder
                $streamVideo = null;
                $streamContent = null;
                foreach ( $liveStreams as $liveStream ) {
                    
                    if ( !empty($liveStream['keycode']) ) {
                        $tmp = explode("_", $liveStream['keycode'], 2);
                        $streamVideo = $tmp[0];
                    }
                    
                    if ( !empty($liveStream['contentkeycode']) ) {
                        $tmp = explode("_", $liveStream['contentkeycode'], 2);
                        $streamContent = $tmp[0];
                    }
   
                }

                // Debug
                if ( $this->debug_mode ) $this->debugLog("[DEBUG] Stream ID selected for transcoder input: video = '" . $streamVideo . "' | content = '" . $streamContent . "'", false);
                
                // Pexip
                $pexip = new Pexip($recLink['apiserver'], $recLink['apiport'], $recLink['apiuser'], $recLink['apipassword'], $recLink['apiishttpsenabled'], $recLink['pexiplocation']);

                // Add recorder (nginx) as a participant
                if ( $this->debug_mode ) $this->debugLog("[DEBUG] Adding streaming participant: alias = '" . $recLink['alias'] . "' | transcoder video url = " . $recLink['livestreamtranscoderingressurl'] . '/' . $streamVideo . " | transcoder content url = " . $recLink['livestreamtranscoderingressurl'] . '/' . $streamContent, false);
                $result = $pexip->addStreamingParticipant($recLink['alias'], $recLink['livestreamtranscoderingressurl'] . '/' . $streamVideo, $recLink['livestreamtranscoderingressurl'] . '/' . $streamContent);
                if ( $this->debug_mode ) $this->debugLog("[DEBUG] Adding streaming participant result:\n" . print_r($result, true), false);

                // Update status fields and datafor livefeed and recording link
                if ( $result['status'] == "success" ) {
                    // Update recording link status ("recording")
                    $vcrObj->updateRecordingLinkStatus($this->bootstrap->config['config_jobs']['dbstatus_vcr_recording']);
                    // Update livefeed status ("recording")
                    $vcrObj->updateLiveFeedStatus($this->bootstrap->config['config_jobs']['dbstatus_vcr_recording']);
                    // Update livefeed Pexip participant ID
                    $vcrObj->updateLiveFeedParams($result['data']['participant_id']);
                    // Update recording link Pexip participant ID
                    $vcrObj->updateRecordingLinkParams($result['data']['participant_id']);
                }

                unset($pexip);
            }
            
        }
        
        // Wait some between operations
        sleep(2);
        
        // CHECK: check if participant is connected
        $recLinks = $vcrObj->getPendingLiveRecordings("pexip", $this->bootstrap->config['config_jobs']['dbstatus_vcr_recording'], $this->bootstrap->config['config_jobs']['dbstatus_vcr_recording']);
        if ( $recLinks !== false ) {
            
            foreach ( $recLinks as $recLink ) {
                
                if ( $this->debug_mode ) $this->debugLog("[DEBUG] CHECK Pexip recording participant:\n" . print_r($recLink, true), false);
                
                // Select objects
                $vcrObj->selectLiveFeed($recLink['id']);
                $vcrObj->selectRecordingLink($recLink['recordinglinkid']);
            
                // Pexip
                $pexip = new Pexip($recLink['apiserver'], $recLink['apiport'], $recLink['apiuser'], $recLink['apipassword'], $recLink['apiishttpsenabled'], $recLink['pexiplocation']);

                // Get livefeed data from database
                $liveFeed = $vcrObj->getLiveFeed();                
                if ( $liveFeed === false ) throw new \Exception("[ERROR] Could not get livefeed information for feedid#" . $recLink['id']);
                if ( $this->debug_mode ) $this->debugLog("[DEBUG] Live feed information:\n" . print_r($liveFeed, true), false);
                
                // Set Pexip participant ID for VCR object
                $vcrObj->setParticipantID($liveFeed[0]['vcrparticipantid']);

                $result = $pexip->getStreamingParticipantStatus($liveFeed[0]['vcrparticipantid']);
                if ( !$result ) {
                    // Exception?
                    $this->debugLog("[ERROR] Participant id#" . $liveFeed[0]['vcrparticipantid'] . " not in call anymore:\n" . print_r($result, true), false);
                    // Set status to disconnected, report error!
                } else {
                    if ( $this->debug_mode ) $this->debugLog("[DEBUG] Participant id#" . $liveFeed[0]['vcrparticipantid'] . " still in call.", false);
                }                    
                
                unset($pexip);

            }
            
        }
        
        // DISCONNECT: Get recordings to stop
        $recLinks = $vcrObj->getPendingLiveRecordings("pexip", $this->bootstrap->config['config_jobs']['dbstatus_vcr_disc'], $this->bootstrap->config['config_jobs']['dbstatus_vcr_recording']);

        if ( $recLinks !== false ) {
            
            foreach ( $recLinks as $recLink ) {
                
                if ( $this->debug_mode ) $this->debugLog("[DEBUG] DISCONNECT Pexip recording participant:\n" . print_r($recLink, true), false);
                
                // Select objects
                $vcrObj->selectLiveFeed($recLink['id']);
                $vcrObj->selectRecordingLink($recLink['recordinglinkid']);
            
                // Pexip
                $pexip = new Pexip($recLink['apiserver'], $recLink['apiport'], $recLink['apiuser'], $recLink['apipassword'], $recLink['apiishttpsenabled'], $recLink['pexiplocation']);

                // Get livefeed data from database
                $liveFeed = $vcrObj->getLiveFeed();
                if ( $liveFeed === false ) throw new \Exception("[ERROR] Could not get livefeed information for feedid#" . $recLink['id']);
                if ( $this->debug_mode ) $this->debugLog("[DEBUG] Live feed information:\n" . print_r($liveFeed, true), false);
                
                // Set Pexip participant ID for VCR object
                $vcrObj->setParticipantID($liveFeed[0]['vcrparticipantid']);
                
                // Disconnect participant
                $result = $pexip->disconnectStreamingParticipant($liveFeed[0]['vcrparticipantid']);
                if ( !$result ) {
                    // Exception?
                    $this->debugLog("[ERROR] Participant id#" . $liveFeed[0]['vcrparticipantid'] . " cannot be disconnected:\n" . print_r($result, true), false);
                    // Set status to disconnected, report error!
                } else {
                    if ( $this->debug_mode ) $this->debugLog("[DEBUG] Participant id#" . $liveFeed[0]['vcrparticipantid'] . " is diconnected.", false);
                }
                
                // Update status fields and datafor livefeed and recording link
                if ( $result['status'] == "success" ) {
                    // Update recording link status ("ready")
                    $vcrObj->updateRecordingLinkStatus($this->bootstrap->config['config_jobs']['dbstatus_vcr_ready']);
                    // Update livefeed status ("ready")
                    $vcrObj->updateLiveFeedStatus($this->bootstrap->config['config_jobs']['dbstatus_vcr_ready']);
                }
                
                unset($pexip);
                
            }
            
        }
        
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
