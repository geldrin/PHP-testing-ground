<?php
// Videosquare conversion control job

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
$myjobid = $jconf['jobid_conv_control'];

clearstatcache();

// Runover check. Is this process already running? If yes, report and exit
if ( !runOverControl($myjobid) ) exit;

// Watchdog
$app->watchdog();

// Query new uploads and insert recording versions
$recordings = getNewUploads();
if ( $recordings !== false ) {

	while ( !$recordings->EOF ) {

		$recording = array();
		$recording = $recordings->fields;
		
		// Reconvert flag := false (legacy profile)
		$isreconvertforce = false;
		// Recording version status filter set
		$filter = $jconf['dbstatus_copystorage_ok'] . "|" . $jconf['dbstatus_conv'] . "|" . $jconf['dbstatus_convert'] . "|" . $jconf['dbstatus_stop'] . "|" . $jconf['dbstatus_copystorage'] . "|" . $jconf['dbstatus_copyfromfe'] . "|" . $jconf['dbstatus_copyfromfe_ok'] . "|" . $jconf['dbstatus_reconvert'];

		// ## New content is uploaded (old removed). Check if recording is legacy encoded. If yes, launch a full reconvert.
		if ( $recording['contentstatus'] == $jconf['dbstatus_uploaded'] ) {

			// Is recording encoded with a legacy encoding group?
			$encodinggroup = getEncodingProfileGroup($recording['encodinggroupid']);
			if ( $encodinggroup !== false ) {
				if ( $encodinggroup['islegacy'] == 1 ) {
					$isreconvertforce = true;
					$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[INFO] Content uploaded for legacy profile encoded recording id = " . $recording['id'] . " (" . $recording['contentmastervideofilename'] . "). Forcing full reconvert.", $sendmail = false);
					$recording['status'] = $jconf['dbstatus_reconvert'];
				}
			}
			
			updateRecordingStatus($recording['id'], $jconf['dbstatus_convert'], 'ocr');
		}
		
		// ## Recording level reconvert: mark all recording versions to be deleted (onstorage, convert, converting, stop, copy*, reconvert)
		// Content: Do content first. If legacy encoded, we will reconvert recording
		if ( $recording['contentstatus'] == $jconf['dbstatus_reconvert'] ) {
			$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[INFO] Content reconvert for recordingid = " . $recording['id'] . " (" . $recording['contentmastervideofilename'] . ").", $sendmail = false);
			updateRecordingVersionStatusApplyFilter($recording['id'], $jconf['dbstatus_markedfordeletion'], "content|pip", $filter);
			updateRecordingStatus($recording['id'], $jconf['dbstatus_reconvert'], 'ocr');
			$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[INFO] OCR reconvert has been issued.", $sendmail = false);
			
			// Is recording encoded with a legacy encoding group?
			$encodinggroup = getEncodingProfileGroup($recording['encodinggroupid']);
			if ( $encodinggroup !== false ) {
				if ( $encodinggroup['islegacy'] == 1 ) $isreconvertforce = true;
			}
		}
		// Recording: Reconvert. If recording was legacy encoded, then force reconvert.
		if ( ( $recording['status'] == $jconf['dbstatus_reconvert'] ) or $isreconvertforce ) {
			$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[INFO] Recording reconvert for recordingid = " . $recording['id'] . " (" . $recording['mastervideofilename'] . ").", $sendmail = false);
			// Force "reconvert", set status fields
			if ( $recording['status'] != $jconf['dbstatus_reconvert'] ) {
				$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[INFO] Recording reconvert forced by legacy encoding profile group.", $sendmail = false);
				$recording['status'] = $jconf['dbstatus_reconvert'];
				updateRecordingStatus($recording['id'], $jconf['dbstatus_reconvert'], "recording");
			}
			updateRecordingVersionStatusApplyFilter($recording['id'], $jconf['dbstatus_markedfordeletion'], "recording", $filter);
		}
		
		// ## Insert recording versions (recording_versions)
		insertRecordingVersions($recording);

		$recordings->MoveNext();
	}
}

