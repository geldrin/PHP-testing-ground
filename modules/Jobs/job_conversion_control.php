<?php
// Media conversion job v0 @ 2012/02/??

define('BASE_PATH',	realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');

// Utils
include_once('job_utils_base.php');
include_once('job_utils_log.php');
include_once('job_utils_status.php');
include_once('job_utils_media.php');

set_time_limit(0);

// Init
$app = new Springboard\Application\Cli(BASE_PATH, PRODUCTION);

// Load jobs configuration file
$app->loadConfig('modules/Jobs/config_jobs.php');
$jconf = $app->config['config_jobs'];

// Check operating system - exit if Windows
if ( iswindows() ) {
	echo "ERROR: Non-Windows process started on Windows platform\n";
	exit;
}

// Start an infinite loop - exit if any STOP file appears
if ( is_file( $app->config['datapath'] . 'jobs/' . $jconf['jobid_conv_control'] . '.stop' ) or is_file( $app->config['datapath'] . 'jobs/all.stop' ) ) exit;

// Log related init
$debug = Springboard\Debug::getInstance();
$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "Job: " . $jconf['jobid_conv_control'] . "started", $sendmail = false);

clearstatcache();

$app->watchdog();
	
// Establish database connection
$db = null;
$db = db_maintain();

// Query new uploads
$recordings = getNewUploads();
if ( $recordings !== false ) {

	while ( !$recordings->EOF ) {

		$recording = array();
		$recording = $recordings->fields;

var_dump($recording);

insertRecordingVersions($recording);

exit;
		$recordings->MoveNext();
	}

}

// Close DB connection if open
if ( is_resource($db->_connectionID) ) $db->close();

$app->watchdog();

sleep($converter_sleep_length);

exit;

function getNewUploads() {
global $jconf, $debug, $db, $app;

	$db = db_maintain();

	$node = $app->config['node_sourceip'];
// !!!
	$node = "stream.videosquare.eu";

	$query = "
		SELECT
			r.id,
			r.masterstatus,
			r.contentmasterstatus,
			r.mastersourceip,
			r.contentmastersourceip,
			r.mastervideores,
			r.contentmastervideores
		FROM
			recordings AS r
		WHERE
			(r.mastersourceip = '" . $node . "' AND r.masterstatus = '" . $jconf['dbstatus_uploaded'] . "') OR
			(r.contentmastersourceip = '" . $node . "' AND r.contentmasterstatus = '" . $jconf['dbstatus_uploaded'] . "')
		ORDER BY
			r.id";

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $jconf['job_conversion_control'] . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
		return false;
	}

	// Check if any record returned
	if ( $rs->RecordCount() < 1 ) return false;

	return $rs;
}

function insertRecordingVersions($recording) {
global $db, $debug, $jconf, $app;

	$converternodeid = selectConverterNode();

	// Recording
	$idx = "";
	if ( $recording['masterstatus'] == $jconf['dbstatus_uploaded'] ) {

		$iscontent = 0;

		$profileset = getEncodingProfileSet("recording", $recording['mastervideores']);

		if ( $profileset !== false ) {

echo "RECORDING: ";
var_dump($profileset);

			for ( $i = 0; $i < count($profileset); $i++ ) {

				$values = array(
					'timestamp'			=> date('Y-m-d H:i:s'),
					'converternodeid'	=> $converternodeid,
					'recordingid'		=> $recording['id'],
					'encodingprofileid'	=> $profileset[$i]['id'],
					'encodingorder'		=> $profileset[$i]['encodingorder'],
					'iscontent'			=> $iscontent,
					'status'			=> $jconf['dbstatus_convert']
				);
var_dump($values);

				$recordingVersion = $app->bootstrap->getModel('recordings_versions');
				$recordingVersion->insert($values);

			}

		} else {
echo "fuck\n";
exit;
		}
	}

exit;
/*
	if ( $recording['contentmasterstatus'] == $jconf['dbstatus_uploaded'] ) {

		$profileset = getEncodingProfileSet("content", $recording['contentmastervideores']);
//$cres = "640x480";
//		$profileset = getEncodingProfileSet("content", $cres);
		if ( $profileset === false ) {
echo "fuck\n";
exit;
		}
echo "CONTENT: " . $cres . "\n";
var_dump($profileset);
// INSERT
	}

//	if ( $recording['contentmasterstatus'] == $jconf['dbstatus_uploaded'] ) {
	if ( 1 ) {

//		$profileset = getEncodingProfileSet("content", $recording['contentmastervideores']);

		$profileset = getEncodingProfileSet("mobile", $rres);
		if ( $profileset === false ) {
echo "fuck\n";
exit;
		}
echo "MOBILE: " . $rres . "\n";
var_dump($profileset);
// INSERT
	}

exit;

*/

}

function getEncodingProfileSet($profile_type, $resolution) {
global $db, $debug, $jconf;

	$tmp = explode("x", $resolution, 2);
	$resx = $tmp[0];
	$resy = $tmp[1];

	$db = db_maintain();

	$query = "
		SELECT
			eg.id AS encodingprofilegroupid,
			eg.name AS encodingprofilegroupname,
			epg.encodingorder,
			ep.id,
			ep.parentid,
			ep.name,
			ep.shortname,
			ep.type,
			ep.videobboxsizex,
			ep.videobboxsizey,
			epprev.videobboxsizex AS parentvideobboxsizex,
			epprev.videobboxsizey AS parentvideobboxsizey
		FROM
			encoding_groups AS eg
		LEFT JOIN
			( encoding_profiles_groups AS epg, encoding_profiles AS ep )
		ON
			( eg.id = epg.encodingprofilegroupid AND epg.encodingprofileid = ep.id )
		LEFT JOIN
			encoding_profiles AS epprev
		ON
			ep.parentid = epprev.id
		WHERE
			eg.default = 1 AND
			eg.disabled = 0 AND
			ep.type = '" . $profile_type . "' AND
			( ep.parentid IS NULL OR ( epprev.videobboxsizex < " . $resx . " AND " . $resx . " <= ep.videobboxsizex ) OR ( epprev.videobboxsizey < " . $resy . " AND " . $resy . " <= ep.videobboxsizey ) )";

echo $query . "\n";

	try {
		$profileset = $db->getArray($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $jconf['job_conversion_control'] . ".log", "[ERROR] Cannot query next job. SQL query failed." . trim($query), $sendmail = true);
		return false;
	}

	// Check if any record returned
	if ( count($profileset) < 1 ) return false;

	return $profileset;
}

function selectConverterNode() {

	// Place for future code (multi-converter environment. Selection criterias might be:
	// - Converter node reachability (watchdog mechanism to update DB field with last activity timestamp?)
	// - Converter node current activity (converting vs. not converting)
	// - Converter node queue length
	$nodeid = 1;

	return $nodeid;
}

?>
