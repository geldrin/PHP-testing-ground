<?php

// Job: updates live concurrent user counter for active live streams.

namespace Videosquare\Job;

define('BASE_PATH',	realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

include_once('./Job/Job.php');

class LiveUpdateCounters extends \Videosquare\Job\Job {

    // Job level config
    protected $needsLoop                = true;     // Looped job?

    protected $signalReceived           = true;     // Watch for signals?

    protected $needsSleep               = true;     // Do we sleep?
    protected $closeDbOnSleep           = true;     // Close DB connection on sleep?

    protected $sleepSeconds             = 60;       // Sleep start duration
    protected $maxSleepSeconds          = 60;       // Sleep max duration (sleeps sleepSeconds * 2 in every round)

    // Videosquare job specific config options
    protected $isWindowsJob             = false;    // Running on Windows?
    protected $needsRunOverControl      = true;
    protected $needsConfigChangeExit    = true;
    
    // REWRITE this function to implement job processing
    protected function process() {
        
        $model = $this->bootstrap->getVSQModel("Live");
        
        // Query live viewers to each channels
        $livefeedids = array();
        $live_feeds = $model->getLiveViewerCountersForAllFeeds();
        if ( $live_feeds !== false ) {
            
            for ( $i = 0; $i < count($live_feeds); $i++ ) {

                $model->selectLiveFeed($live_feeds[$i]['livefeedid']);
                $model->updateLiveFeedViewCounter($live_feeds[$i]['currentviewers']);
                    
                // Log livefeed IDs to update non-active livefeeds to zero
                array_push($livefeedids, $live_feeds[$i]['livefeedid']);
            }
            
        }
    
        // Set all currentviewers > 0 to zero (no active livefeeds anymore)
        $err = $model->setLiveFeedViewCountersToZero($livefeedids);
    }

}

unlink("/home/conv/dev.videosquare.eu/data/watchdog/LiveUpdateCounters.php.watchdog");

$job = new LiveUpdateCounters(BASE_PATH, PRODUCTION);

try {
    $job->run();
} catch( Exception $err ) {
    $job->debugLog( '[EXCEPTION] run(): ' . $err->getMessage(), false );
    throw $err;
}

?>
