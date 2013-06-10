<?php

// Generate given number of Videosquare users with random username, password and validated status

define('BASE_PATH',	realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');

set_time_limit(0);

$iscommit = FALSE;

// Init
$app = new Springboard\Application\Cli(BASE_PATH, PRODUCTION);

// Users base data
// Organization ID = Conforg (ID: 200)
$org_id = 200;
// Department ID
$org_dep_id = 57;
// How many users?
$user_num = 100;
// Single login: forced?
$issingleloginenforced = 1;
// Presence check: forced?
$ispresencecheckforced = 1;

// User name format and length: prefix + random number @ suffix
$user_nameprefix = "felh";
$user_namesuffix_length = 4;
$user_nametermination = "conforg.hu";
$pass_length = 8;

$users = array();

// Establish database connection
try {
	$db = $app->bootstrap->getAdoDB();
} catch (exception $err) {
	// Send mail alert, sleep for 15 minutes
	echo "[ERROR] No connection to DB (getAdoDB() failed). Error message:\n" . $err . "\n";
	exit -1;
}

$org_dep_name = query_department($org_dep_id);

echo "-= VIDEOSQUARE User Generator Script =-\n";

$msg  = "# Number of users to generate: " . $user_num . "\n";
$msg .= "# Username prefix: " . $user_nameprefix . "\n";
$msg .= "# Username number length: " . $user_namesuffix_length . "\n";
$msg .= "# Username suffix: " . $user_nametermination . "\n";
$msg .= "# Password length: " . $pass_length . "\n";
$msg .= "# Org ID: " . $org_id . "\n";
$msg .= "# Org department ID: " . $org_dep_id . " (" . $org_dep_name . ")\n";

$msg .= "# COMMIT: " . ($iscommit?"YES":"NO, TEST ONLY") . "\n";

echo $msg;

// Open TXT (CSV) file for user data
$out_file = "vsq_users.txt";
if ( file_exists($out_file) ) {
	if ( !is_writable($out_file) ) {
		echo "[ERROR]: Cannot write output file\n";
		exit -1;
	}
}
$fh = fopen($out_file, 'a');
$msg .= "# User data added: " . date("Y-m-d H:i:s") . " * COMMITTED: " . ($iscommit?"YES":"NO") . "\n";
$msg .= "No,Username,Password\n";
fwrite($fh, $msg);

$encryption = $app->bootstrap->getEncryption();
$usersdb = $app->bootstrap->getModel('users');

$users_added = 0;

for ( $i = 1; $i <= $user_num; $i++ ) {

	// Generate a locally unique username
	$user_isunique = FALSE;
	while ( !$user_isunique ) {

		$username = $user_nameprefix . sprintf("%04d", rand(1, 9999));

		// Regenerate until local uniqueness is granted
		if ( !empty($users[$username]) ) {
			echo "Username exists. Regenerate.\n";
			continue;
		}

		$query = "
			SELECT
				id,
				email
			FROM
				users
			WHERE
				email LIKE '%" . $username . "%'
		";

//echo $query . "\n";

		try {
			$rs = $db->Execute($query);
		} catch (exception $err) {
			echo "[ERROR] SQL query failed.\n" . trim($query) . "\n" . $err . "\n";
			exit -1;
		}

		// User exist in DB, regenerate
		if ( $rs->RecordCount() >= 1 ) {
			echo "Username exists in DB. Regenerate.\n";
			continue;
		}

		$user_isunique = TRUE;

		$users[$username] = array();
		$pwd = generatePassword($pass_length);
		$users[$username]['username'] = $username . "@" . $user_nametermination;
		$users[$username]['password'] = $pwd;
		$users[$username]['hash'] = $encryption->getHash($pwd);

		$date = date("Y-m-d H:i:s");

		$values = Array(
			'nickname'			=> $username,
			'email'				=> $users[$username]['username'],
			'namefirst'			=> $username,
			'namelast'			=> $user_nametermination,
			'nameformat'		=> "straight",
			'organizationid'	=> $org_id,
			'isadmin'			=> 0,
			'isclientadmin'		=> 0,
			'iseditor'			=> 0,
			'isnewseditor'		=> 0,
			'isuploader'		=> 0,
			'isliveadmin'		=> 0,
			'timestamp'			=> $date,
			'lastloggedin'		=> null,
			'language'			=> "hu",
			'newsletter'		=> 0,
			'password'			=> $users[$username]['hash'],
			'browser'			=> "(diag information was not posted)",
			'validationcode'	=> "123456",
			'disabled'			=> 0,
			'isapienabled'		=> 0,
			'issingleloginenforced'	=> $issingleloginenforced,
			'ispresencecheckforced'	=> $ispresencecheckforced
		);

//var_dump($values);

		echo " Adding user: " . $users[$username]['username'] . "\n";

		$userid = 0;
		if ( $iscommit ) {

			try {
				$usersdb->insert($values);
			} catch (exception $err) {
				echo "[ERROR] Cannot add record to users DB.\n" . $err . "\nInput data:\n";
				var_dump($values);
				exit -1;
			}

			$userid = $usersdb->id;

			$userdepid = insert_users_deps($userid, $org_dep_id);
		}


		$data_write = $i . "," . $users[$username]['username'] . "," . $users[$username]['password'] . "\n";
		fwrite($fh, $data_write);

		$users_added++;
	}

}

echo "Users added: " . $users_added . "\n";

fclose($fh);

exit;

function insert_users_deps($userid, $depid) {
 global $db, $iscommit; 

	// Insert HU string
	$query = "
		INSERT INTO
			users_departments (userid, departmentid)
		VALUES(" . $userid . ", " . $depid . ")";

//echo $query . "\n";

	$id = 0;
	if ( $iscommit ) {

		try {
			$rs = $db->Execute($query);
		} catch (exception $err) {
			echo "[ERROR] SQL query failed.\n" . trim($query) . "\n" . $err . "\n";
			exit -1;
		}

		$id = $db->Insert_ID();

	}

	return $id;
}

function generatePassword($length = 8) {

	// start with a blank password
	$password = "";

	// define possible characters - any character in this string can be
	// picked for use in the password, so if you want to put vowels back in
	// or add special characters such as exclamation marks, this is where
	// you should do it
	$possible = "2346789bcdfghjkmnpqrtvwxyzBCDFGHJKLMNPQRTVWXYZ";

	// we refer to the length of $possible a few times, so let's grab it now
	$maxlength = strlen($possible);

	// check for length overflow and truncate if necessary
	if ($length > $maxlength) {
	  $length = $maxlength;
	}

	// set up a counter for how many characters are in the password so far
	$i = 0; 

	$is_number = FALSE;
	$is_low = FALSE;
	$is_up = FALSE;

	// add random characters to $password until $length is reached
	while ($i < $length) { 

		// pick a random character from the possible ones
		$char = substr($possible, mt_rand(0, $maxlength-1), 1);

		// have we already used this character in $password?
		if (!strstr($password, $char)) { 

			// Is at least one number is included?
			if ( is_numeric($char) ) {
				$is_number = TRUE;
			} else {
				// At the last char, but not number yet
				if ( ( $is_number == FALSE ) and ( $i == ($length - 1) ) ) {
//					echo "\nNo number, add one\n";
					$char = mt_rand(2,9);
					$is_number = TRUE;
				}
			}

			// no, so it's OK to add it onto the end of whatever we've already got...
			$password .= $char;
			// ... and increase the counter by one
			$i++;
		}

	}

	return $password;

}

function query_department($department_id) {
 global $db;

	$query = "
		SELECT
			id,
			name
		FROM
			departments
		WHERE
			id = " . $department_id;

//echo $query . "\n";

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		echo "[ERROR] SQL query failed.\n" . trim($query) . "\n" . $err . "\n";
		exit -1;
	}

	// User exist in DB, regenerate
	if ( $rs->RecordCount() < 1 ) {
		echo "[ERROR] Department id = " . $department_id . " does not exist.\n";
		exit -1;
	}

	$department = $rs->fields;
	
	return $department['name'];
}

?>
