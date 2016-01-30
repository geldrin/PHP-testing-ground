<?php
// Videosquare live update counters job

define('BASE_PATH',	realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');

// Utils
include_once('job_utils_base.php');
include_once('job_utils_log.php');
include_once('job_utils_status.php');
include_once('job_utils_media2.php');

set_time_limit(0);

// Init
$app = new Springboard\Application\Cli(BASE_PATH, PRODUCTION);

// Load jobs configuration file
$app->loadConfig('modules/Jobs/config_jobs.php');
$jconf = $app->config['config_jobs'];
$myjobid = $jconf['jobid_live_counters'];

// Check operating system - exit if Windows
if ( iswindows() ) {
	echo "ERROR: Non-Windows process started on Windows platform\n";
	exit;
}

// Exit if any STOP file is present
if ( is_file( $app->config['datapath'] . 'jobs/' . $myjobid . '.stop' ) or is_file( $app->config['datapath'] . 'jobs/all.stop' ) ) exit;

// Log init
$debug = Springboard\Debug::getInstance();
$debug_mode = false;

// Runover check. Is this process already running? If yes, report and exit
if ( !runOverControl($myjobid) ) exit;

clearstatcache();

// Watchdog
$app->watchdog();

// Establish database connection
$db = db_maintain();

// Watchdog
$app->watchdog();

// List of active livefeedids
$livefeedids = array();

// Query live viewers to each channels
$live_feeds = getLiveViewersForFeeds();
if ( $live_feeds !== false ) {
    
    for ( $i = 0; $i < count($live_feeds); $i++ ) {

        $values = array(
            'currentviewers'    => $live_feeds[$i]['currentviewers']
        );

        //var_dump($live_feeds[$i]);
        
        // Update livefeed currentviewers counter
        try {
            $liveFeedObj = $app->bootstrap->getModel('livefeeds');
            $liveFeedObj->select($live_feeds[$i]['livefeedid']);
            $liveFeedObj->updateRow($values);
        } catch (exception $err) {
            $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot update currentviewers for livefeedid#" . $live_feeds[$i]['livefeedid'] . "\n\n" . $err, $sendmail = false);
            return false;
        }
        
        // Log livefeed IDs to update non-active livefeeds to zero
        array_push($livefeedids, $live_feeds[$i]['livefeedid']);
    }
    
}
    
// Set all currentviewers > 0 to zero (no active livefeeds any more)
$err = setLiveFeedViewersToZero($livefeedids);

// Close DB connection if open
if ( is_resource($db->_connectionID) ) $db->close();

exit;

function getLiveViewersForFeeds() {
global $jconf, $debug, $db, $app, $myjobid;

	$db = db_maintain();

	$now = date("Y-m-d H:i:s");

	$query = "
        SELECT
            vsl.livefeedid,
            COUNT(vsl.id) AS currentviewers
        FROM
            view_statistics_live AS vsl
        WHERE
            vsl.timestampuntil >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)
        GROUP BY
            vsl.livefeedid";

	try {
		$rs = $db->getArray($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed. " . trim($query), $sendmail = false);
		return false;
	}

	// Check if any record returned
	if ( count($rs) < 1 ) return false;

	return $rs;
}

function setLiveFeedViewersToZero($livefeedids) {
global $jconf, $debug, $db, $app, $myjobid;

	$db = db_maintain();

    $in = "";
    
    if ( !empty($livefeedids) ) $in = " AND lf.id NOT IN (" . implode(", ", $livefeedids) . ")";

	$query = "
        UPDATE
            livefeeds AS lf
        SET
            lf.currentviewers = 0
        WHERE
            lf.currentviewers > 0 " . $in;
    
	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed. " . trim($query), $sendmail = false);
		return false;
	}

	return $rs->RecordCount();
}

?>