// Upload: make recording status "onstorage" when a recording version is ready
$recordings = getReadyConversions();
if ( $recordings !== false ) {

	while ( !$recordings->EOF ) {

		$recording = array();
		$recording = $recordings->fields;

		// Update recording/content status to "onstorage"
		if ( $recording['status'] == $jconf['dbstatus_conv'] ) {
			updateRecordingStatus($recording['id'], $jconf['dbstatus_copystorage_ok'], "recording");
			// Update recording.mobilestatus to "onstorage"
			if ( $recording['ismobilecompatible'] == 1 ) updateRecordingStatus($recording['id'], $jconf['dbstatus_copystorage_ok'], "mobile");
		}
		if ( $recording['contentstatus'] == $jconf['dbstatus_conv'] ) updateRecordingStatus($recording['id'], $jconf['dbstatus_copystorage_ok'], "content");
		if ( in_array($recording['contentstatus'], array(
			$jconf['dbstatus_copystorage_ok'],
			$jconf['dbstatus_uploaded'],
			$jconf['dbstatus_conv'],
			$jconf['dbstatus_convert'],
		))) updateRecordingStatus($recording['id'], $jconf['dbstatus_convert'], 'ocr');

		//// E-mail: send e-mail about a converted recording
		// Query recording creator
		$uploader_user = getRecordingCreator($recording['id']);
		if ( $uploader_user === false ) {
			$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[ERROR] Cannot get uploader user for recording. Not sending an e-mail to user. Recording data:\n\n" . print_r($recording, true), $sendmail = true);
			$recordings->MoveNext();
			continue;
		}

		// Recording e-mail
		if ( $recording['status'] == $jconf['dbstatus_conv'] ) {

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

		}

		$recordings->MoveNext();
	}
}

// Get additional ready mobile versions if mobilestatus != "onstorage" (non-mobile compatible desktop version - above - will not trigger mobilestatus := "onstorage")
$recordings = getReadyMobileConversions();
if ( $recordings !== false ) {

	while ( !$recordings->EOF ) {

		$recording = array();
		$recording = $recordings->fields;
		
		$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[INFO] Mobile compatible version for recid#" . $recording['id'] . " become ready.", false);
		
		updateRecordingStatus($recording['id'], $jconf['dbstatus_copystorage_ok'], "mobile");

		$recordings->MoveNext();
	}
}


// Failed recordings: get failed recording versions and set "failed" status on recording level
$failedRecordings = getFailedRecordingVersions();
if ( $failedRecordings !== false ) {
    
    $failedRecording = $failedRecordings->fields;

    // Get all recording versions
    $recVersionsOK = 0;
    $recVersions = getRecordingVersionsApplyStatusFilter($failedRecording['id'], $failedRecording['type'], null);
    if ( $recVersions !== false ) {

        while ( !$recVersions->EOF ) {

            $recVersion = $recVersions->fields;

            // Count specific type of media files with "onstorage" status
            if ( $recVersion['status'] == $jconf['dbstatus_copystorage_ok'] ) $recVersionsOK++;

            // Next
            $recVersions->MoveNext();
        }
        
    }
    
    // ## Are there any serviceable media files available?
    // Is there at least a playable recording?
    if ( ( $recVersion['type'] == "recording" ) and ( $recVersionsOK == 0 ) ) {
        $debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[ERROR] Recording id#" . $failedRecording['id'] . " has no serviceable video instances.", true);
        updateRecordingStatus($failedRecording['id'], $jconf['dbstatus_conv_err'], 'recording');
    }
    // Is there at least a single playable content?
    if ( ( $recVersion['type'] == "content" ) and ( $recVersionsOK == 0 ) ) {
        $debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[ERROR] Recording id#" . $failedRecording['id'] . " has no serviceable content instances.", true);
        updateRecordingStatus($failedRecording['id'], $jconf['dbstatus_conv_err'], 'content');
    }
    // Is there at least a single pip content?
    if ( ( $recVersion['type'] == "pip" ) and ( $recVersionsOK == 0 ) ) {
        $debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[ERROR] Recording id#" . $failedRecording['id'] . " has no serviceable pip instances.", true);
        updateRecordingStatus($failedRecording['id'], $jconf['dbstatus_conv_err'], 'mobile');
    }
    
    $failedRecordings->MoveNext();
}

// SMIL: generate new SMIL files
$err = generateRecordingSMILs("recording");
$err = generateRecordingSMILs("content");

// SMIL: generate SMILs for live
$err = generateLiveSMILs("video");
$err = generateLiveSMILs("content");

// Watchdog
$app->watchdog();

exit;

