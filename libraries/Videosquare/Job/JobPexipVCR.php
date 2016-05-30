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
    protected $closeDbOnSleep           = true;
    
    protected $sleepSeconds             = 10;
    protected $maxSleepSeconds          = 20;

    // Videosquare job specific config options
    protected $removeLockOnStart        = true;
    
    protected $debug_mode               = true;
            
    // Process job task
    protected function process() {
    
        $vcrObj = $this->bootstrap->getVSQModel("VCR");

        // Get recordings to start (with 'ready' recording links)
        $recLinks = $vcrObj->getPendingLiveFeeds("pexip", $this->bootstrap->config['config_jobs']['dbstatus_vcr_start'], $this->bootstrap->config['config_jobs']['dbstatus_vcr_ready']);
    
        if ( $recLinks !== false ) {
            
            foreach ( $recLinks as $recLink ) {
                
                // Select objects
                $vcrObj->selectLiveFeed($recLink['id']);
                $vcrObj->selectRecordingLink($recLink['recordinglinkid']);
                if ( $recLink['needrecording'] == 1 ) {
                    $vcrObj->selectLiveFeedRecording($recLink['livefeedrecordingid']);
                }
                
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
                if ( $this->debug_mode ) $this->debugLog("[DEBUG] Adding RTMP streaming participant: alias = '" . $recLink['alias'] . "' | transcoder video url = " . $recLink['livestreamtranscoderingressurl'] . '/' . $streamVideo . " | transcoder content url = " . $recLink['livestreamtranscoderingressurl'] . '/' . $streamContent, false);
                $result = $pexip->addParticipant($recLink['alias'], $recLink['livestreamtranscoderingressurl'] . '/' . $streamVideo, $recLink['livestreamtranscoderingressurl'] . '/' . $streamContent, 'rtmp', 'chair', 'Recorder');
                if ( $this->debug_mode ) $this->debugLog("[DEBUG] Adding RTMP streaming participant result:\n" . print_r($result, true), false);
                                
                // Update status fields and datafor livefeed and recording link
                if ( $result['status'] == "success" ) {
                    // Update recording link status ("recording")
                    $vcrObj->updateRecordingLinkStatus($this->bootstrap->config['config_jobs']['dbstatus_vcr_recording']);
                    // Update livefeed status ("recording")
                    $vcrObj->updateLiveFeed($this->bootstrap->config['config_jobs']['dbstatus_vcr_recording']);
                    // Update livefeed Pexip participant ID
                    $vcrObj->updateLiveFeedParams(array('vcrparticipantid' => $result['data']['participant_id']));
                    // Update recording link Pexip participant ID
                    $vcrObj->updateRecordingLinkParams($result['data']['participant_id']);
                    // Update livefeed recording start date
                    if ( $recLink['needrecording'] == 1 ) {
                        $vcrObj->updateLiveFeedRecording(null, date("Y-m-d H:i:s"));
                    }
                    
                    // Add auto dial participant if defined
                    if ( !empty($recLink['autodialparticipant']) ) {
                        
                        if ( $this->debug_mode ) $this->debugLog("[DEBUG] Adding auto dial participant: alias = '" . $recLink['alias'] . "' | destination = " . $recLink['autodialparticipant'] . " | protocol = " . $recLink['autodialparticipantprotocol'] . " | display name = " . $recLink['autodialparticipantdisplayname'], false);
                        $result = $pexip->addParticipant($recLink['alias'], $recLink['autodialparticipant'], null, $recLink['autodialparticipantprotocol'], 'chair', $recLink['autodialparticipantdisplayname']);
                        if ( $this->debug_mode ) $this->debugLog("[DEBUG] Adding auto dial participant result:\n" . print_r($result, true), false);
                        
                        if ( !$result ) $this->debugLog("[ERROR] Adding auto dial participant failed", true);
                        
                        if ( $result['status'] == "success" ) {
                            // Update livefeed auto dial participant ID
                            $vcrObj->updateLiveFeedParams(array('autodialparticipantid' => $result['data']['participant_id']));
                        }
                        
                    }
                    
                }

                unset($pexip);
            }
            
        }
        
        // Wait some between operations
        sleep(2);
        
        // CHECK: check if participant is connected
        $recLinks = $vcrObj->getPendingLiveFeeds("pexip", $this->bootstrap->config['config_jobs']['dbstatus_vcr_recording'], $this->bootstrap->config['config_jobs']['dbstatus_vcr_recording']);
        if ( $recLinks !== false ) {
            
            foreach ( $recLinks as $recLink ) {
                
                if ( $this->debug_mode ) $this->debugLog("[DEBUG] Pexip recording participant check:\n" . print_r($recLink, true), false);
                
                // Select objects
                $vcrObj->selectLiveFeed($recLink['id']);
                $vcrObj->selectRecordingLink($recLink['recordinglinkid']);
                if ( $recLink['needrecording'] == 1 ) {
                    $vcrObj->selectLiveFeedRecording($recLink['livefeedrecordingid']);
                }
                
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
                    // Update recording link status ("ready")
                    $vcrObj->updateRecordingLinkStatus($this->bootstrap->config['config_jobs']['dbstatus_vcr_ready']);
                    // Update livefeed status ("ready")
                    $vcrObj->updateLiveFeed($this->bootstrap->config['config_jobs']['dbstatus_vcr_ready'], null);
                    // Update livefeed recording end date
                    if ( $recLink['needrecording'] == 1 ) {
                        $vcrObj->updateLiveFeedRecording("finished", null, date("Y-m-d H:i:s"));
                    }
                    
                    // Log
                    $this->debugLog("[ERROR] Participant id#" . $liveFeed[0]['vcrparticipantid'] . " not in call anymore:\n" . print_r($pexip->lastapidatareturned, true), true);
                    
                    // Disconnect auto dial out participant if exists
                    if ( !empty($liveFeed[0]['autodialparticipantid']) ) {

                        // Pexip: disconnect
                        $this->debugLog("[INFO] Disconnecting auto dial participant id#" . $liveFeed[0]['autodialparticipantid'], false);
                        $result = $pexip->disconnectStreamingParticipant($liveFeed[0]['autodialparticipantid']);
                        if ( !$result ) {
                            $this->debugLog("[ERROR] Auto dial participant id#" . $liveFeed[0]['autodialparticipantid'] . " cannot be disconnected. Already disconnected by Pexip?\n" . print_r($result, true), true);
                        } else {
                            if ( $this->debug_mode ) $this->debugLog("[DEBUG] Auto dial participant id#" . $liveFeed[0]['autodialparticipantid'] . " is successfully diconnected.", false);
                        }
                        
                        // Update live feed paramaters
                        $vcrObj->updateLiveFeedParams(array('autodialparticipantid' => null));

                    }
                    
                } else {
                    
                    // Recorder is in call
                    if ( $this->debug_mode ) $this->debugLog("[DEBUG] Participant id#" . $liveFeed[0]['vcrparticipantid'] . " still in call.", false);
                    
                    // Is auto dial party in call?
                    if ( !empty($liveFeed[0]['autodialparticipantid']) ) {
                        
                        $result = $pexip->getStreamingParticipantStatus($liveFeed[0]['autodialparticipantid']);
                        if ( !$result ) {

                            // Not in call, try to reconnect
                            $this->debugLog("[ERROR] Auto dial participant id#" . $liveFeed[0]['autodialparticipantid'] . " is not in call, while streaming is in progress!\n" . print_r($result, true), false);
                        
                            // Add auto dial participant if defined
                            if ( !empty($recLink['autodialparticipant']) ) {
                                
                                if ( $this->debug_mode ) $this->debugLog("[DEBUG] Adding auto dial participant again: alias = '" . $recLink['alias'] . "' | destination = " . $recLink['autodialparticipant'] . " | protocol = " . $recLink['autodialparticipantprotocol'] . " | display name = " . $recLink['autodialparticipantdisplayname'], false);
                                $result = $pexip->addParticipant($recLink['alias'], $recLink['autodialparticipant'], null, $recLink['autodialparticipantprotocol'], 'chair', $recLink['autodialparticipantdisplayname']);
                                if ( $this->debug_mode ) $this->debugLog("[DEBUG] Adding auto dial participant result:\n" . print_r($result, true), false);
                                
                                if ( !$result ) $this->debugLog("[ERROR] Adding auto dial participant failed.\n" . print_r($recLink), true);
                                
                                if ( $result['status'] == "success" ) {
                                    // Update livefeed auto dial participant ID
                                    $vcrObj->updateLiveFeedParams(array('autodialparticipantid' => $result['data']['participant_id']));
                                }
                                
                            }
                            
                        }
                        
                    }

                }                    
                
                unset($pexip);

            }
            
        }
        
        // DISCONNECT: Get recordings to stop
        $recLinks = $vcrObj->getPendingLiveFeeds("pexip", $this->bootstrap->config['config_jobs']['dbstatus_vcr_disc'], $this->bootstrap->config['config_jobs']['dbstatus_vcr_recording']);

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
                $now = date("Y-m-d H:i:s");
                if ( !$result ) {
                    $this->debugLog("[ERROR] Participant id#" . $liveFeed[0]['vcrparticipantid'] . " cannot be disconnected:\n" . print_r($result, true), false);
                } else {
                    if ( $this->debug_mode ) $this->debugLog("[DEBUG] Participant id#" . $liveFeed[0]['vcrparticipantid'] . " is diconnected.", false);
                }
                
                // Update status fields and datafor livefeed and recording link
                if ( $result['status'] == "success" ) {
                    // Update recording link status ("ready")
                    $vcrObj->updateRecordingLinkStatus($this->bootstrap->config['config_jobs']['dbstatus_vcr_ready']);
                    // Update livefeed status ("ready")
                    $vcrObj->updateLiveFeed($this->bootstrap->config['config_jobs']['dbstatus_vcr_ready']);
                    // Update livefeed recording end date
                    if ( $recLink['needrecording'] == 1 ) {
                        $liveFeedRecordings = $vcrObj->getLiveFeedRecordings(null, $recLink['id'], "finishing", null);
                        if ( count($liveFeedRecordings) > 0 ) {
                            $vcrObj->selectLiveFeedRecording($liveFeedRecordings[0]['id']);
                            $vcrObj->updateLiveFeedRecording("finished", null, $now);
                        } else {
                            $this->debugLog("[ERROR] No livefeed recording found for this recording session.", true);
                        }
                        if ( count($liveFeedRecordings) > 1 ) $this->debugLog("[WARN] More livefeed recordings are in progress. Using the latest. Livefeed recording in progress:\n" . print_r($liveFeedRecordings, true), false);
                    }
                    
                    // Disconnect auto dial out participant if exists
                    if ( !empty($liveFeed[0]['autodialparticipantid']) ) {

                        // Pexip: disconnect
                        $this->debugLog("[INFO] Disconnectiog auto dial participant id#" . $liveFeed[0]['autodialparticipantid'], false);
                        $result = $pexip->disconnectStreamingParticipant($liveFeed[0]['autodialparticipantid']);
                        if ( !$result ) {
                            $this->debugLog("[ERROR] Participant id#" . $liveFeed[0]['autodialparticipantid'] . " cannot be disconnected:\n" . print_r($result, true), true);
                        } else {
                            if ( $this->debug_mode ) $this->debugLog("[DEBUG] Participant id#" . $liveFeed[0]['autodialparticipantid'] . " is successfully diconnected.", false);
                        }
                        
                        // Update live feed paramaters
                        $vcrObj->updateLiveFeedParams(array('autodialparticipantid' => null));

                    }
                    
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
