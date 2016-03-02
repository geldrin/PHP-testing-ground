<?php
namespace Videosquare\Job;

define('BASE_PATH',	realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

include_once('./Job/Job.php');
include_once('../../modules/Jobs/job_utils_base.php');

class LiveUpdateCounters extends Job {

    // Job level config
    protected $needsLoop                = false;
    protected $signalReceived           = false;
    protected $needsSleep               = false;

    // Videosquare job specific config options
    protected $removeLockOnStart        = false;
            
    // Process job task
    protected function process() {

        $model = $this->bootstrap->getVSQModel("Live");
    
        // Query live viewers to each channels
        $livefeedids = array();
        
        $live_feeds = $model->getLiveViewerCountersForAllFeeds();
        
        if ( $live_feeds !== false ) {
            
            // Debug
            if ( $this->debug_mode ) $this->debugLog("[DEBUG] Active livefeeds: " . print_r($live_feeds, true));
            
            for ( $i = 0; $i < count($live_feeds); $i++ ) {

                $model->selectLiveFeed($live_feeds[$i]['livefeedid']);
                $model->updateLiveFeedViewCounter($live_feeds[$i]['currentviewers']);
                
                // Debug
                if ( $this->debug_mode ) $this->debugLog("[DEBUG] Livefeed id = " . $model->getLiveFeed() . " currentviewers updated to " . $live_feeds[$i]['currentviewers'] . ".");
                    
                // Log livefeed IDs to update non-active livefeeds to zero
                array_push($livefeedids, $live_feeds[$i]['livefeedid']);
            }
            
        } else {
            $this->debugLog("[DEBUG] No active livefeeds to process.");
        }

        // Set all currentviewers > 0 to zero (no active livefeeds anymore)
        $model->setLiveFeedViewCountersToZero($livefeedids);
        
        // Debug
        if ( $this->debug_mode ) {
            if ( !empty($livefeedids) ) $this->debugLog("[DEBUG] currentviewers filed of the following livefeed IDs updated to zero:\n" . print_r($livefeedids, true));
        }
        
    }

}

$job = new LiveUpdateCounters(BASE_PATH, PRODUCTION);

try {
    $job->run();
} catch( Exception $err ) {
    $job->debugLog( '[EXCEPTION] run(): ' . $err->getMessage(), false );
    throw $err;
}

?>
