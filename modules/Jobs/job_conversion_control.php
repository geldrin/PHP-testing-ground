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
$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "Job: " . $jconf['jobid_conv_control'] . " started", $sendmail = false);

clearstatcache();

$app->watchdog();
	
// Establish database connection
$db = null;
$db = db_maintain();

// Query new uploads and insert recording versions
$recordings = getNewUploads();
if ( $recordings !== false ) {

	while ( !$recordings->EOF ) {

		$recording = array();
		$recording = $recordings->fields;

var_dump($recording);

		// Insert recording versions (recording_versions)
		insertRecordingVersions($recording);

		$recordings->MoveNext();
	}
}

exit;

//$queue = $app->bootstrap->getMailqueue();
//$queue->sendHTMLEmail("hiba@videosqr.com", "mikkamakka teszteles", "hovatova");
//exit;

// Upload: make recording status "onstorage" when a recording version is ready
// ...
$recordings = getReadyConversions();
if ( $recordings !== false ) {

	while ( !$recordings->EOF ) {

		$recording = array();
		$recording = $recordings->fields;
var_dump($recording);

		// Update recording status to "onstorage"
		updateRecordingStatus($recording['id'], $jconf['dbstatus_copystorage_ok'], "recording");
		$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[INFO] Recording status for recordingid = " . $recording['id'] . " (" . $recording['mastervideofilename'] . ") was set to: " . $jconf['dbstatus_copystorage_ok'], $sendmail = false);

		//// E-mail: send e-mail about a converted recording
		// Query recording creator
		$uploader_user = getRecordingCreator($recording['id']);
		if ( $uploader_user === false ) {
			$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[ERROR] Cannot get uploader user for recording. Not sending an e-mail to user. Recording data:\n\n" . print_r($recording, true), $sendmail = true);
			$recordings->MoveNext();
			continue;
		}

		// Send e-mail to user about successful conversion
		$smarty = $app->bootstrap->getSmarty();
		$organization = $app->bootstrap->getModel('organizations');
		$organization->select($uploader_user['organizationid']);
		$smarty->assign('organization', $organization->row);
		$smarty->assign('filename', $recording['mastervideofilename']);
		$smarty->assign('language', $uploader_user['language']);
		$smarty->assign('recid', $recording['id']);
		$smarty->assign('supportemail', $uploader_user['supportemail']);
		$smarty->assign('domain', $uploader_user['domain']);
		// Nyelvi stringbe kivinni!!!
		$subject = "Video conversion ready";
		if ( $uploader_user['language'] == "hu" ) $subject = "Videó konverzió kész";
		// !!!
		if ( !empty($recording['mastervideofilename']) ) $subject .= ": " . $recording['mastervideofilename'];
		$queue = $app->bootstrap->getMailqueue();

		// Send e-mail
		try {
			$body = $smarty->fetch('Visitor/Recordings/Email/job_media_converter.tpl');			
			$queue->sendHTMLEmail($uploader_user['email'], $subject, $body);
		} catch (exception $err) {
			$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[ERROR] Cannot send mail to user: " . $uploader_user['email'] . "\n\n" . trim($body), $sendmail = true);
		}

// Error mail????

		$recordings->MoveNext();
	}
}

// SMIL: generate new SMIL files
// ...

// Close DB connection if open
if ( is_resource($db->_connectionID) ) $db->close();

$app->watchdog();

exit;

function getNewUploads() {
global $jconf, $debug, $db, $app;

	$db = db_maintain();

	$node = $app->config['node_sourceip'];
// !!!
	$node = "stream.videosquare.eu";

// master v. sima statuszt kell ellenorizni???
	$query = "
		SELECT
			r.id,
			r.status,
			r.contentstatus,
			r.mastersourceip,
			r.contentmastersourceip,
			r.mastervideores,
			r.contentmastervideores,
			r.mastervideofilename,
			r.contentmastervideofilename
		FROM
			recordings AS r
		WHERE
			( r.mastersourceip = '" . $node . "' AND r.status = '" . $jconf['dbstatus_uploaded'] . "' ) OR
			( r.contentmastersourceip = '" . $node . "' AND r.contentstatus = '" . $jconf['dbstatus_uploaded'] . "' )
AND r.id = 89
		ORDER BY
			r.id";


	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
		return false;
	}

	// Check if any record returned
	if ( $rs->RecordCount() < 1 ) return false;

	return $rs;
}

