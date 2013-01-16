
<?php

define('BASE_PATH',	realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');

set_time_limit(0);

$iscommit = TRUE;

// Init
$app = new Springboard\Application\Cli(BASE_PATH, PRODUCTION);

// Roles
// 1 = Előadó
// 2 = Műsorvezető
// 3 = Moderátor

$authors = array(
	// Vezetéknév, keresztnév, nénsorrend, pozíció, cég, recordingid, role

/*	array("Andrási", "Jánosné", "straight", "osztályvezető", "Nemzeti Adó- és Vámhivatal", 67, 1),
	array("Dr. Futó", "Gábor", "straight", "főtanácsos", "Budapest Főváros Kormányhivatala Nyugdíjbiztosítási Igazgatóság", 189, 1),
	array("Dr. Kovács", "Ferenc", "straight", "elnökhelyettesi tanácsadó", "Nemzeti Adó- és Vámhivatal", 190, 1),
	array("Ácsné Molnár", "Judit", "straight", "szakmai főtanácsadó", "Nemzeti Adó- és Vámhivatal", 191, 1),
*/

	array("Kelemen", "István", "straight", "vezető tanácsadó", "Green Tax Consulting Kft.", 193, 1),
	array("Mészáros", "Adrienn", "straight", "százados", "Nemzeti Adó- és Vámhivatal Adóügyi Főosztály", 194, 1),

);

// Establish database connection
try {
	$db = $app->bootstrap->getAdoDB();
} catch (exception $err) {
	echo "[ERROR] No connection to DB (getAdoDB() failed). Error message:\n" . $err . "\n";
	exit -1;
}

foreach($authors as $key => $value) {

	echo "Processing record:\n";
	var_dump($authors[$key]);

	$c_name1 = $authors[$key][0];
	$c_name2 = $authors[$key][1];
	$c_format = $authors[$key][2];
	$c_job = $authors[$key][3];
	$c_org = $authors[$key][4];
	$rec_id = $authors[$key][5];
	$c_role = $authors[$key][6];

	$id_org = null;

	if ( !empty($c_org) ) $id_org = insert_org($c_org);
	echo "OrgID: " . $id_org . "\n";

	$id_cont = insert_cont($c_name1, $c_name2, $c_format, $id_org, $c_job, $c_role, $rec_id);

}

exit;

function insert_string_dbl($string_hu, $string_en) {
global $db, $iscommit; 

	// Insert HU string
	$query = "
		INSERT INTO
			strings (language, value, translationof)
		VALUES('hu', '" . $string_hu . "', NULL)";

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		echo "[ERROR] SQL query failed.\n", trim($query), $err . "\n";
		exit -1;
	}

	$id_hu = $db->Insert_ID();
	echo "string insert id: " . $id_hu . "\n";

	// Update translationof field to itself
	$query = "
		UPDATE
			strings
		SET
			translationof = " . $id_hu . "
		WHERE
			id  = " . $id_hu;

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		echo "[ERROR] SQL query failed.\n", trim($query), $err . "\n";
		exit -1;
	}

	// Insert EN string
	$query = "
		INSERT INTO
			strings (language, value, translationof)
		VALUES('en', '" . $string_en . "', " . $id_hu . ")";

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		echo "[ERROR] SQL query failed.\n", trim($query), $err . "\n";
		exit -1;
	}

	$id_en = $db->Insert_ID();
//	echo "string en insert id: " . $id_en . "\n";

	return $id_hu;
}

function insert_cont($c_name1, $c_name2, $nameformat, $id_org, $c_job, $c_role, $rec_id) {
global $db, $iscommit;

	if ( $nameformat == "straight" ) {
		$c_first = $c_name2;
		$c_last = $c_name1;
	} else {
		$c_first = $c_name1;
		$c_last = $c_name2;
	}

	// check if cont exists
	$query = "
		SELECT
			a.id,
			a.namefirst,
			a.namelast,
			b.id as jobid,
			b.contributorid,
			b.organizationid,
			b.jobgroupid,
			b.job,
			c.id as orgid,
			c.name as orgname
		FROM
			contributors as a
		LEFT OUTER JOIN
			contributors_jobs as b
		ON
			a.id = b.contributorid
		LEFT OUTER JOIN
			organizations as c
		ON
			b.organizationid = c.id
		WHERE
			a.namefirst LIKE '%" . $c_first . "%' AND
			a.namelast LIKE '%" . $c_last . "%'
		ORDER BY
			a.id,
			b.id
	";

//echo $query . "\n";

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		echo "[ERROR] SQL query failed.\n", trim($query), $err . "\n";
		exit -1;
	}

	// Contributor exists?
	if ( $rs->RecordCount() >= 1 ) {
		echo "ERROR: már létezik a szerző? Válassz szerzőt, job és organization kombinációt az adatbázisból!\n";

		//List contribs and job groups
		$q = 1;
		$keys = array();
		$vals = array();
		$vals['cont'] = array();
		$vals['jobgrp'] = array();
		$vals['org'] = array();
		while ( !$rs->EOF ) {
			$cont = $rs->fields;
			array_push($keys, $q);
			array_push($vals['cont'], $cont['id']);
			array_push($vals['jobgrp'], $cont['jobgroupid']);
			array_push($vals['org'], $cont['organizationid']);
			echo "(" . $q . ") " . $cont['id'] . ", " . $cont['namelast'] . " " . $cont['namefirst'] . ", jobgrpid = " . $cont['jobgroupid'] . ", job = " . $cont['job'] . ", orgid = " . $cont['organizationid'] . ", org: " . $cont['orgname'] . "\n";
			$q++;
			$rs->MoveNext();
		}

		array_push($keys, "i");
		array_push($keys, "j");
		echo "...or (i)nsert new contrib or insert new (j)ob group?\n";
// Melyik szerzohoz???

		$key = read_key($keys);
		echo "\n";

var_dump($vals);

		// if not new contributor or job group, then use the selected job group
		if ( is_numeric($key) ) {

			$values = "";
			if ( empty($vals['org'][$key-1]) ) {
				$values .= "null, ";
			} else {
				$values .= $vals['org'][$key-1] . ", ";
			}

			$values .= $vals['cont'][$key-1] . ", " . $rec_id . ", ";

			if ( empty($vals['jobgrp'][$key-1]) ) {
				$values .= "null, ";
			} else {
				$values .= $vals['jobgrp'][$key-1] . ", ";
			}

			$values .= $c_role;

			$query = "
				INSERT INTO
					contributors_roles (organizationid, contributorid, recordingid, jobgroupid, roleid)
				VALUES(" . $values . ")
			";

echo $query . "\n";

			try {
				$rs = $db->Execute($query);
			} catch (exception $err) {
				echo "[ERROR] SQL query failed.\n", trim($query), $err . "\n";
				exit -1;
			}

			return TRUE;
		}

		if ( $key == "j" ) {
			echo "ERROR: !!!!!!!!! SELECT AUTHOR !!!!!!!!!!!!!!!\n";
			exit -1;
		}

	}

	// Cannot find any record or (i) is chosen
	$date = date("Y-m-d H:i:s");

	// insert contributor
	$query = "
		INSERT INTO
			contributors (timestamp, namefirst, namelast, nameformat, organizationid, createdby)
		VALUES('" . $date . "', '" . $c_first . "', '" . $c_last . "', '" . $nameformat . "', 2, 14)";

