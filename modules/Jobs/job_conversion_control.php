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
$myjobid = $jconf['jobid_conv_control'];

clearstatcache();

// Watchdog
$app->watchdog();
	
// Establish database connection
$db = db_maintain();

// !!!!
//updateRecordingStatus(9, null, "smil");
//updateRecordingStatus(9, null, "contentsmil");

// Query new uploads and insert recording versions
$recordings = getNewUploads();
if ( $recordings !== false ) {

	while ( !$recordings->EOF ) {

		$recording = array();
		$recording = $recordings->fields;

var_dump($recording);

		// Insert recording versions (recording_versions)
		insertRecordingVersions($recording);
exit;

		$recordings->MoveNext();
	}
}
exit;

// Upload: make recording status "onstorage" when a recording version is ready
// ...
$recordings = getReadyConversions();
if ( $recordings !== false ) {

	while ( !$recordings->EOF ) {

		$recording = array();
		$recording = $recordings->fields;
var_dump($recording);

		// Update recording/content status to "onstorage"
		if ( $recording['status'] == $jconf['dbstatus_conv'] ) updateRecordingStatus($recording['id'], $jconf['dbstatus_copystorage_ok'], "recording");
		if ( $recording['contentstatus'] == $jconf['dbstatus_conv'] ) updateRecordingStatus($recording['id'], $jconf['dbstatus_copystorage_ok'], "content");

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

		// Get e-mail subject line from localization
		$localization = $app->bootstrap->getLocalization();
		$subject = $localization('recordings', 'email_conversion_done_subject', $uploader_user['language']);
		if ( !empty($recording['mastervideofilename']) ) $subject .= ": " . $recording['mastervideofilename'];

		// Send e-mail
		try {
			$queue = $app->bootstrap->getMailqueue();
			$body = $smarty->fetch('Visitor/Recordings/Email/job_media_converter.tpl');			
			$queue->sendHTMLEmail($uploader_user['email'], $subject, $body);
		} catch (exception $err) {
			$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[ERROR] Cannot send mail to user: " . $uploader_user['email'] . "\n\n" . trim($body), $sendmail = true);
		}

// Error mail???? Hiba a status/contentstatus mezőkben jelenik meg. Ha egy recversion meghal, attól még lehet jó a többi??????

		$recordings->MoveNext();
	}
}

// SMIL: generate new SMIL files
$err = generateRecordingSMILs("recording");
$err = generateRecordingSMILs("content");

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
		ORDER BY
			r.id";

//echo $query;

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
			r.contentstatus,
			r.contentmasterstatus,
			r.contentmastersourceip,
			r.contentmastervideofilename,
			rv.status AS recordingversionstatus
		FROM
			recordings AS r,
			recordings_versions AS rv,
			encoding_profiles AS ep
		WHERE
			rv.recordingid = r.id AND
			rv.status = '" . $jconf['dbstatus_copystorage_ok'] . "' AND
			rv.encodingprofileid = ep.id AND
			( ep.mediatype = 'video' OR ( r.mastermediatype = 'audio' AND ep.mediatype = 'audio' ) ) AND
			( ( r.mastersourceip = '" . $node . "' AND r.status = '" . $jconf['dbstatus_conv'] . "' ) OR ( r.contentmastersourceip = '" . $node . "' AND r.contentstatus = '" . $jconf['dbstatus_conv'] . "' ) )
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
//var_dump($profileset);
//exit;

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
//var_dump($profileset);
//exit;

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

	if ( !empty($resolution) ) {
		$tmp = explode("x", $resolution, 2);
		$resx = $tmp[0];
		$resy = $tmp[1];
		$ep_filter = "( ep.parentid IS NULL OR ( epprev.videobboxsizex < " . $resx . " AND " . $resx . " <= ep.videobboxsizex ) OR ( epprev.videobboxsizey < " . $resy . " AND " . $resy . " <= ep.videobboxsizey ) )";
	} else {
		// No resolution, assume audio only input
		$ep_filter = "ep.mediatype = 'audio'";
	}

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
			ep.type = '" . $profile_type . "' AND " . $ep_filter;

echo $query . "\n";

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