function getNewUploads() {
global $jconf, $debug, $app;

	$node = $app->config['node_sourceip'];

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
			r.contentmastervideofilename,
			r.mastermediatype,
			r.contentmastermediatype,
			r.mediatype,
			r.organizationid,
			r.encodinggroupid,
			o.defaultencodingprofilegroupid
		FROM
			recordings AS r,
			organizations AS o
		WHERE
			r.organizationid = o.id AND (
			( r.mastersourceip = '" . $node . "' AND
            ( r.status = '" . $jconf['dbstatus_uploaded'] . "' OR r.status = '" . $jconf['dbstatus_reconvert'] . "' ) AND
			( r.masterstatus = '" . $jconf['dbstatus_uploaded'] . "' OR r.masterstatus = '" . $jconf['dbstatus_copystorage_ok'] . "' ) ) OR
            ( r.contentmastersourceip = '" . $node . "' AND
            ( r.contentstatus = '" . $jconf['dbstatus_uploaded'] . "' OR r.contentstatus = '" . $jconf['dbstatus_reconvert'] . "' ) AND
			( r.contentmasterstatus = '" . $jconf['dbstatus_uploaded'] . "' OR r.contentmasterstatus = '" . $jconf['dbstatus_copystorage_ok'] . "' ) )
			)
		ORDER BY
			r.id";
			
	try {
        $model = $app->bootstrap->getModel('recordings');
        $rs = $model->safeExecute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
		return false;
	}

	if ( $rs->RecordCount() < 1 ) return false;

	return $rs;
}

function getReadyConversions() {
global $jconf, $debug, $app;

	$node = $app->config['node_sourceip'];

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
			rv.status AS recordingversionstatus,
			rv.ismobilecompatible
		FROM
			recordings AS r,
			recordings_versions AS rv,
			encoding_profiles AS ep
		WHERE
			r.id = rv.recordingid AND
			rv.status = '" . $jconf['dbstatus_copystorage_ok'] . "' AND
			rv.encodingprofileid = ep.id AND
			( ep.mediatype = 'video' OR ( r.mastermediatype = 'audio' AND ep.mediatype = 'audio' ) ) AND
			( ( r.status = '" . $jconf['dbstatus_conv'] . "' AND ep.type = 'recording' AND r.mastersourceip = '" . $node . "' ) OR
			  ( r.contentstatus = '" . $jconf['dbstatus_conv'] . "' AND ep.type = 'content' AND r.contentmastersourceip = '" . $node . "' ) )
		GROUP BY
			r.id";

	try {
        $model = $app->bootstrap->getModel('recordings');
        $rs = $model->safeExecute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
		return false;
	}

	if ( $rs->RecordCount() < 1 ) return false;

	return $rs;
}

// For encoding profiles that generate different versions for desktop players and mobile devices.
// In this case, we set status := "onstorage" when first (desktop only) version is ready. Since it is not
// mobile compatible mobilestatus := "onstorage" never executed.
function getReadyMobileConversions() {
global $jconf, $debug, $app;

	$node = $app->config['node_sourceip'];

	// Get status = "onstorage" AND mobilestatus = NULL recordings with at least one "onstorage" mobile compatible recording version
	$query = "
		SELECT
			r.id,
			r.status,
			r.masterstatus,
			r.mastersourceip,
			r.mastervideofilename,
			rv.status AS recordingversionstatus,
			rv.ismobilecompatible
		FROM
			recordings AS r,
			recordings_versions AS rv,
			encoding_profiles AS ep
		WHERE
			r.mastersourceip = '" . $node . "' AND
			r.status = '" . $jconf['dbstatus_copystorage_ok'] . "' AND
			( r.mobilestatus IS NULL OR r.mobilestatus <> '" . $jconf['dbstatus_copystorage_ok'] . "' ) AND
			r.id = rv.recordingid AND
			rv.status = '" . $jconf['dbstatus_copystorage_ok'] . "' AND
			rv.ismobilecompatible = 1 AND
			rv.encodingprofileid = ep.id AND
			ep.type = 'recording' AND
			( ep.mediatype = 'video' OR ( r.mastermediatype = 'audio' AND ep.mediatype = 'audio' ) )
		GROUP BY
			r.id";

	try {
        $model = $app->bootstrap->getModel('recordings');
        $rs = $model->safeExecute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
		return false;
	}

	if ( $rs->RecordCount() < 1 ) return false;

	return $rs;
}

