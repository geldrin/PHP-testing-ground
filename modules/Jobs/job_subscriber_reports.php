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

// Get organizations with valid contracts
$org_contracts = getOrganizationsContracts();
if ( $org_contracts === false ) {
    $debug->log($jconf['log_dir'], $myjobid . ".log", "[WARN] No organization with valid contract found. Exiting.", $sendmail = false);
    exit;
}

// Previous day
$prev_day = date('Y-m-d', strtotime(' -1 day'));
$start_date = $prev_day . " 00:00:00";
$end_date = $prev_day . " 23:59:59";

// CSV legend
$legend = "Username;Recording ID;Length;Session start;Session end;Session last position;Session duration;Watched %";

for ($i = 0; $i < count($org_contracts); $i++ ) {

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
            ud.departmentid,
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
            rvs.timestampuntil >= '" . $start_date . "' AND
            rvs.timestampuntil <= '" . $end_date . "' AND
            rvs.recordingid = r.id AND
            rvs.positionuntil >= (( o.elearningcoursecriteria / 100 ) * r.masterlength) AND
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
    
    //echo $query . "\n";

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
                    $deps[$user['departmentid']]['count'] = 1;
                    $deps[$user['departmentid']]['recordings'] = array();
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
        
        // Add data to user
        if ( !isset($users_summary[$idx]) ) {
            $users_summary[$idx]['departmentid'] = $user['departmentid'];
            $users_summary[$idx]['email'] = $user['email'];
            $users_summary[$idx]['timestampdisabledafter'] = $user['timestampdisabledafter'];
            $users_summary[$idx]['confirmed'] = 0;
            $users_summary[$idx]['recordings_finished'] = 1;
            $users_summary[$idx]['session_info'] = array();
            $users_summary[$idx]['session_info'][$user['recordingid']] = userRecordingViewSessionCSV($user);
        } else {
            $users_summary[$idx]['recordings_finished']++;
            if ( isset($users_summary[$idx]['session_info'][$user['recordingid']]) ) echo "kaki!\n";
            $users_summary[$idx]['session_info'][$user['recordingid']] = userRecordingViewSessionCSV($user);
        }

        if ( $users_summary[$idx]['recordings_finished'] == $deps[$user['departmentid']]['count'] ) {
            $users_summary[$idx]['confirmed'] = 1;
        }
        
        $users_sessions->MoveNext();
    }

//var_dump($users_summary);

    // USERS FINISHED COURSE - Compile e-mail summary
    $mail_body  = "";
    foreach ($users_summary as $key => $user_sum) {

        // Filter confirmed users (watched all recordings in department)
        if ( $user_sum['confirmed'] == 1 ) {
            $mail_body .= $user_sum['email'] . " / " . $deps[$user_sum['departmentid']]['name'] . ":\n";
            
            foreach ($user_sum['session_info'] as $session_info_key => $session_info) {
            
                $mail_body .= $session_info . "\n";
            
            }
            
            $mail_body .= "\n";
        }

    }
    
    if ( !empty($mail_body) ) $mail_body = "Users completed accredited courses between " . $start_date . " - " . $end_date . "\n\n" . $mail_body . "\n";
    
    // USERS NOT YET FINISHED COURSE AND 1 WEEK TO GO - Compile e-mail summary
    $mail_body2 = "";
    foreach ($users_summary as $key => $user_sum) {

        // Filter NOT confirmed users (watched all recordings in department)
        if ( $user_sum['confirmed'] == 0 ) {

            $weekfromnow = time() + 24 * 3600 * 7 * 3;
            $disabletime = strtotime($user_sum['timestampdisabledafter']);
            if ( $disabletime <= $weekfromnow ) {
        
                $mail_body2 .= $user_sum['email'] . " / " . $deps[$user_sum['departmentid']]['name'] . ":\n";
                
                foreach ($user_sum['session_info'] as $session_info_key => $session_info) {
                    $mail_body2 .= $session_info . "\n";
                
                }

                $mail_body2 .= "Valid until;" . $user_sum['timestampdisabledafter'] . "\n";
                $mail_body2 .= "\n";
            }

        }

    }

    if ( !empty($mail_body2) ) $mail_body2 = "Users NOT completed accredited courses and will be disabled in a week.\n\n" . $mail_body2 . "\n";
    
    if ( !empty($mail_body) or  !empty($mail_body2) ) {
    
        // Subject
        $subject = "Accredited courses daily report for period: " . $start_date . " - " . $end_date;
    
        // Header
        $header  = "Subscriber: " . $org['name'] . " (id: " . $org['id'] . ")\n";
        $header .= "Domain: " . $org['domain'] . "\n";
        $header .= "E-mail: " . $org['supportemail'] . "\n";
        $header .= "Period: " . $start_date . " - " . $end_date . "\n";

        // Legend
        $mail = $header . "\n" . $legend . "\n\n";

        if ( !empty($mail_body) )  $mail .= $mail_body;
        if ( !empty($mail_body2) ) $mail .= $mail_body2;
        
        // HTML mail
        $html_mail = nl2br($mail, true);
        
        $email = $org['supportemail'];
//        $email = "andras.kovacs@videosqr.com";
        $queue = $app->bootstrap->getMailqueue();
        $queue->instant = 1;
        //$queue->put($email, null, $subject, $mail, false, 'text/plain; charset="UTF-8"');
        $queue->sendHTMLEmail($email, $subject, $html_mail);
        $debug->log($jconf['log_dir'], ($myjobid . ".log"), "Information sent to subscriber: " . $email . "\n\n" . $mail, $sendmail = false);
        
        // !!!
//        $queue->sendHTMLEmail("szepcsik.tunde@conforg.hu", $subject, $html_mail);

    }
    
    
    $org_contracts->MoveNext();
}

exit;

function getOrganizationsContracts() {
global $db, $debug, $app, $myjobid;

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
            oc.enddate
        FROM
            organizations AS o,
            organizations_contracts AS oc
        WHERE
            o.issubscriber = 1 AND
            o.id = 200 AND # !!! Conforg only - TEST !!!
            oc.organizationid = o.id AND
            oc.disabled = 0 AND
            oc.startdate <= NOW() AND ( oc.enddate >= NOW() OR oc.enddate IS NULL)
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