function generateRecordingSMILs($type = "recording") {
global $db, $app, $debug, $jconf;

	if ( ( $type != "recording" ) and ( $type != "content" ) ) return false;

	$idx = "";
	if ( $type == "content" ) $idx = "content";

	// SMILs to update
	$query = "
		SELECT
			r.id,
			r." . $idx . "smilstatus,
			r." . $idx . "mastersourceip
		FROM
			recordings AS r
		WHERE
			r." . $idx . "status = '" . $jconf['dbstatus_copystorage_ok'] . "' AND 
			( r." . $idx . "smilstatus IS NULL OR r." . $idx . "smilstatus = '" . $jconf['dbstatus_regenerate'] . "' )
		ORDER BY
			r.id";

	try {
		$recordings = $db->Execute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
		return false;
	}

	// No pending SMILs
	if ( $recordings->RecordCount() < 1 ) return false;

/*
<smil>
    <head>
    </head>
    <body>
        <switch>
            <video src="mp4:20_320p_aligned.mp4" system-bitrate="264000"/>
            <video src="mp4:20_360p_aligned.mp4" system-bitrate="700000"/>
            <video src="mp4:20_480p_aligned.mp4" system-bitrate="1170000"/>
            <video src="mp4:20_720p_aligned.mp4" system-bitrate="2500000"/>
        </switch>
    </body>
</smil>
*/

	// SMIL header and footer tags
	$smil_header = "<smil>\n\t<head>\n\t</head>\n\t<body>\n\t\t<switch>\n";
	$smil_footer = "\t\t</switch>\n\t</body>\n</smil>\n";

	while ( !$recordings->EOF ) {

		$recording = $recordings->fields;

var_dump($recording);

		// Get all recording versions for this recording (or content)
		$recording_versions = getRecordingVersionsForRecording($recording['id'], $type);
		if ( $recording_versions === false ) {
			$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[ERROR] No recording versions found for " . $type . " id = " . $recording['id'], $sendmail = true);
			$recordings->MoveNext();
			continue;
		}

		$smil = $smil_header;
		while ( !$recording_versions->EOF ) {

			$recording_version = $recording_versions->fields;

			$smil .= sprintf("\t\t<video src=\"mp4:%s\" system-bitrate=\"%d\"/>\n", $recording_version['filename'], $recording_version['bandwidth']);

			$recording_versions->MoveNext();
		}
		$smil .= $smil_footer;

echo $smil . "\n";

		$smil_filename_suffix = "";
		if ( $idx == "content" ) $smil_filename_suffix = "_content";

		// Write SMIL content to a temporary file
		$smil_filename = "/tmp/" . $recording['id'] . $smil_filename_suffix . ".smil";
		$err = file_put_contents($smil_filename, $smil);
		if ( $err === false ) {
			$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[ERROR] SMIL file cannot be created at " . $smil_filename, $sendmail = true);
		} else {
			$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[INFO] SMIL file created at " . $smil_filename, $sendmail = false);
		}

		// SSH: copy SMIL file to server
		$smil_remote_filename = $app->config['recordingpath'] . ( $recording['id'] % 1000 ) . "/" . $recording['id'] . "/" . $recording['id'] . $smil_filename_suffix . ".smil";
echo $smil_remote_filename . "\n";

		$err = ssh_filecopy($recording[$idx . 'mastersourceip'], $smil_filename, $smil_remote_filename, false);
		if ( !$err['code'] ) {
			$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[ERROR] SMIL file update failed.\nMSG: " . $err['message'] . "\nCOMMAND: " . $err['command'] . "\nRESULT: " . $err['result'], $sendmail = true);
			$recordings->MoveNext();
			continue;
		} else {
			$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[INFO] SMIL file updated.\nCOMMAND: " . $err['command'], $sendmail = false);
		}

		// SSH: chmod new remote files
		$ssh_command = "ssh -i " . $jconf['ssh_key'] . " " . $jconf['ssh_user'] . "@" . $recording[$idx . 'mastersourceip'] . " ";
		$command = $ssh_command . "\"" . "chmod -f " . $jconf['file_access'] . " " . $smil_remote_filename . "\"";
		exec($command, $output, $result);
		$output_string = implode("\n", $output);
		if ( $result != 0 ) {
			$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[WARN] Cannot chmod new remote files (SSH)\nCOMMAND: " . $command . "\nRESULT: " . $output_string, $sendmail = true);
		} else {
			$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[INFO] Remote chmod OK. (SSH)\nCOMMAND: " . $command, $sendmail = false);
		}

		updateRecordingStatus($recording['id'], $jconf['dbstatus_copystorage_ok'], $idx . "smil");

		$recordings->MoveNext();
	}

	return true;
}

function getRecordingVersionsForRecording($recordingid, $type = "recording") {
global $db, $app, $jconf, $debug;

	if ( ( $type != "recording" ) and ( $type != "content" ) ) return false;

	$iscontent = 0;
	$mediatype = "video";
	if ( $type == "content" ) $iscontent = 1;

	$query = "
		SELECT
			rv.id,
			rv.recordingid,
			rv.status,
			rv.filename,
			rv.iscontent,
			rv.bandwidth,
			rv.isdesktopcompatible
		FROM
			recordings_versions AS rv,
			encoding_profiles AS ep
		WHERE
			rv.recordingid = " . $recordingid . " AND
			rv.iscontent = " . $iscontent . " AND
			rv.isdesktopcompatible = 1 AND
			rv.status = '" . $jconf['dbstatus_copystorage_ok'] . "' AND
			rv.encodingprofileid = ep.id AND
			ep.type = '" . $type . "' AND
			ep.mediatype = '" . $mediatype . "'
		ORDER BY
			rv.bandwidth";

echo $query . "\n";
//if ( $type == "content" ) exit;

	try {
		$recording_versions = $db->Execute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
		return false;
	}

	// No pending SMILs
	if ( $recording_versions->RecordCount() < 1 ) return false;

	return $recording_versions;
}

?>