function getReadyConversions() {
global $jconf, $debug, $db, $app;

	$db = db_maintain();

	$node = $app->config['node_sourceip'];
// !!!
	$node = "stream.videosquare.eu";

	// Get status = "converting" recordings with at least one "onstorage" recording version
	$query = "
		SELECT
			r.id,
			r.status,
			r.masterstatus,
			r.mastersourceip,
			r.mastervideofilename,
			rv.status AS recordingversionstatus
		FROM
			recordings AS r,
			recordings_versions AS rv,
			encoding_profiles AS ep
		WHERE
			r.mastersourceip = '" . $node . "' AND
			r.status = '" . $jconf['dbstatus_conv'] . "' AND
			rv.recordingid = r.id AND
			rv.status = '" . $jconf['dbstatus_copystorage_ok'] . "' AND
			rv.encodingprofileid = ep.id AND
			( ep.mediatype = 'video' OR ( r.mastermediatype = 'audio' AND ep.mediatype = 'audio' ) )
		GROUP BY
			r.id";

echo $query . "\n";

	try {
		$rs = $db->Execute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
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
	if ( $recording['status'] == $jconf['dbstatus_uploaded'] ) {

		$profileset = getEncodingProfileSet("recording", $recording['mastervideores']);

		if ( $profileset !== false ) {

			for ( $i = 0; $i < count($profileset); $i++ ) {

				$values = array(
					'timestamp'			=> date('Y-m-d H:i:s'),
					'converternodeid'	=> $converternodeid,
					'recordingid'		=> $recording['id'],
					'encodingprofileid'	=> $profileset[$i]['id'],
					'encodingorder'		=> $profileset[$i]['encodingorder'],
					'iscontent'			=> 0,
					'status'			=> $jconf['dbstatus_convert']
				);

				$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[INFO] Inserting recording version for recordingid = " . $recording['id'] . " (" . $recording['mastervideofilename'] . "):\n\n" . print_r($values, true), $sendmail = false);

				$recordingVersion = $app->bootstrap->getModel('recordings_versions');
				$recordingVersion->insert($values);

			}

			// Status: uploaded -> converting
			updateRecordingStatus($recording['id'], $jconf['dbstatus_conv'], "recording");
			$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[INFO] Recording status for recordingid = " . $recording['id'] . " was set to: " . $jconf['dbstatus_conv'], $sendmail = false);

		} else {
			$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[ERROR] No encoding profile can be selected to recording.\n" . print_r($recording, true), $sendmail = true);
		}
	}

	if ( $recording['contentstatus'] == $jconf['dbstatus_uploaded'] ) {

		$profileset = getEncodingProfileSet("content", $recording['contentmastervideores']);

		if ( $profileset !== false ) {

			for ( $i = 0; $i < count($profileset); $i++ ) {

				$values = array(
					'timestamp'			=> date('Y-m-d H:i:s'),
					'converternodeid'	=> $converternodeid,
					'recordingid'		=> $recording['id'],
					'encodingprofileid'	=> $profileset[$i]['id'],
					'encodingorder'		=> $profileset[$i]['encodingorder'],
					'iscontent'			=> 1,
					'status'			=> $jconf['dbstatus_convert']
				);

				$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[INFO] Inserting recording version for content recordingid = " . $recording['id'] . " (" . $recording['mastervideofilename'] . "):\n\n" . print_r($values, true), $sendmail = false);

				$recordingVersion = $app->bootstrap->getModel('recordings_versions');
				$recordingVersion->insert($values);

			}

			// Status: uploaded -> converting
			updateRecordingStatus($recording['id'], $jconf['dbstatus_conv'], "content");
			$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[INFO] Recording status for content recordingid = " . $recording['id'] . " (" . $recording['mastervideofilename'] . ") was set to: " . $jconf['dbstatus_conv'], $sendmail = false);

		} else {
			$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[ERROR] No encoding profile to recording.\n" . print_r($recording, true), $sendmail = true);
		}
	}

	//// Mobile versions
// !!!
	// No content: normal encoding
/*	if ( ( $recording['masterstatus'] == $jconf['dbstatus_uploaded'] ) and empty($recording['contentmasterstatus']) ) {

		$profileset = getEncodingProfileSet("mobile", $recording['mastervideores']);

	}
	// Content uploaded: picture-in-picture encoding
	if ( ( $recording['masterstatus'] == $jconf['dbstatus_uploaded'] ) and ( $recording['contentmasterstatus'] == $jconf['dbstatus_uploaded'] ) ) {

		$profileset = getEncodingProfileSet("mobile", $recording['contentmastervideores']);
*/

	return true;
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

//echo $query . "\n";

	try {
		$profileset = $db->getArray($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[ERROR] Cannot query next job. SQL query failed." . trim($query), $sendmail = true);
		return false;
	}

	// Check if any record returned
	if ( count($profileset) < 1 ) return false;

	return $profileset;
}

function selectConverterNode() {
global $debug, $jconf;

	// Place for future code (multi-converter environment. Selection criterias might be:
	// - Converter node reachability (watchdog mechanism to update DB field with last activity timestamp?)
	// - Converter node current activity (converting vs. not converting)
	// - Converter node queue length
	$nodeid = 1;

	$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[INFO] Converter node nodeid = " . $nodeid . " was selected.", $sendmail = false);

	return $nodeid;
}

?>