function getFailedRecordingVersions() {
global $jconf, $debug, $app;

    $node = $app->config['node_sourceip'];

    $query = "
        SELECT
            r.id,
            COUNT(r.id) AS numberoffails,
            r.status,
            r.masterstatus,
            r.mastersourceip,
            r.mastervideofilename,
            r.contentstatus,
            r.contentmasterstatus,
            r.contentmastersourceip,
            r.contentmastervideofilename,
            rv.status AS recordingversionstatus,
            rv.ismobilecompatible,
            ep.type
        FROM
            recordings AS r,
            recordings_versions AS rv,
            encoding_profiles AS ep
        WHERE
            r.id = rv.recordingid AND
            rv.status LIKE 'failed%' AND
            rv.encodingprofileid = ep.id AND
            ( ep.mediatype = 'video' OR ( r.mastermediatype = 'audio' AND ep.mediatype = 'audio' ) ) AND
            ( ( r.status = '" . $jconf['dbstatus_conv'] . "' AND ep.type = 'recording' AND r.mastersourceip = '" . $node . "' ) OR
              ( r.contentstatus = '" . $jconf['dbstatus_conv'] . "' AND ep.type = 'content' AND r.contentmastersourceip = '" . $node . "' ) )
        GROUP BY
            r.id, ep.type";
            
	try {
        $model = $app->bootstrap->getModel('recordings');
        $rs = $model->safeExecute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
		return false;
	}
    
    if ( $rs->RecordCount() < 1 ) return false;

	return $rs;        
}

function insertRecordingVersions($recording) {
global $debug, $jconf, $app;

	// Converter node selection
	$converternodeid = selectConverterNode();

	// Encoding group selection
    if ( empty($recording['encodinggroupid']) ) {
        $encodinggroupid = $recording['defaultencodingprofilegroupid'];
    } else {
        $encodinggroupid = $recording['encodinggroupid'];
    }

    $debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[INFO] Selected encoding profile group: " . $encodinggroupid, $sendmail = false);

	// Recording
	$idx = "";
	if ( ( $recording['status'] == $jconf['dbstatus_uploaded'] ) or  ( $recording['status'] == $jconf['dbstatus_reconvert'] ) ) {

		$profileset = getEncodingProfileSet("recording", $recording['mastervideores'], $encodinggroupid);

		if ( $profileset !== false ) {

			for ( $i = 0; $i < count($profileset); $i++ ) {
				
				if ($profileset[$i]['mediatype'] == 'audio' && $recording['mediatype'] == 'videoonly') {
					// If encprofile is audio-only, but the input has no audiochannel, skip inserting 'audio' rec. version
					continue;
				}
				
				$values = array(
					'timestamp'         => date('Y-m-d H:i:s'),
					'converternodeid'   => $converternodeid,
					'recordingid'       => $recording['id'],
					'encodingprofileid' => $profileset[$i]['id'],
					'encodingorder'     => $profileset[$i]['encodingorder'],
					'iscontent'         => 0,
					'status'            => $jconf['dbstatus_convert']
				);

				$recordingVersion = $app->bootstrap->getModel('recordings_versions');
				$recordingVersion->insert($values);

				$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[INFO] Recording version for recordingid = " . $recording['id'] . " (" . $recording['mastervideofilename'] . ") inserted (" . $profileset[$i]['name'] . "):\n\n" . print_r($values, true), $sendmail = false);

			}

			// Update encoding profile group id (to help islegacy check)
			updateRecordingEncodingProfile($recording['id'], $encodinggroupid);

			// Status: uploaded -> converting
			updateRecordingStatus($recording['id'], $jconf['dbstatus_conv'], "recording");
		} else {
			$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[ERROR] No encoding profile can be selected for recordingid = " . $recording['id'] . ". Recording info:\n" . print_r($recording, true), $sendmail = true);
		}
	}

	// Content
	if ( ( $recording['contentstatus'] == $jconf['dbstatus_uploaded'] ) or ( $recording['contentstatus'] == $jconf['dbstatus_reconvert'] ) ) {

		$profileset = getEncodingProfileSet("content", $recording['contentmastervideores'], $encodinggroupid);

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

				$recordingVersion = $app->bootstrap->getModel('recordings_versions');
				$recordingVersion->insert($values);

				$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[INFO] Recording version for content recordingid = " . $recording['id'] . " (" . $recording['contentmastervideofilename'] . ") inserted (" . $profileset[$i]['name'] . "):\n\n" . print_r($values, true), $sendmail = false);
			}

			// Status: uploaded -> converting
			updateRecordingStatus($recording['id'], $jconf['dbstatus_conv'], "content");
		} else {
			$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[ERROR] No encoding profile for content recordingid = " . $recording['id'] . ". Content info:\n" . print_r($recording, true), $sendmail = true);
		}

		// Mobile versions into recordings_versions. Use content resolution as background.
		$profileset = getEncodingProfileSet("pip", $recording['contentmastervideores'], $encodinggroupid);

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

				$recordingVersion = $app->bootstrap->getModel('recordings_versions');
				$recordingVersion->insert($values);

				$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[INFO] Inserting recording version for PiP recordingid = " . $recording['id'] . " inserted (" . $profileset[$i]['name'] . "):\n\n" . print_r($values, true), $sendmail = false);
			}

		} else {
			$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[ERROR] No encoding profile for PiP version recordingid = " . $recording['id'] . ". Recording info:\n" . print_r($recording, true), $sendmail = true);
		}

	}

	return true;
}

