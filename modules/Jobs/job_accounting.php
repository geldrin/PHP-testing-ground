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

// Init
$app = new Springboard\Application\Cli(BASE_PATH, PRODUCTION);

// Load jobs configuration file
$app->loadConfig('modules/Jobs/config_jobs.php');
$jconf = $app->config['config_jobs'];
$myjobid = $jconf['jobid_acc'];

// Log related init
$debug = Springboard\Debug::getInstance();
$debug->log($jconf['log_dir'], $myjobid . ".log", "Accounting job started", $sendmail = FALSE);

// Exit if any STOP file appears
if ( is_file( $app->config['datapath'] . 'jobs/job_accounting.stop' ) or is_file( $app->config['datapath'] . 'jobs/all.stop' ) ) exit;

// Contract data: should be retrived from DB later, when contract description database is implemented.
$org_contracts = array(
	0	=> array(
			'orgid' 		=> 200,
			'name'			=> "Conforg",
			'price_peruser'	=> 2000,
			'currency'		=> "HUF",
			'listfromdate'	=> null
		),
	1	=> array(
			'orgid' 		=> 222,
			'name'			=> "Infoszféra",
			'price_peruser'	=> 2000,
			'currency'		=> "HUF",
			'listfromdate'	=> "2013-12-01 00:00:00"
		),
	2	=> array(
			'orgid' 		=> 282,
			'name'			=> "IIR",
			'price_peruser'	=> 2000,
			'currency'		=> "HUF",
			'listfromdate'	=> "2013-11-01 00:00:00"
		)
);

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
	$this_year = 2014;
	$this_month = 2;

	if ( $this_month == 1 ) {
		$year = $this_year - 1;			
		$month = 12;
	} else {
		$year = $this_year;
		$month = $this_month - 1;
	}

	$month_days = date("t", time($year . "-" . $month . "-01"));

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
			id,
			email,
			firstloggedin
		FROM
			users
		WHERE
			organizationid = " . $org_contracts[$i]['orgid'] . " AND
			isusergenerated = 1 AND
			firstloggedin > \"" . $firstloggedin_startdate . "\" AND
			firstloggedin < \"" . $firstloggedin_enddate . "\"
		ORDER BY
			firstloggedin";

	unset($users);

	try {
		$users = $db->Execute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed.\n" . trim($query) . "\n" . $err . "\n", $sendmail = TRUE);
		exit -1;
	}

	// User exist in DB, regenerate
	if ( $users->RecordCount() < 1 ) {
//		echo "No activated users found for this organization\n";
		continue;
	}

	// Print users to log
	$accounting_log .= "Client: " . $org_contracts[$i]['name'] . " (" . $org_contracts[$i]['orgid'] . "):\n";
	$accounting_log .= "Accounting period: " . $firstloggedin_startdate . "-" . $firstloggedin_enddate . "\n";
	$accounting_log .= "Per user price: " . $org_contracts[$i]['price_peruser'] . $org_contracts[$i]['currency'] . "\n\n";

	$accounting_log .= "User ID;Username;Activated\n";

	$users_num = 0;
	while ( !$users->EOF ) {

		$user = array();
		$user = $users->fields;

		$accounting_log .= $user['id'] . ";" . $user['email'] . ";" . $user['firstloggedin'] . "\n";

		$users_num++;
		$users->MoveNext();
	}

	$accounting_log .= "Summary;" . $users_num . ";" . $users_num * $org_contracts[$i]['price_peruser'] . $org_contracts[$i]['currency'] . "\n\n";

}

// Send mail with accounting information
//$queue = $app->bootstrap->getMailqueue();
//$queue->sendHTMLEmail("andras.kovacs@videosquare.eu", "Videosquare accounting information", $accounting_log);

$email = "andras.kovacs@videosquare.eu";
$queue = $app->bootstrap->getMailqueue();
$queue->instant = 1;
$queue->put($email, null, "Videosquare accounting information", $accounting_log, false, 'text/plain; charset="UTF-8"');

$debug->log($jconf['log_dir'], ($myjobid . ".log"), "Accounting information:\n\n" . $accounting_log, $sendmail = false);

//echo $accounting_log . "\n";

function verifyDate($date) {
    return (DateTime::createFromFormat('Y-m-d H:i:s', $date) !== false);
}

?>
