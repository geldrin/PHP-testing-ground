<?php
namespace Videosquare\Job;

define('BASE_PATH',	realpath( __DIR__ . '/../../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

include_once('Job.php');
include_once('../Modules/Filesystem.php');
include_once( BASE_PATH . 'resources/apitest/httpapi.php');

class RecordingsUploadJob extends Job {

    // Job level config
    protected $needsLoop                = true;
    protected $signalReceived           = false;
    protected $needsSleep               = true;

    // Videosquare job specific config options
    protected $removeLockOnStart        = true;
    
    protected $debug_mode               = true;
    protected $cachetimeoutseconds      = 300;
            
    protected $temp = array();
    
    // Process job task
    protected function process() {
        
        $vcrObj = $this->bootstrap->getVSQModel("VCR");
        
        $liveFeedRecordings = $vcrObj->getLiveFeedRecordings($id = null, $livefeedid = null, "finished", $this->bootstrap->config['node_sourceip']);
        
        foreach ( $liveFeedRecordings as $liveFeedRecording ) {
            
            echo "livefeedrecording processed:\n";
            var_dump($liveFeedRecording);
            
            $vcrObj->selectLiveFeed($liveFeedRecording['livefeedid']);
            
            $liveStreams = $vcrObj->getStreamsForLivefeed();
            
            // Get stream IDs for this recording (for filename filtering)
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
            
            // Search video channel recording
            $recordingVideo = $this->findFileByClosestOffset($streamVideo, $liveFeedRecording['starttimestamp']);
            echo "Video found:\n";
            var_dump($recordingVideo);
                        
            if ( $recordingVideo === false ) {
                $this->debugLog("[ERROR] No recorded video file found for livefeed id#" . $liveFeedRecording['livefeedid'], false);
                $vcrObj->selectLiveFeedRecording($liveFeedRecording['id']);
                $vcrObj->updateLiveFeedRecording("notfound", null, null);
            } else {
                // Upload using Videosquare API
                try {
                    $api = new \Api($this->bootstrap->config['api_user'], $this->bootstrap->config['api_password']);
                    
                    $api->setDomain($liveFeedRecording['domain']);
                    
                    // Upload recording
                    $time_start = time();
                    //$recording = $api->uploadRecording($filename, "hun", $liveFeedRecording['userid'], 0);
                    $recording = $api->uploadRecording($this->bootstrap->config['recpath'] . $recordingVideo['file'], "hun");
                    $duration = time() - $time_start;
                    $mins_taken = round($duration / 60, 2);
                    
                    var_dump($recording);
                    
                    echo "Successful upload in " . $mins_taken . " mins.\n";
                                
                    $metadata = array(
                        'title'					=> "Videoconference recording: " . $liveFeedRecording['starttimestamp'],
                        'recordedtimestamp'		=> $liveFeedRecording['starttimestamp'],
                        'copyright'				=> 'Minden jog fenntartva. A felvétel egészének vagy bármely részének újrafelhasználása kizárólag a szerző(k) engedélyével lehetséges.',
                        'slideonright'			=> 1,
                        'accesstype'			=> 'public',
                        'ispublished'			=> 0,
                        'isdownloadable'		=> 0,
                        'isaudiodownloadable'	=> 0,
                        'isembedable'			=> 1
                    );
        
                    $api->modifyRecording($recording['data']['id'], $metadata);
                    
                } catch ( \Exception $err ) {
                    
                    $this->debugLog( '[EXCEPTION] run(): ' . $err->getMessage(), false );
                    echo $err->getMessage() . "\n";
                    
                }
                
            }

            // Search content channel recording
            $recordingContent = $this->findFileByClosestOffset($streamContent, $liveFeedRecording['starttimestamp']);
            echo "Content found:\n";
            var_dump($recordingContent);
                        
            if ( $recordingContent === false ) $this->debugLog("[INFO] No recorded content file found for livefeed id#" . $liveFeedRecording['livefeedid'], false);

            exit;
        
        }
        
/*         $cacheindex = 'nginxrecordings-' . $hostname;
        
        $cache = $this->bootstrap->getCache($cacheindex, $this->cachetimeoutseconds, true);

        $tmp = $cache->get();
        echo "Cache content:\n";
        var_dump($tmp);

        array_push($this->temp, date("Y-m-d H:i:s"));
        echo "Put to cache:\n" . print_r($this->temp, true) . "\n";

        $cache->put($this->temp);
        
        if ( $cache->expired() ) {
            echo "Cache expired!!!!\n";
        } */
        
        echo "Sleep...\n";
        
    }
    
    private function findFileByClosestOffset($streamID, $starttime) {
                
        $starttimestamp = strtotime($starttime);
        
        // Search for stream ID and date in file
        $filePattern = $streamID . "*_" . date("Y-m-d", $starttimestamp) . "_*.flv";
        $files = \Videosquare\Modules\Filesystem::findFilesByPattern($this->bootstrap->config['recpath'], "f", $filePattern);
        asort($files);
        
        var_dump($files);
        
        $hitFile['offset'] = 3600;
        foreach ( $files as $file ) {
            
            // Is file closed already?
            $filename = $this->bootstrap->config['recpath'] . $file;
            if ( \Videosquare\Modules\Filesystem::isFileClosed($filename) ) {

                $tmp = explode("_", $file, 3);
                $recordDate = $tmp[1] . " " . substr($tmp[2], 0, 2) . ":" . substr($tmp[2], 2, 2) . ":" . substr($tmp[2], 4, 2);
                $recordTimeStamp = strtotime($recordDate);
                $offset = abs(strtotime($starttime) - $recordTimeStamp);
                
                if ( $hitFile['offset'] > $offset ) {
                    $hitFile['offset'] = $offset;
                    $hitFile['file'] = $file;
                }
                
            }
        }
        
        if ( !isset($hitFile['file']) ) return false;

        return $hitFile;
    }

}

$job = new RecordingsUploadJob(BASE_PATH, PRODUCTION);

try {
    $job->run();
} catch( Exception $err ) {
    $job->debugLog( '[EXCEPTION] run(): ' . $err->getMessage(), false );
    throw $err;
}

?>