function getEncodingProfileSet($profile_type, $resolution, $encodinggroupid) {
global $debug, $jconf, $app;

	if ( !empty($resolution) ) {
		$tmp = explode("x", $resolution, 2);
		$resx = $tmp[0];
		$resy = $tmp[1];
		$ep_filter = "( ep.parentid IS NULL OR ( epprev.videobboxsizex < " . $resx . " OR epprev.videobboxsizey < " . $resy . " ) )";
	} else {
		// No resolution, assume audio only input
		$ep_filter = "ep.mediatype = 'audio'";
	}

	// Encoding group filter: choose default if invalid value
	if ( !empty($encodinggroupid) and $encodinggroupid > 0 ) {
		$eg_filter = "eg.id = " . $encodinggroupid;
	} else {
		$eg_filter = "eg.default = 1";
	}

	$query = "
		SELECT
			eg.id AS encodingprofilegroupid,
			eg.name AS encodingprofilegroupname,
			eg.default,
			eg.islegacy,
			epg.encodingorder,
			ep.id,
			ep.parentid,
			ep.name,
			ep.shortname,
			ep.type,
			ep.mediatype,
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
			" . $eg_filter . " AND
			eg.disabled = 0 AND
			ep.type = '" . $profile_type . "' AND " . $ep_filter;

	try {
        $model = $app->bootstrap->getModel('encoding_groups');
        $rs = $model->safeExecute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[ERROR] Cannot query next job. SQL query failed." . trim($query), $sendmail = true);
		return false;
	}

    if ( $rs->RecordCount() < 1 ) return false;

    $rs_array = adoDBResourceSetToArray($rs);    

	return $rs_array;
}

function getEncodingProfileGroup($encodinggroupid) {
global $app, $debug, $jconf;

	// Encoding group filter: choose default if invalid value
	if ( empty($encodinggroupid) ) return false;

	$query = "
		SELECT
			eg.id,
			eg.name,
			eg.default,
			eg.islegacy,
			eg.disabled
		FROM
			encoding_groups AS eg
		WHERE
			eg.id = " . $encodinggroupid . " AND eg.disabled = 0";

	try {
        $model = $app->bootstrap->getModel('encoding_groups');
        $rs = $model->safeExecute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[ERROR] Cannot query encoding group. SQL query failed.\n" . trim($query), $sendmail = true);
		return false;
	}

    if ( $rs->RecordCount() < 1 ) return false;

    $rs_array = adoDBResourceSetToArray($rs);  

	return $rs_array[0];
}


function selectConverterNode() {
global $debug, $jconf, $app;

	// Place for future code (multi-converter environment. Selection criterias might be:
	// - Converter node reachability (watchdog mechanism to update DB field with last activity timestamp?)
	// - Converter node current activity (converting vs. not converting)
	// - Converter node queue length

    $query = "
        SELECT
            ins.id,
            ins.server,
            ins.cpuload15min,
            ins.storageworkfree,
            ins.statusstorage
        FROM
            infrastructure_nodes AS ins
        WHERE
            ins.type = 'converter' AND
            ins.disabled = 0 AND
            ( (ins.statusstorage = 'ok' AND
			ins.statusnetwork = 'ok' AND
			ins.cpuload15min IS NOT NULL ) OR ins.default = 1 )
        ORDER BY
            ins.cpuload15min ASC,
            ins.storageworkfree DESC
        LIMIT 1";

    try {
        $model = $app->bootstrap->getModel('infrastructure_nodes');
        $rs = $model->safeExecute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
		return false;
	}

	$node_info = $rs->fields;
        
	$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[INFO] Converter node nodeid = " . $node_info['id'] . " (" . $node_info['server'] . ") was selected.", $sendmail = false);

	return $node_info['id'];
}

