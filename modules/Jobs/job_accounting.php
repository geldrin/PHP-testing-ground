<?php

// Generate given number of Videosquare users with random username, password and validated status

define('BASE_PATH',	realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

include_once(BASE_PATH . 'libraries/Springboard/Application/Cli.php');

// Utils
include_once(BASE_PATH . 'modules/Jobs/job_utils_base.php');

set_time_limit(0);
clearstatcache();

date_default_timezone_set('Europe/Budapest');

// Init
$app = new Springboard\Application\Cli(BASE_PATH, PRODUCTION);

// Load jobs configuration file
$app->loadConfig('modules/Jobs/config_jobs.php');
$jconf = $app->config['config_jobs'];
$myjobid = $jconf['jobid_acc'];

// Log related init
$debug = Springboard\Debug::getInstance();
$debug->log($jconf['log_dir'], $myjobid . ".log", "Accounting job started", $sendmail = false);

// Exit if any STOP file appears
if ( is_file( $app->config['datapath'] . 'jobs/job_accounting.stop' ) or is_file( $app->config['datapath'] . 'jobs/all.stop' ) ) exit;

// Contract data: should be retrived from DB later, when contract description database is implemented.
include_once('subscriber_descriptor.php');

// Look current month?
$iscurrentmonth = false;
if ( $argc >= 2 ) {
    switch ( $argv[1] ) {
        case "-currentmonth":
            $iscurrentmonth = true;
            break;
        case "-help":
            echo "-currentmonth: print current month, instead of last month\n";
            exit;
    }
}

// Establish database connection
$db = null;
$db = db_maintain();

$accounting_log  = "NODE: " . $app->config['node_sourceip'] . "\n";
$accounting_log .= "SITE: " . $app->config['baseuri'] . "\n";
$accounting_log .= "JOB: " . $myjobid . "\n\n";

for ($i = 0; $i < count($org_contracts); $i++ ) {

	// Identify previous month
	$this_year = date("Y");
	$this_month = date("n");

    if ( !$iscurrentmonth ) {
        if ( $this_month == 1 ) {
            $year = $this_year - 1;			
            $month = 12;
        } else {
            $year = $this_year;
            $month = $this_month - 1;
        }
    } else {
        $year = $this_year;
        $month = $this_month; 
    }
    
	$month_days = cal_days_in_month(CAL_GREGORIAN, $month, $year);

	$firstloggedin_enddate = $year . "-" . sprintf("%02d", $month) . "-" . $month_days . " 23:59:59";

	if ( !empty($org_contracts[$i]['listfromdate']) ) {

		if ( !verifyDate($org_contracts[$i]['listfromdate']) ) {
			$accounting_log .= "ERROR: invalid date format\n" . print_r($org_contracts[$i], true) . "\n\n";
			continue;
		}

		$firstloggedin_startdate = $org_contracts[$i]['listfromdate'];

	} else {

		$firstloggedin_startdate = $year . "-" . sprintf("%02d", $month) . "-01 00:00:00";

	}

	$query = "
		SELECT
			u.id,
			u.email,
			u.firstloggedin,
			GROUP_CONCAT(d.name SEPARATOR ';') as deps
		FROM
			users as u
		LEFT JOIN
			users_departments as ud
		ON
			u.id = ud.userid
		LEFT JOIN
			departments as d
		ON
			ud.departmentid = d.id
		WHERE
			u.organizationid = " . $org_contracts[$i]['orgid'] . " AND
			u.isusergenerated = 1 AND
			u.firstloggedin > '" . $firstloggedin_startdate . "' AND
			u.firstloggedin < '" . $firstloggedin_enddate . "' AND
			u.email REGEXP '^felh[\d]+@.+'
		GROUP BY
			u.id
		ORDER BY
			firstloggedin";
			
	unset($users);
	
	try {
		$users = $db->Execute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed.\n" . trim($query) . "\n" . $err . "\n", $sendmail = true);
		exit -1;
	}
	
	// User exist in DB, regenerate
	if ( $users->RecordCount() < 1 ) {
		continue;
	}
	
	// Print users to log
	$accounting_log .= "Client: " . $org_contracts[$i]['name'] . " (" . $org_contracts[$i]['orgid'] . "):\n";
	$accounting_log .= "Accounting period: " . $firstloggedin_startdate . "-" . $firstloggedin_enddate . "\n";
	$accounting_log .= "Per user price: " . $org_contracts[$i]['price_peruser'] . $org_contracts[$i]['currency'] . "\n\n";

	$accounting_log .= "User ID;Username;Activated;Group1;Group2;Group3;...\n";

	$users_num = 0;
	while ( !$users->EOF ) {

		$user = array();
		$user = $users->fields;

		$accounting_log .= $user['id'] . ";" . $user['email'] . ";" . $user['firstloggedin'] . ";" . $user['deps'] . "\n";

		$users_num++;
		$users->MoveNext();
	}

	$accounting_log .= "Summary;" . $users_num . ";" . $users_num * $org_contracts[$i]['price_peruser'] . $org_contracts[$i]['currency'] . "\n\n";

}

$email = "info@videosqr.com";
$queue = $app->bootstrap->getMailqueue();
$queue->instant = 1;
$queue->put($email, null, "Videosquare accounting information", $accounting_log, false, 'text/plain; charset="UTF-8"');

$debug->log($jconf['log_dir'], ($myjobid . ".log"), "Accounting information:\n\n" . $accounting_log, $sendmail = false);

exit;

function verifyDate($date) {
    return (DateTime::createFromFormat('Y-m-d H:i:s', $date) !== false);
}

?>
