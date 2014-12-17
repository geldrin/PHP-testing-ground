<?php

// Subscriber reports

define('BASE_PATH', realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

include_once(BASE_PATH . 'libraries/Springboard/Application/Cli.php');

// Utils
include_once(BASE_PATH . 'modules/Jobs/job_utils_base.php');

set_time_limit(0);
clearstatcache();

// Init
$app = new Springboard\Application\Cli(BASE_PATH, PRODUCTION);

// Load jobs configuration file
$app->loadConfig('modules/Jobs/config_jobs.php');
$jconf = $app->config['config_jobs'];
//$myjobid = $jconf['jobid_subscriber_reports'];
$myjobid = "job_subscriber_reports";

// Log related init
$debug = Springboard\Debug::getInstance();
$debug->log($jconf['log_dir'], $myjobid . ".log", "Subscriber reports job started", $sendmail = false);

// Exit if any STOP file appears
if ( is_file( $app->config['datapath'] . 'jobs/' . $myjobid . '.stop' ) or is_file( $app->config['datapath'] . 'jobs/all.stop' ) ) exit;

// Establish database connection
$db = null;
$db = db_maintain();

// Is organization specified on command line?
$organizationid = 0;
if ( $argc >= 2 ) {
    if ( !is_numeric($argv[1]) ) {
        echo "[ERROR] organization ID is specified but not numeric\n";
        exit -1;
    }
    $organizationid = $argv[1];
}

// Get organizations with valid contracts
$org_contracts = getOrganizationsContracts($organizationid);
if ( $org_contracts === false ) {
    $debug->log($jconf['log_dir'], $myjobid . ".log", "[WARN] No organization with valid contract found. Exiting.", $sendmail = false);
    exit;
}

// # Intervals calculations
// Interval for users completed
$interval_start = date('Y-m-d', strtotime(' -1 day'));
$interval_end = date('Y-m-d', strtotime(' -1 day'));
$start_date = $interval_start . " 00:00:00";
$end_date = $interval_end . " 23:59:59";
$start_date_ts = strtotime($start_date);
$end_date_ts = strtotime($end_date);
$debug->log($jconf['log_dir'], $myjobid . ".log", "[INFO] Checking for period: " . $start_date . " - " . $end_date, $sendmail = false);
// Interval for users to be disabled
$user_disable_interval = 24 * 3600 * 7;     // week

// CSV legend
$legend = "Username;Recording ID;Length;Session start;Session end;Session last position;Session duration;Watched %";

while ( !$org_contracts->EOF ) {

    $org = array();
    $org = $org_contracts->fields;
    
    $query = "
        SELECT
            u.id,
            u.email,
            rvs.recordingid,
            r.masterlength,
            rvs.positionfrom,
            rvs.positionuntil,
            rvs.timestampfrom,
            rvs.timestampuntil,
            TIMESTAMPDIFF(SECOND, rvs.timestampfrom, rvs.timestampuntil) AS sessionduration,
            o.elearningcoursecriteria,
            ROUND( rvs.positionuntil * 100 / r.masterlength, 2) AS sessionpositionpercent,
            ud.departmentid,
            u.firstloggedin,
            u.timestampdisabledafter
        FROM
            users AS u,
            recording_view_sessions AS rvs,
            recordings AS r,
            organizations AS o,
            users_departments AS ud,
            access AS a
        WHERE
            o.id = " . $org['id'] . " AND
            u.isusergenerated = 1 AND
            u.disabled = 0 AND
            u.organizationid = o.id AND
            ( u.timestampdisabledafter IS NULL OR u.timestampdisabledafter > '" . $end_date . "' ) AND
            u.id = rvs.userid AND
            rvs.recordingid = r.id AND
            u.id = ud.userid AND
            ud.departmentid = a.departmentid AND
            a.recordingid = r.id AND
            r.isseekbardisabled = 1 AND
            r.approvalstatus = 'approved' AND
            r.accesstype = 'departmentsorgroups' AND
            r.status = '" . $jconf['dbstatus_copystorage_ok'] . "'
        ORDER BY
            u.id,
            r.id,
            ud.departmentid,
            rvs.timestampuntil
    ";
    
    unset($users_sessions);
    
    try {
        $users_sessions = $db->Execute($query);
    } catch (exception $err) {
        $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed.\n" . trim($query) . "\n" . $err . "\n", $sendmail = true);
        exit -1;
    }

    $users_summary = array();
    $deps = array();
	while ( !$users_sessions->EOF ) {

        $user = array();
		$user = $users_sessions->fields;
        
        $idx = $user['email'] . "_" . $user['departmentid'];
        
        // List department recordings (if empty)
        if ( !isset($deps[$user['departmentid']]) ) {
        
            //$deps[$user['departmentid']] = array();
        
            $dep_recordings = getDepartmentRecordings($user['departmentid']);
            while ( !$dep_recordings->EOF ) {

                $dep_rec = array();
                $dep_rec = $dep_recordings->fields;

                //var_dump($dep_rec);
                //exit;
                if ( !isset($deps[$user['departmentid']]) ) {
                    $deps[$user['departmentid']] = array();
                    $deps[$user['departmentid']]['name'] = $dep_rec['name'];
                    $deps[$user['departmentid']]['recordings'] = array();
                    $deps[$user['departmentid']]['count'] = 1;
                } else {
                    $deps[$user['departmentid']]['count']++;
                }
                
                if ( isset($deps[$user['departmentid']]['recordings'][$dep_rec['recordingid']]) ) {
                    echo "kaki2\n";
                } else {
                    $deps[$user['departmentid']]['recordings'][$dep_rec['recordingid']] = $dep_rec['title'];
                }

                $dep_recordings->MoveNext();
            }

        }
        
        // Init per user/dep info array
        if ( !isset($users_summary[$idx]) ) {
            $users_summary[$idx]['departmentid'] = $user['departmentid'];
            $users_summary[$idx]['email'] = $user['email'];
            // Frist login for user
            $users_summary[$idx]['firstloggedin'] = $user['firstloggedin'];
            // Validity time for user
            $users_summary[$idx]['timestampdisabledafter'] = $user['timestampdisabledafter'];
            // Has the course been completed?
            $users_summary[$idx]['iscoursecompleted'] = 0;
            // Recordings in this course
            $users_summary[$idx]['coursecount'] = $deps[$user['departmentid']]['count'];
            // Completed session information
            $users_summary[$idx]['session_info_completed'] = array();
            // Uncompleted session information
            $users_summary[$idx]['session_info_notcompleted'] = array();
            // How many recordings has the user finished watching?
            $users_summary[$idx]['recordings_finished'] = 0;
            // Report it? Yes, if a session finished between start/end date
            $users_summary[$idx]['tobereported'] = 0;
        }
        
        // Is recording playback completed?
        if ( $user['sessionpositionpercent'] > $org['elearningcoursecriteria'] ) {
            // Completed
            $users_summary[$idx]['recordings_finished']++;
            $users_summary[$idx]['session_info_completed'][$user['recordingid']] = userRecordingViewSessionCSV($user);
            // Completed at least one recording between start and end time
            $timestampuntil = strtotime($user['timestampuntil']);
            if ( ( $timestampuntil >= $start_date_ts ) and ( $timestampuntil <= $end_date_ts ) ) $users_summary[$idx]['tobereported']++;
        } else {
            // Not completed
            $users_summary[$idx]['session_info_notcompleted'][$user['recordingid']] = userRecordingViewSessionCSV($user);
        }
        
        if ( $users_summary[$idx]['recordings_finished'] == $deps[$user['departmentid']]['count'] ) {
            $users_summary[$idx]['iscoursecompleted'] = 1;
        }
        
        $users_sessions->MoveNext();
    }

    //// Compile e-mail summary

    // USERS FINISHED COURSE (at least 1x recording watched between start and end period)
    $mail_body  = "";
    foreach ($users_summary as $key => $user_sum) {

        // Filter iscoursecompleted users (watched all recordings in department)
        if ( ( $user_sum['iscoursecompleted'] == 1 ) and ( $user_sum['tobereported'] > 0 ) ) {
            $mail_body .= $user_sum['email'] . " / " . $deps[$user_sum['departmentid']]['name'] . ":\n";
            
            foreach ($user_sum['session_info_completed'] as $session_info_key => $session_info) {
            
                $mail_body .= $session_info . "\n";
            
            }
            
            $mail_body .= "\n";
        }

    }
    
    if ( !empty($mail_body) ) $mail_body = "*** Users completed accredited courses between " . $start_date . " - " . $end_date . " ***\n\n" . $mail_body . "\n";
    
    // USERS NOT YET FINISHED COURSE AND 1 WEEK TO GO
    $mail_body2 = "";
    foreach ($users_summary as $key => $user_sum) {

        // Filter NOT iscoursecompleted users (watched all recordings in department)
        if ( $user_sum['iscoursecompleted'] == 0 ) {

            $disabletime = strtotime($user_sum['timestampdisabledafter']);
            if ( $disabletime <= ( time() + $user_disable_interval ) ) {
        
                $mail_body2 .= $user_sum['email'] . " / " . $deps[$user_sum['departmentid']]['name'] . ":\n";

                // Completed sessions: log them to hide uncompleted for the same recording
                $finished_log = array();
                foreach ($user_sum['session_info_completed'] as $session_info_key => $session_info) {
                    $mail_body2 .= $session_info . "\n";
                    array_push($finished_log, $session_info_key);
                }
                
                foreach ($user_sum['session_info_notcompleted'] as $session_info_key => $session_info) {
                    if ( array_search($session_info_key, $finished_log) === false ) $mail_body2 .= $session_info . "\n";
                }

                if ( empty($user_sum['timestampdisabledafter']) ) {
                    $user_sum['timestampdisabledafter'] = date("Y-m-d H:i:s", strtotime($user_sum['firstloggedin']) + 30 * 24 * 3600);
                }
                $mail_body2 .= "Valid;" . $user_sum['firstloggedin'] . ";" . $user_sum['timestampdisabledafter'] . "\n";
                $mail_body2 .= "Finished/All videos;" . $user_sum['recordings_finished'] . ";" . $deps[$user_sum['departmentid']]['count'] . "\n";
                $mail_body2 .= "\n";
            }

        }

    }

    if ( !empty($mail_body2) ) $mail_body2 = "*** Users NOT completed accredited courses and will be disabled in a " . round($user_disable_interval /3600 / 24 ) . " days from now. ***\n\n" . $mail_body2 . "\n";
    
    if ( !empty($mail_body) or  !empty($mail_body2) ) {
    
        // Subject
        $subject = "Accredited courses daily report for period: " . $start_date . " - " . $end_date;
    
        // Header
        $header  = "Subscriber: " . $org['name'] . " (id: " . $org['id'] . ")\n";
        $header .= "Domain: " . $org['domain'] . "\n";
        $header .= "E-mail: " . $org['reportemailaddresses'] . "\n";
        $header .= "Period: " . $start_date . " - " . $end_date . "\n";
        $header .= "Course criteria: " . $org['elearningcoursecriteria'] . "%\n";
        
        // Legend
        $mail = $header . "\n" . $legend . "\n\n";

        if ( !empty($mail_body) )  $mail .= $mail_body;
        if ( !empty($mail_body2) ) $mail .= $mail_body2;

        // Send HTML mail
        $mail_list = explode(";", $org['reportemailaddresses']);
        $html_mail = nl2br($mail, true);
        for ($q = 0; $q < count($mail_list); $q++) {
            if (!filter_var($mail_list[$q], FILTER_VALIDATE_EMAIL)) {
                $debug->log($jconf['log_dir'], ($myjobid . ".log"), "[ERROR] Mail address is not valid in organization_contracts DB table: " . $org['reportemailaddresses'], $sendmail = false);
                continue;
            }
            
            $queue = $app->bootstrap->getMailqueue();
            $queue->instant = 1;
            $queue->sendHTMLEmail($mail_list[$q], $subject, $html_mail);
        }
        
        // Log outgoing message
        $debug->log($jconf['log_dir'], ($myjobid . ".log"), "[INFO] Information sent to subscriber: " . $org['reportemailaddresses'] . "\n\n" . $mail, $sendmail = false);

    }
    
    $org_contracts->MoveNext();
}

exit;

function getOrganizationsContracts($organizationid) {
global $db, $debug, $app, $myjobid;

    $sql_org_filter = "";
    if ( $organizationid > 0 ) {
        $sql_org_filter = " AND o.id = " . $organizationid;
    }

    $query = "
        SELECT
            o.id,
            o.name,
            o.issubscriber,
            o.domain,
            o.supportemail,
            o.elearningcoursecriteria,
            o.iselearningcoursesessionbound,
            oc.identifier,
            oc.startdate,
            oc.enddate,
            oc.reportemailaddresses
        FROM
            organizations AS o,
            organizations_contracts AS oc
        WHERE
            o.issubscriber = 1 AND
            oc.organizationid = o.id AND
            oc.disabled = 0 AND
            oc.isreportenabled = 1 AND
            oc.reportemailaddresses IS NOT NULL AND
            oc.startdate <= NOW() AND ( oc.enddate >= NOW() OR oc.enddate IS NULL)"
            . $sql_org_filter . "
        ORDER BY
            o.id
    ";
    
    unset($org_contracts);
    
    try {
        $org_contracts = $db->Execute($query);
    } catch (exception $err) {
        $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed.\n" . trim($query) . "\n" . $err . "\n", $sendmail = true);
        exit -1;
    }
    
    if ( $org_contracts->RecordCount() < 1 ) {
        return false;
    }

    return $org_contracts;
}

function userRecordingViewSessionCSV($user) {

    $position_percent = round(( 100 / $user['masterlength'] ) * $user['positionuntil'], 2);
    if ( $position_percent >= 100 ) $position_percent = 100;
    if ( $user['positionuntil'] > $user['masterlength'] ) $user['positionuntil'] = $user['masterlength'];

    $session_info = $user['email'] . ";" . $user['recordingid'] . ";" . secs2hms($user['masterlength']) . ";" . $user['timestampfrom'] . ";" . $user['timestampuntil'] . ";" . secs2hms($user['positionuntil']) . ";" . secs2hms($user['sessionduration']) . ";" . $position_percent;

    return $session_info;
}

function getDepartmentRecordings($depid) {
global $db, $debug, $app, $myjobid, $jconf;

    $query = "
        SELECT
            d.id,
            d.name,
            a.recordingid,
            r.title
        FROM
            departments AS d,
            access AS a,
            recordings AS r
        WHERE
            d.id = " . $depid . " AND
            d.id = a.departmentid AND
            a.recordingid IS NOT NULL AND
            a.recordingid = r.id AND
            r.isseekbardisabled = 1 AND
            r.approvalstatus = 'approved' AND
            r.accesstype = 'departmentsorgroups' AND
            r.status = '" . $jconf['dbstatus_copystorage_ok'] . "'
    ";

    unset($dep_recordings);
    
    try {
        $dep_recordings = $db->Execute($query);
    } catch (exception $err) {
        $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed.\n" . trim($query) . "\n" . $err . "\n", $sendmail = true);
        exit -1;
    }
    
    if ( $dep_recordings->RecordCount() < 1 ) {
        return false;
    }

    return $dep_recordings;
}

?>