function generateRecordingSMILs($type = "recording", $filtercompatibility = "desktop") {
global $app, $debug, $jconf;

	if ( ( $type != "recording" ) and ( $type != "content" ) ) return false;

	$idx = "";
	if ( $type == "content" ) $idx = "content";

    $node = $app->config['node_sourceip'];
      
	// SMILs to update
	$query = "
		SELECT
			r.id,
			r." . $idx . "smilstatus,
			r." . $idx . "mastersourceip,
			r." . $idx . "mastermediatype
		FROM
			recordings AS r
		WHERE
			r." . $idx . "status = '" . $jconf['dbstatus_copystorage_ok'] . "' AND
			( r." . $idx . "smilstatus IS NULL OR r." . $idx . "smilstatus = '" . $jconf['dbstatus_regenerate'] . "' ) AND
            r." . $idx . "mastersourceip = '" . $node . "'
		ORDER BY
			r.id";

	try {
        $model = $app->bootstrap->getModel('recordings');
        $recordings = $model->safeExecute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
		return false;
	}

	// No pending SMILs
	if ( $recordings->RecordCount() < 1 ) return false;

	/*
	SMIL example:
		<?xml version="1.0" encoding="UTF-8"?>
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

	SMIL live example with audio stream (audio tag is for Smooth Streaming only?):
		<?xml version="1.0" encoding="UTF-8"?>
		<smil title="">
			<body>
				<switch>
					<video height="240" src="bigbuckbunny_450.mp4" systemLanguage="eng" width="424">
						<param name="videoBitrate" value="450000" valuetype="data"></param>
						<param name="audioBitrate" value="44100" valuetype="data"></param>
					</video>
					<video height="360" src="bigbuckbunny_750.mp4" systemLanguage="eng" width="640">
						<param name="videoBitrate" value="750000" valuetype="data"></param>
						<param name="audioBitrate" value="44100" valuetype="data"></param>
					</video>
					<video height="720" src="bigbuckbunny_1100.mp4" systemLanguage="eng" width="1272">
						<param name="videoBitrate" value="1100000" valuetype="data"></param>
						<param name="audioBitrate" value="44100" valuetype="data"></param>
					</video>
					<video height="900" src="bigbuckbunny_1500.mp4" systemLanguage="eng" width="1590">
						<param name="videoBitrate" value="1500000" valuetype="data"></param>
						<param name="audioBitrate" value="44100" valuetype="data"></param>
					</video>
					<audio>
						<param name="audioBitrate" value="44100" valuetype="data"></param>
					</audio>
				</switch>
			</body>
		</smil>

	HDS: audio only SMIL
		<smil>
			<head>
			</head>
			<body>
				<switch>
					<video src="mp3:music.mp3" system-bitrate="128000"/>
					<video src="mp3:music_256.mp3" system-bitrate="256000"/>
				</switch>
			</body>
		</smil>
	*/

	// SMIL header and footer tags
	$smil_header = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<smil>\n\t<head>\n\t</head>\n\t<body>\n\t\t<switch>\n";
	$smil_footer = "\t\t</switch>\n\t</body>\n</smil>\n";

	while ( !$recordings->EOF ) {

		$recording = $recordings->fields;

		// Is recording audio only?
		$media_type = "mp4";
		$isaudio = false;
		if ( $recording[$idx . 'mastermediatype'] == "audio" ) {
			$media_type = "mp3";
			$isaudio = true;
		}

		// Get all recording versions for this recording (or content)
		$recording_versions = getRecordingVersionsForRecording($recording['id'], $type, $isaudio, $filtercompatibility);
		if ( $recording_versions === false ) {
			$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[WARNING] No recording versions for SMIL found for " . $type . " id = " . $recording['id'], $sendmail = false);
			// recording.(content)smilstatus = null
			updateRecordingStatus($recording['id'], null, $idx . "smil");
			$recordings->MoveNext();
			continue;
		}

		// SMIL: add header
		$smil = $smil_header;

        // SMIL: add video source lines
		while ( !$recording_versions->EOF ) {

			$recording_version = $recording_versions->fields;

			$smil .= sprintf("\t\t\t<video src=\"%s:%s\" system-bitrate=\"%d\"/>\n", $media_type, $recording_version['filename'], $recording_version['bandwidth']);

			$recording_versions->MoveNext();
		}
        
        // SMIL: add footer
		$smil .= $smil_footer;

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

        // SMIL file source and destination path
		$remotedir = $app->config['recordingpath'] . ( $recording['id'] % 1000 ) . "/" . $recording['id'] . "/";
        $smil_remote_filename = $remotedir . $recording['id'] . $smil_filename_suffix . ".smil";

        // Move SMIL file to its final destination
        $err = rename($smil_filename, $smil_remote_filename);
        if ( $err === false ) {
            $debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[ERROR] SMIL file update failed.\nCOMMAND: php: rename(" . $smil_filename . ", " . $smil_remote_filename . ")\nRESULT: " . $err, $sendmail = true);
            updateRecordingStatus($recording['id'], $jconf['dbstatus_update_err'], $idx . "smil");
            $recordings->MoveNext();
            continue;
        } else {
            $debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[INFO] SMIL file updated.", $sendmail = false);
            
            // Update recording directory size
            $err_fsize = null;
            $err_fsize = directory_size($remotedir);

            if ( !$err_fsize['code'] ) {
                $msg = "[WARN] directory_size(" . $remotedir . ") failed. Message: ". $err_fsize['message'];
            } else {
                $update = array('recordingdatasize' => intval($err_fsize['value']));
                $recDoc = $app->bootstrap->getModel('recordings');
                $recDoc->select($recording['id']);
                $recDoc->updateRow($update);
                $msg = "[INFO] Recording datasize updated. Value: " . intval($err_fsize['value']);
            }
                
            $debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", $msg, false);
            
            // Chmod/chown new file
            $err_stat = MakeChmodChown($smil_remote_filename);
            if ( !$err_stat['code'] ) {
                $debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", $err_stat['message'] . "\nCOMMAND: " . $err_stat['command'] . "\nRESULT: " . $err_stat['code'], $sendmail = true);
            } else {
                $debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", $err_stat['message'] . "\nCOMMAND: " . $err_stat['command'], $sendmail = false);
            }
            
            unset($msg, $err_fsize, $err_stat, $update, $recDoc);
        }
        
		updateRecordingStatus($recording['id'], $jconf['dbstatus_copystorage_ok'], $idx . "smil");

		$recordings->MoveNext();
	}

	return true;
}

