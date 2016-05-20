<?php
namespace Videosquare\Job;

define('BASE_PATH',	realpath( __DIR__ . '/../../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

include_once('Job.php');
include_once('../Modules/Filesystem.php');
include_once('../Modules/runext.php');
include_once( BASE_PATH . 'resources/apitest/httpapi.php');

class RecordingsUploadJob extends Job {

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
    //protected $cachetimeoutseconds      = 300;
            
    protected $temp = array();
    
    // Process job task
    protected function process() {
        
        $vcrObj = $this->bootstrap->getVSQModel("VCR");
        
        $liveFeedRecordings = $vcrObj->getLiveFeedRecordings($id = null, $livefeedid = null, "finished", $this->bootstrap->config['node_sourceip']);
        
        if ( $liveFeedRecordings === false ) return;
        
        foreach ( $liveFeedRecordings as $liveFeedRecording ) {
            
            if ( $this->debug_mode ) $this->debugLog("[DEBUG] Livefeed recording processed:\n" . print_r($liveFeedRecording, true), false);
            
            // Select objects
            $vcrObj->selectLiveFeed($liveFeedRecording['livefeedid']);
            $vcrObj->selectLiveFeedRecording($liveFeedRecording['id']);
            
            // Get live streams to identify stream ID for filename search
            $liveStreams = $vcrObj->getStreamsForLivefeed();
            
            if ( $this->debug_mode ) $this->debugLog("[DEBUG] Livefeed streams:\n" . print_r($liveStreams, true), false);
            
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
            if ( $this->debug_mode ) $this->debugLog("[DEBUG] Recorded video for livefeed id#" . $liveFeedRecording['livefeedid'] . ":\n" . print_r($recordingVideo, true), false);
                        
            if ( $recordingVideo === false ) {
                $this->debugLog("[ERROR] No recorded video file found for livefeed id#" . $liveFeedRecording['livefeedid'], false);
                $vcrObj->updateLiveFeedRecording("notfound", null, null);
            } else {
                
                // Index FLV using yamdi
                //$this->indexFLVFile($this->bootstrap->config['recpath'] . $recordingVideo['file'], $this->bootstrap->config['recpath'] . "temp/" . $recordingVideo['file']);

                // Convert FLV to MP4 (overcome NGINX's junky FLV output)
                $tmp = pathinfo($recordingVideo['file']);
                $dstFileName = $tmp['filename'] . ".mp4";
                $this->convertFLV2MP4($this->bootstrap->config['recpath'] . $recordingVideo['file'], $this->bootstrap->config['recpath'] . "temp/" . $dstFileName);
                
                // Upload using Videosquare API
                try {
                    
                    // API init
                    $api = new \Api($this->bootstrap->config['api_user'], $this->bootstrap->config['api_password']);
                    
                    // API URL domain, set http/https
                    $protocol = "https://";
                    if ( !$this->bootstrap->config['forcesecureapiurl'] ) $protocol = "http://";
                    $api->setDomain($protocol . $liveFeedRecording['domain']);

                    // Debug
                    if ( $this->debug_mode ) $this->debugLog("[DEBUG] Videosquare API init: user = " . $this->bootstrap->config['api_user'] . " | domain = " . $liveFeedRecording['domain'], false);
                    
                    // Upload recording
                    $time_start = time();
                    //$recording = $api->uploadRecording($filename, "hun", $liveFeedRecording['userid'], 0);
                    $recording = $api->uploadRecording($this->bootstrap->config['recpath'] . "temp/" . $dstFileName, "hun");
                    $duration = time() - $time_start;
                    $mins_taken = round($duration / 60, 2);

                    // Debug
                    $this->debugLog("[INFO] Videosquare API upload completed in " . $mins_taken . "mins. API returned values:\n" . print_r($recording, true), false);

                    // Update video metadata
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
                    
                    // Debug
                    $this->debugLog("[INFO] Videosquare API metadata updated:\n" . print_r($metadata, true), false);
                    
                    // Set livefeed recording status
                    $vcrObj->updateLiveFeedRecording("uploaded", null, null, $recording['data']['id']);
                    
                    // Search content channel recording
                    $recordingContent = $this->findFileByClosestOffset($streamContent, $liveFeedRecording['starttimestamp']);
                                
                    if ( $recordingContent === false ) {
                        $this->debugLog("[INFO] No recorded content file found for livefeed id#" . $liveFeedRecording['livefeedid'], false);
                    } else {
                        if ( $this->debug_mode ) $this->debugLog("[DEBUG] Recorded content for livefeed id#" . $liveFeedRecording['livefeedid'] . ":\n" . print_r($recordingContent, true), false);
                        
                        // Index FLV using yamdi
                        //$this->indexFLVFile($this->bootstrap->config['recpath'] . $recordingContent['file'], $this->bootstrap->config['recpath'] . "temp/" . $recordingContent['file']);

                        // Convert FLV to MP4 (overcome NGINX's junky FLV output)
                        $tmp = pathinfo($recordingVideo['file']);
                        $dstFileName = $tmp['filename'] . ".mp4";
                        $this->convertFLV2MP4($this->bootstrap->config['recpath'] . $recordingContent['file'], $this->bootstrap->config['recpath'] . "temp/" . $dstFileName);
                        
                        $time_start = time();
                        $content = $api->uploadContent($recording['data']['id'], $this->bootstrap->config['recpath'] . "temp/" . $dstFileName);
                        $duration = time() - $time_start;
                        $mins_taken = round($duration / 60, 2);
                        
                        // Debug
                        $this->debugLog("[INFO] Videosquare API content upload completed in " . $mins_taken . "mins. API returned values:\n" . print_r($content, true), false);
                    
                    }
                    
                } catch ( \Exception $err ) {
                    
                    $this->debugLog('[EXCEPTION] run(): ' . $err->getMessage(), true);
                    echo $err->getMessage() . "\n";
                    
                }
                
            }
        
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
        
    }
    
    private function findFileByClosestOffset($streamID, $starttime) {
                
        $starttimestamp = strtotime($starttime);
        
        // Search for stream ID and date in file
        $filePattern = $streamID . "*_" . date("Y-m-d", $starttimestamp) . "_*.flv";
        $files = \Videosquare\Modules\Filesystem::findFilesByPattern($this->bootstrap->config['recpath'], "f", $filePattern);
        asort($files);
        
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

    public function convertFLV2MP4($src, $dst) {

        if ( file_exists($dst) ) {
            $err = unlink($dst);
            if ( $err === false ) throw new \Exception("[ERROR] Cannot remove file " . $dst);
        }

        // ffmpeg command
        $command = $this->bootstrap->config['nice'] . " " . $this->bootstrap->config['ffmpeg_alt'] . " -v ". $this->bootstrap->config['ffmpeg_loglevel'] ." -y -i " . $src . " -c copy -copyts -timecode 00:00:00:00 " . $dst;
        
        // Run command
        $output = new runExt($command);
        $output->run();
        
        if ( $this->debug_mode ) $this->debugLog("[DEBUG] ffmpeg command executed:\n" . $command . ".\nResult: " . $output->getCode() . "\nOutput: " . $output->getOutput(), false);
        
        if ( $output->getCode() > 0 ) {
            
            $this->debugLog("[ERROR] ffmpeg conversion error. Return code: " . $output->getCode() . ". Output:\n" . $output->getOutput(), false);
            
        } else {
            
            $this->debugLog("[OK] ffmpeg FLV->MP4 conversion ready. Resulting file: " . $dst, false);
            
            if ( !file_exists($dst) ) $this->debugLog("[ERROR] ffmpeg returned no error, but output file does not exists: " . $dst, false);
            
            if ( filesize($dst) == 0 ) $this->debugLog("[ERROR] ffmpeg returned no error, but output file has zero size: " . $dst, false);
            
        }
        
    }
    
    public function indexFLVFile($src, $dst) {

        if ( file_exists($dst) ) {
            $err = unlink($dst);
            if ( $err === false ) throw new \Exception("[ERROR] Cannot remove file " . $dst);
        }
    
        $command = $this->bootstrap->config['yamdi'] . " -i " . $src . " -o " . $dst;

        // Run command
        $output = new runExt($command);
        $output->run();
        
        if ( $this->debug_mode ) $this->debugLog("[DEBUG] yamdi command executed:\n" . $command . ".\nResult: " . $output->getCode() . "\nOutput: " . $output->getOutput(), false);
        
        if ( $output->getCode() > 0 ) {
            
            $this->debugLog("[ERROR] yamdi conversion error. Return code: " . $output->getCode() . ". Output:\n" . $output->getOutput(), false);
            
        } else {
            
            $this->debugLog("[OK] yamdi conversion ready. Resulting file: " . $dst, false);
            
            if ( !file_exists($dst) ) $this->debugLog("[ERROR] yamdi returned no error, but output file does not exists: " . $dst, false);
            
            if ( filesize($dst) == 0 ) $this->debugLog("[ERROR] yamdi returned no error, but output file has zero size: " . $dst, false);
            
        }
    
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
