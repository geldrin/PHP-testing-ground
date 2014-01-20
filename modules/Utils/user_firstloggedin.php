<?php

// Generate given number of Videosquare users with random username, password and validated status

define('BASE_PATH',	realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');

set_time_limit(0);

$iscommit = true;

// Init
$app = new Springboard\Application\Cli(BASE_PATH, PRODUCTION);

$users = array();

// Establish database connection
try {
	$db = $app->bootstrap->getAdoDB();
} catch (exception $err) {
	// Send mail alert, sleep for 15 minutes
	echo "[ERROR] No connection to DB (getAdoDB() failed). Error message:\n" . $err . "\n";
	exit -1;
}

$query = "
	SELECT
		id,
		email
	FROM
		users
	WHERE
		firstloggedin IS NULL
	ORDER BY
		id";

try {
	$users = $db->Execute($query);
} catch (exception $err) {
	echo "[ERROR] SQL query failed.\n" . trim($query) . "\n" . $err . "\n";
	exit -1;
}

// User exist in DB, regenerate
if ( $users->RecordCount() < 1 ) {
	echo "[ERROR] MySQL no results\n";
	exit -1;
}

$logs = array(
	0 => "2013-05-login.txt",
	1 => "2013-06-login.txt",
	2 => "2013-07-login.txt",
	3 => "2013-08-login.txt",
	4 => "2013-09-login.txt",
	5 => "2013-10-login.txt",
	6 => "2013-11-login.txt",
	7 => "2013-12-login.txt",
	8 => "2014-01-login.txt"
);

while ( !$users->EOF ) {

	$user = array();
	$user = $users->fields;

	echo $user['id'] . "," . $user['email'] . "\n";

	// grep it from logs
//	$command = "grep -h \"support@videosquare.eu\" ../../data/logs/201*-*-login.txt | head -n 1";
	$isfound = false;
	$i = 0;
	while ( $isfound != true ) {

		$command = "cd ../../data/logs ; cat " . $logs[$i] . " | grep \"" . $user['email'] . "\"";
//echo $command . "\n";
		$output = array();
		$err = 0;
		exec($command, $output, $err);

		if ( count($output) > 1 ) {
			$isfound = true;
			break;
		}

		$i++;
		if ( $i >= count($logs) ) break;
	}

	if ( $isfound == true ) {

		$res = $output[0];
		$tmp = explode("|", $res, 2);
		$tmp2 = explode(".", trim($tmp[0]), 2);

		$firstloggedin = $tmp2[0];
		
		if ( $iscommit ) {

			$query = "
				UPDATE
					users
				SET
					firstloggedin = \"" . $firstloggedin . "\"
				WHERE
					id = " . $user['id'];

//echo $query . "\n";

				try {
					$rs = $db->Execute($query);
				} catch (exception $err) {
					echo "[ERROR] SQL query failed.\n" . trim($query) . "\n" . $err . "\n";
					exit -1;
				}

		}

		echo "Found: " . $firstloggedin . "\n";

//echo $output[0] . "\n";

	} else {
		echo "Not found for this user\n";
	}

//var_dump($output);
//exit;
	$users->MoveNext();
}

exit;

?>