function generateLiveSMILs($type = "video", $filtercompatibility = "desktop") {
global $app, $debug, $jconf;

	if ( ( $type != "video" ) and ( $type != "content" ) ) return false;

    // Content index
	$idx = "";
	if ( $type == "content" ) $idx = "content";
    
    // Filter for compatibility
    if ( $filtercompatibility == "desktop" ) $filter_compat = " AND lfs.isdesktopcompatible = 1 ";
    if ( $filtercompatibility == "mobile" )  $filter_compat = " AND lfs.isioscompatible = 1 AND lfs.isandroidcompatible = 1 ";

	// SMILs to update
	$query = "
		SELECT
			lf.id,
			lf." . $idx . "smilstatus,
			lfs.id AS livefeedstreamid,
			lfs." . $idx . "keycode,
			lfs.status
		FROM
			livefeeds AS lf,
			livefeed_streams AS lfs
		WHERE
			( lf." . $idx . "smilstatus IS NULL OR lf." . $idx . "smilstatus = '" . $jconf['dbstatus_regenerate'] . "' ) AND
			lf.id = lfs.livefeedid AND
			( lfs.status IS NULL OR lfs.status = '" . $jconf['dbstatus_vcr_recording'] . "' )
            " . $filter_compat . "
		ORDER BY
			lf.id, lfs.weight";

	try {
        $model = $app->bootstrap->getModel('livefeeds');
        $rs = $model->safeExecute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
		return false;
	}

	// No pending SMILs
    if ( $rs->RecordCount() < 1 ) return false;

    // Convert AdoDB resource to array
    $livefeeds = adoDBResourceSetToArray($rs);    

	// SMIL header and footer tags
	$smil_header = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<smil>\n\t<head>\n\t</head>\n\t<body>\n\t\t<switch>\n";
	$smil_footer = "\t\t</switch>\n\t</body>\n</smil>\n";

	$smil_filename_suffix = "";
	if ( $idx == "content" ) $smil_filename_suffix = "_content";

	$smil = "";

	$i = 0;
	while ( $i < count($livefeeds) ) {

		// SMIL: start a new file, add header first
		$smil = $smil_header;

		$q = 0;
		while ( $livefeeds[$i]['id'] == $livefeeds[$i + $q]['id'] ) {

			// NOTE: Bandwidth calculation is fake! (no way to calc, if the bitrate is not added on website - no GUI)
			$smil .= sprintf("\t\t\t<video src=\"%s\" system-bitrate=\"%d\"/>\n", $livefeeds[$i + $q][$idx . 'keycode'], ( $q + 1 ) * 400000);

			$q++;

			if ( !isset($livefeeds[$i + $q]['id']) ) break;

		}

		// SMIL: close with footer
		$smil .= $smil_footer;

		// Write SMIL content to a temporary file
		$smil_filename = "/tmp/" . $livefeeds[$i]['id'] . $smil_filename_suffix . ".smil";
		$err = file_put_contents($smil_filename, $smil);
		if ( $err === false ) {
			$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[ERROR] SMIL live file cannot be created at " . $smil_filename, $sendmail = true);
		} else {
			$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[INFO] SMIL live file created at " . $smil_filename, $sendmail = false);
		}

        $smil_remote_filename = $app->config['livestreampath'] . $livefeeds[$i]['id'] . $smil_filename_suffix . ".smil";

        // Move SMIL file to its final location
        $err = rename($smil_filename, $smil_remote_filename);
        if ( $err === false ) {
            $debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[ERROR] SMIL file update failed.\nCOMMAND: php: rename(" . $smil_filename . ", " . $smil_remote_filename . ")\nRESULT: " . $err, $sendmail = true);
            updateRecordingStatus($recording['id'], $jconf['dbstatus_update_err'], $idx . "smil");
            $recordings->MoveNext();
            continue;
        } else {
            
            $debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[INFO] SMIL file updated.", $sendmail = false);

            // Chmod/chown new file
            $err_stat = MakeChmodChown($smil_remote_filename);
            if ( !$err_stat['code'] ) {
                $debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", $err_stat['message'] . "\nCOMMAND: " . $err_stat['command'] . "\nRESULT: " . $err_stat['code'], $sendmail = true);
            } else {
                $debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", $err_stat['message'] . "\nCOMMAND: " . $err_stat['command'], $sendmail = false);
            }
        }
            
        unset($msg, $err_stat);
            
		updateLiveFeedSMILStatus($livefeeds[$i]['id'], $jconf['dbstatus_copystorage_ok'], $type);

		$i = $i + $q;
	}

	return true;
}

