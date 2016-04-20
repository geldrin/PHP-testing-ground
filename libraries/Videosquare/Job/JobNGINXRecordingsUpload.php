<?php
namespace Videosquare\Job;

define('BASE_PATH',	realpath( __DIR__ . '/../../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

include_once('Job.php');
include_once('../Modules/Filesystem.php');
include_once( BASE_PATH . 'resources/apitest/httpapi.php');

class NGINXRecordingsUploadJob extends Job {

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
    
        echo "Woke up...\n";       
        
        $vcrObj = $this->bootstrap->getVSQModel("VCR");
        
        $liveFeedRecordings = $vcrObj->getFinishedRecordedFeeds($this->bootstrap->config['node_sourceip']);
        
        foreach ( $liveFeedRecordings as $liveFeedRecording ) {
            
            echo "livefeedrecording processed:\n";
            var_dump($liveFeedRecording);
            
            $vcrObj->selectLiveFeed($liveFeedRecording['livefeedid']);
            
            $liveStreams = $vcrObj->getStreamsForLivefeed();
            
            echo "livefeed streams:\n";
            var_dump($liveStreams);
            
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
            
            echo "streamVideo: " . $streamVideo . "\n";
            echo "streamContent: " . $streamContent . "\n";

            $videoFiles = \Videosquare\Modules\Filesystem::findFilesByPattern($this->bootstrap->config['recpath'], "f", $streamVideo . "*.flv");
            
            var_dump($videoFiles);
            
            foreach ( $videoFiles as $videoFile ) {
                
                // Is file closed already?
                $filename = $this->bootstrap->config['recpath'] . $videoFile;
                if ( \Videosquare\Modules\Filesystem::isFileClosed($filename) ) {

                    $tmp = explode("_", $videoFile, 3);
                    $recordDate = $tmp[1] . " " . substr($tmp[2], 0, 2) . ":" . substr($tmp[2], 2, 2) . ":" . substr($tmp[2], 4, 2);
                    $recordTimeStamp = strtotime($recordDate);
                    $offset = abs(strtotime($liveFeedRecording['starttimestamp']) - $recordTimeStamp);
                    
                    echo "offset: " . $offset . "\n";
                    
                    if ( $offset > 30 ) {
                        echo "offset too big\n";
                        exit;
                    }

                    // Connect Videosquare API
                    
                    try {
                        $api = new \Api($this->bootstrap->config['api_user'], $this->bootstrap->config['api_password']);
                        // !!!
                        //$api->apiurl = 'https://dev.videosquare.eu/hu/api';
                        $api->setDomain($liveFeedRecording['domain']);
                        
                        // Upload recording
                        $time_start = time();
                        $recording = $api->uploadRecording($filename, "hun", $liveFeedRecording['userid'], 0);
                        $duration = time() - $time_start;
                        $mins_taken = round( $duration / 60, 2);
                        
                        var_dump($recording);
                        
                        echo "Successful upload in " . $mins_taken . " mins.\n";
                                    
                        $metadata = array(
                            'title'					=> "Videoconference recording: " . $recordDate,
                            'recordedtimestamp'		=> $recordDate,
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
                
            }
        
        }
        
        exit;
        
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

}

$job = new NGINXRecordingsUploadJob(BASE_PATH, PRODUCTION);

try {
    $job->run();
} catch( Exception $err ) {
    $job->debugLog( '[EXCEPTION] run(): ' . $err->getMessage(), false );
    throw $err;
}

?>
