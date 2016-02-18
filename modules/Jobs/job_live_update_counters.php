<?php
// Videosquare live update counters job
define('BASE_PATH',	realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );
define('JOB_FILE', __FILE__);

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');

// Utils
include_once('job_utils_base.php');
include_once('job.live.class.php');

set_time_limit(0);

$live = new Live();

// Check operating system - exit if Windows
if ( iswindows() ) {
	echo "ERROR: Non-Windows process started on Windows platform\n";
	exit;
}

// Exit if any STOP file is present
if ( is_file( $live->app->config['datapath'] . 'jobs/' . $live->jobid . '.stop' ) or is_file( $live->app->config['datapath'] . 'jobs/all.stop' ) ) exit;

// Runover check. Is this process already running? If yes, report and exit
if ( !$live->runOverControl() ) exit;

clearstatcache();

// Watchdog
$live->watchdog();

// List of active livefeedids
$livefeedids = array();

// Query live viewers to each channels
$live_feeds = $live->getLiveViewerCountersForAllFeeds();
if ( $live_feeds !== false ) {
    
    for ( $i = 0; $i < count($live_feeds); $i++ ) {

        $live->selectLiveFeed($live_feeds[$i]['livefeedid']);
        $live->updateLiveFeedViewCounter($live_feeds[$i]['currentviewers']);
            
        // Log livefeed IDs to update non-active livefeeds to zero
        array_push($livefeedids, $live_feeds[$i]['livefeedid']);
    }
    
}
    
// Set all currentviewers > 0 to zero (no active livefeeds anymore)
$err = $live->setLiveFeedViewCountersToZero($livefeedids);

exit;

?>