//echo $query . "\n";

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		echo "[ERROR] SQL query failed.\n", trim($query), $err . "\n";
		exit -1;
	}

	$id_cont = $db->Insert_ID();

	if ( empty($id_org) ) $id_org = "null";
	if ( empty($c_job) ) {
		$c_job = "null";
	} else {
		$c_job = "'" . $c_job . "'";
	}

	if ( ( $id_org != "null" ) or ( $c_job != "null" ) ) {

		// add job group
		$query = "
			INSERT INTO
				contributors_jobs (contributorid, organizationid, userid, jobgroupid, job)
			VALUES(" . $id_cont . ", " . $id_org . ", 14, 1, " . $c_job . ")
		";

		if ( !$iscommit ) echo $query . "\n";

		try {
			$rs = $db->Execute($query);
		} catch (exception $err) {
			echo "[ERROR] SQL query failed.\n", trim($query), $err . "\n";
			exit -1;
		}

//		$id_jobid = $db->Insert_ID();
	}

	// add job group to recording
	$query = "
		INSERT INTO
			contributors_roles (organizationid, contributorid, recordingid, jobgroupid, roleid)
		VALUES(" . $id_org . ", " . $id_cont . ", " . $rec_id . ", 1, " . $c_role . ")
	";

	if ( !$iscommit ) echo $query . "\n";

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		echo "[ERROR] SQL query failed.\n", trim($query), $err . "\n";
		exit -1;
	}

	return TRUE;
}

function insert_org($name) {
global $db, $iscommit;

	$recording = array();

	// check if org exists
	$query = "
		SELECT
			id,
			name
		FROM
			organizations
		WHERE
			name LIKE '%" . $name . "%'
	";

	if ( !$iscommit ) echo $query . "\n";

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		echo "[ERROR] SQL query failed.\n", trim($query), $err . "\n";
		exit -1;
	}

	if ( $rs->RecordCount() >= 1 ) {
		echo "ERROR: már létezik az org?\n";

		$q = 1;
		$keys = array();
		$vals = array();
		while ( !$rs->EOF ) {
			$org = $rs->fields;
			echo "(" . $q . ") org = " . $org['id'] . ", " . $org['name'] . "\n";
			array_push($keys, $q);
			array_push($vals, $org['id']);
			$q++;
			$rs->MoveNext();
		}

		array_push($keys, "i");
		echo "...or (i)nsert record?\n";

		$key = read_key($keys);
		echo "\n";

		// if not insert then
		if ( $key != "i" ) return $vals[$key-1];
	}

	$id_org_name = insert_string_dbl($name, null);
	$id_org_short = insert_string_dbl(null, null);
	$id_org_intro = insert_string_dbl(null, null); 

	// Insert EN string
	$query = "
		INSERT INTO
			organizations (name, name_stringid, nameshort_stringid, introduction_stringid, languages)
		VALUES('" . $name . "', " . $id_org_name . ", " . $id_org_short . ", " . $id_org_intro . " , 'hu,en')";

	if ( !$iscommit ) echo $query . "\n";

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		echo "[ERROR] SQL query failed.\n", trim($query), $err . "\n";
		exit -1;
	}

	$id_org = $db->Insert_ID();
//echo "idorg = " . $id_org . "\n";

	return $id_org;
}

function read_key($vals) {

	system("stty -icanon");
	echo "input# ";
	$inKey = "";
	while (!in_array($inKey, $vals)) {
		$inKey = fread(STDIN, 1);
	}

    return $inKey;
}

?>