function getRecordingVersionsForRecording($recordingid, $type = "recording", $filteraudioonly = false, $filtercompatibility = "desktop") {
global $app, $jconf, $debug;

	if ( ( $type != "recording" ) and ( $type != "content" ) ) return false;
    
	$mediatype = "video";
	if ( $filteraudioonly ) $mediatype = "audio";
    
    $iscontent = 0;
	if ( $type == "content" ) $iscontent = 1;

    // Filter for compatibility    
    if ( $filtercompatibility == "desktop" ) $filter_compat = " AND rv.isdesktopcompatible = 1 ";
    if ( $filtercompatibility == "mobile" )  $filter_compat = " AND rv.ismobilecompatible = 1 ";

	$query = "
		SELECT
			rv.id,
			rv.recordingid,
			rv.status,
			rv.filename,
			rv.iscontent,
			rv.bandwidth,
			rv.isdesktopcompatible,
            rv.ismobilecompatible
		FROM
			recordings_versions AS rv,
			encoding_profiles AS ep
		WHERE
			rv.recordingid = " . $recordingid . " AND
			rv.iscontent = " . $iscontent . " AND
			rv.status = '" . $jconf['dbstatus_copystorage_ok'] . "' AND
			rv.encodingprofileid = ep.id AND
			ep.type = '" . $type . "' AND
			ep.mediatype = '" . $mediatype . "'
            " . $filter_compat . "
		ORDER BY
			rv.bandwidth";

	try {
        $model = $app->bootstrap->getModel('recordings_versions');
        $rs = $model->safeExecute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $jconf['jobid_conv_control'] . ".log", "[ERROR] SQL query failed." . trim($query), $sendmail = true);
		return false;
	}

	// No pending SMILs
	if ( $rs->RecordCount() < 1 ) return false;

	return $rs;
}

?>
