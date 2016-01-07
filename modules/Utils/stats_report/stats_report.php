<?php
// Job: system health

define('BASE_PATH', realpath( __DIR__ . '/../../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');

// Utils
include_once('../../../modules/Jobs/job_utils_base.php');
include_once('../../../modules/Jobs/job_utils_log.php');

set_time_limit(0);
clearstatcache();

// Init
$app = new Springboard\Application\Cli(BASE_PATH, PRODUCTION);

// Load jobs configuration file
$app->loadConfig('modules/Jobs/config_jobs.php');
$jconf = $app->config['config_jobs'];
$jobid = "stats_report";

// Log related init
$thisjobstarted = time();
$debug = Springboard\Debug::getInstance();
$debug->log($jconf['log_dir'], $jobid . ".log", "*************************** Stats Report started ***************************", $sendmail = false);

// DB connection
$db = db_maintain();

$vsq_epoch = strtotime("2013-10-01 00:00:00");

if ( $argc >= 2 ) {
    if ( is_numeric($argv[1]) ) {
        $organizationid = $argv[1];
    } else {
        exit;
    }
} else {
    echo "[HELP] Provide organization ID as parameter. Exiting...\n";
    exit;
}

// Basics
$rs = getRecordingsSummary($organizationid);
$csv = "";
foreach ($rs[0] as $key => $value) {
    $csv .= $key . ";" . $value . "\n";
}
$fp = fopen('basic_info.csv', 'w');
fputs($fp, $csv);
fclose($fp);

// Top 50 recordings
$fp = fopen('top_recordings_50.csv', 'w');
fputcsv($fp, array('Recording ID', 'Title', 'Recorded date/time', 'Number of views', 'URL'));
$recs = getRecordingsTopN($organizationid, 50);
foreach ($recs as $rec) {
    fputcsv($fp, $rec);
}
fclose($fp);


// Recordings playback per month
$now = time();
$year = date("Y", $vsq_epoch);
$month = date("n", $vsq_epoch);
$fp = fopen('recordings_view_by_date.csv', 'w');
fputcsv($fp, array('Year/month', 'Recordings view', 'View duration [sec]', 'View duration'));
while ( $year <= date("Y") ) {

    for ( $i = $month; $i <= 12; $i++ ) {
        $month_days = cal_days_in_month(CAL_GREGORIAN, $i, $year);
        $start_date = $year . "-" . sprintf("%02d", $i) . "-01 00:00:00";
        $end_date = $year . "-" . sprintf("%02d", $i) . "-" . sprintf("%02d", $month_days) . " 23:59:59";
        $recs_view_by_date = getRecordingsAllPlaybacksByDate($start_date, $end_date);
        //var_dump($recs_view_by_date);
        $csv_line = array(
            'date'              => date("Y. M.", strtotime($start_date)),
            'numviewsessions'   => $recs_view_by_date['numviewsessions'],
            'duration'          => $recs_view_by_date['duration'],
            'duration_hhmmss'   => secs2hms($recs_view_by_date['duration'])
        );
        fputcsv($fp, $csv_line);
    }
    
    $year++;
    $month = 1;
}
fclose($fp);

// Live playback per month
$now = time();
$year = date("Y", $vsq_epoch);
$month = date("n", $vsq_epoch);
$fp = fopen('live_view_by_date.csv', 'w');
fputcsv($fp, array('Year/month', 'Live views', 'View duration [sec]' , 'View duration'));
while ( $year <= date("Y") ) {

    for ( $i = $month; $i <= 12; $i++ ) {
        $month_days = cal_days_in_month(CAL_GREGORIAN, $i, $year);
        $start_date = $year . "-" . sprintf("%02d", $i) . "-01 00:00:00";
        $end_date = $year . "-" . sprintf("%02d", $i) . "-" . sprintf("%02d", $month_days) . " 23:59:59";
        $live_view_by_date = getLiveAllPlaybacksByDate($start_date, $end_date);
        //var_dump($recs_view_by_date);
        $csv_line = array(
            'date'              => date("Y. M.", strtotime($start_date)),
            'numviewsessions'   => $live_view_by_date['numviewsessions'],
            'duration'          => $live_view_by_date['duration'],
            'duration_hhmmss'   => secs2hms($live_view_by_date['duration'])
        );
        fputcsv($fp, $csv_line);
    }
    
    $year++;
    $month = 1;
}
fclose($fp);

// Livefeed concurrent users
$fp = fopen('livefeed_views.csv', 'w');
fputcsv($fp, array('Channel title', 'Channel ID', 'Date', 'Livefeed' , 'Livefeed ID', 'Number of views', 'Number of all views', 'All playback duration', 'Max. concurrent viewers'));

$livefeeds = getLiveFeeds($organizationid);
if ( $livefeeds !== false ) {
 
    while ( !$livefeeds->EOF ) {
    
        $livefeed = $livefeeds->fields;
    
        $csv_line = array(
            0 => $livefeed['channeltitle'],
            1 => $livefeed['channelid'],
            2 => $livefeed['channelstarttimestamp'],
            3 => $livefeed['name'],
            4 => $livefeed['id'],
            5 => $livefeed['numberofviews']
        );
    
    //var_dump($livefeed);
        $viewers = getLiveFeedPlaybacksByID($livefeed['id']);
        if ( $viewers !== false ) {
            $csv_line[6] = $viewers['numberofallviewers'];
            $csv_line[7] = $viewers['allviewsduration'];
        } else {
            $csv_line[6] = 0;
            $csv_line[7] = 0;
        }

        $viewers = getLiveFeedMaxConcurrentViewers($livefeed['id']);
        
        if ( $viewers !== false ) {
            $csv_line[8] = $viewers['numberofconcurrentviewers'];
        } else {
            $csv_line[8] = 0;
        }
        
        var_dump($csv_line);  
     
        fputcsv($fp, $csv_line);
        
        $livefeeds->MoveNext();
    }
}
fclose($fp);

exit;

function getRecordingsTopN($organizationid, $limit) {
global $db, $jobid, $debug, $jconf;

	$query = "
        SELECT
            r.id,
            r.title,
            r.recordedtimestamp,
            r.numberofviews,
            CONCAT('http://', o.domain, '/hu/recordings/details/', r.id)
        FROM
            recordings AS r,
            organizations AS o
        WHERE
            r.id > 0 AND
            r.organizationid = " . $organizationid . " AND
            o.id = " . $organizationid . "
        ORDER BY
            r.numberofviews DESC
        LIMIT " . $limit;

	try {
		$rs = $db->getArray($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $jobid . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
		return false;
	}

    // Check if any record returned
	if ( count($rs) < 1 ) return false;
        
    return $rs;
}

function getRecordingsSummary($organizationid) {
global $db, $jobid, $debug, $jconf;

	$query = "
        SELECT
            COUNT(r.id) AS numberofrecordings,
            SUM(r.numberofviews) AS numberofviews,
            SUM(r.masterdatasize) AS masterdatasize,
            SUM(recordingdatasize) AS recordingdatasize,
            SUM(r.masterlength) AS masterlength,
            SUM(r.contentmasterlength) AS contentmasterlength,
            ( SUM(r.masterlength) + SUM(r.contentmasterlength) ) AS allmasterlength
        FROM
            recordings AS r
        WHERE
            r.id > 0 AND
            r.organizationid = " . $organizationid;

	try {
		$rs = $db->getArray($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $jobid . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
		return false;
	}

    // Check if any record returned
	if ( count($rs) < 1 ) return false;
        
    return $rs;
}

function getRecordingsAllPlaybacksByDate($start_date, $end_date) {
global $db, $jobid, $debug, $jconf;

	$query = "
        SELECT
            COUNT(vso.id) AS numviewsessions,
            SUM(vso.positionuntil - vso.positionfrom) AS duration
        FROM
            view_statistics_ondemand AS vso
        WHERE
            vso.timestamp >= '" . $start_date . "' AND
            vso.timestamp <= '" . $end_date . "' AND
            (vso.positionuntil - vso.positionfrom) > 0";

	try {
		$rs = $db->getArray($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $jobid . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
		return false;
	}

    // Check if any record returned
	if ( count($rs) < 1 ) return false;
    
    if ( empty($rs[0]['duration']) ) $rs[0]['duration'] = 0;
    
    return $rs[0];
}

function getLiveAllPlaybacksByDate($start_date, $end_date) {
global $db, $jobid, $debug, $jconf;

	$query = "
        SELECT
            COUNT(vsl.viewsessionid) AS numviewsessions,
            SUM(TIMESTAMPDIFF(SECOND, vsl.timestampfrom, vsl.timestampuntil)) AS duration
        FROM
            view_statistics_live AS vsl
        WHERE
            vsl.timestampfrom > '" . $start_date . "' AND
            vsl.timestampuntil <= '" . $end_date . "' AND
            TIMESTAMPDIFF(SECOND, vsl.timestampfrom, vsl.timestampuntil) > 0";

	try {
		$rs = $db->getArray($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $jobid . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
		return false;
	}
    
    // Check if any record returned
	if ( count($rs) < 1 ) return false;
    
    if ( empty($rs[0]['duration']) ) $rs[0]['duration'] = 0;
    
    return $rs[0];
}

function getLiveFeedPlaybacksByID($livefeedid) {
global $db, $jobid, $debug, $jconf;

	$query = "
        SELECT
            COUNT(vsl.viewsessionid) AS numberofallviewers,
            SUM(TIMESTAMPDIFF(SECOND, vsl.timestampfrom, vsl.timestampuntil)) AS allviewsduration
        FROM
            view_statistics_live AS vsl
        WHERE
            vsl.livefeedid = " . $livefeedid . " AND
            TIMESTAMPDIFF(SECOND, vsl.timestampfrom, vsl.timestampuntil) > 0";

	try {
		$rs = $db->getArray($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $jobid . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
		return false;
	}
    
    // Check if any record returned
	if ( count($rs) < 1 ) return false;
    
    if ( empty($rs[0]['allviewsduration']) ) $rs[0]['allviewsduration'] = 0;
    
    return $rs[0];
}

function getLiveFeeds($organizationid) {
global $db, $jobid, $debug, $jconf;

	$query = "
        SELECT
            lf.id,
            lf.name,
            lf.numberofviews,
            c.id AS channelid,
            c.title AS channeltitle,
            c.starttimestamp AS channelstarttimestamp
        FROM
            livefeeds AS lf,
            channels AS c
        WHERE
            lf.organizationid = " . $organizationid . " AND
            lf.channelid = c.id
        ORDER BY
            c.starttimestamp";

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $jobid . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
		return false;
	}
    
    // Check if any record returned
	if ( $rs->RecordCount() < 1 ) return false;
    
    return $rs;
}

function getLiveFeedMaxConcurrentViewers($livefeedid) {
global $db, $jobid, $debug, $jconf;

	$query = "
        SELECT
            s5.timestamp AS concurrentviewerstimestamp,
            ( s5.numberofflashwin + s5.numberofflashmac + s5.numberofflashlinux + s5.numberofandroid + s5.numberofiphone + s5.numberofipad + s5.numberofunknown ) AS numberofconcurrentviewers
        FROM
            statistics_live_5min AS s5
        WHERE
            s5.livefeedid = " . $livefeedid . "
        ORDER BY
            numberofconcurrentviewers DESC
        LIMIT 1";

	try {
		$rs = $db->getArray($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $jobid . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
		return false;
	}
    
    // Check if any record returned
	if ( count($rs) < 1 ) return false;
    
    return $rs[0];
}

?>