<?php

// Dataintegrity check job for Videotorium v0.4 @ 2011/07/06
//	1. Check contributor images
//	2. Check recordings: media files, thumbnails
//	3. Check recording attachments
//	4. Check slides belonging to a recording

define('BASE_PATH',	realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');

// Utils
include_once('job_utils_base.php');
include_once('job_utils_log.php');

set_time_limit(0);

// Init
$app = new Springboard\Application\Cli(BASE_PATH, PRODUCTION);

// Load jobs configuration file
$app->loadConfig('modules/Jobs/config_jobs.php');
$jconf = $app->config['config_jobs'];
$myjobid = $jconf['jobid_integrity_check'];

// Log related init
$debug = Springboard\Debug::getInstance();
$debug->log($jconf['log_dir'], $myjobid . ".log", "Data integrity job started", $sendmail = FALSE);

// Check operating system - exit if Windows
if ( iswindows() ) {
	echo "ERROR: Non-Windows process started on Windows platform\n";
	exit;
}

clearstatcache();

// Exit if any STOP file appears
if ( is_file( $app->config['datapath'] . 'jobs/job_integrity_check.stop' ) or is_file( $app->config['datapath'] . 'jobs/all.stop' ) ) exit;

// Establish database connection
try {
	$db = $app->bootstrap->getAdoDB();
} catch (exception $err) {
	// Send mail alert, sleep for 15 minutes
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] No connection to DB (getAdoDB() failed). Error message:\n" . $err, $sendmail = true);
	// Sleep 15 mins then resume
	exit;
}
$db_close = TRUE;

// -------------------------------------------------------------------------------

$log_summary = "";

$time_start = time();

// Check contributor images
/*if ( check_contributor_images() === FALSE ) {
	tools::log(LOGPATH_JOBS, LOG_FILE, "[ERROR] Data integrity check interrupted due to error. Manual restart is required.\nCheck log files.", TRUE);
	exit;
} */

// Check recordings one by one

$recording = array();
$recordings = array();

$query = "
	SELECT
		a.id,
		a.userid,
		a.mastermediatype,
		a.mastervideoextension,
		a.status,
		a.masterstatus,
		a.numberofindexphotos,
		a.videoreslq,
		a.videoreshq,
		a.videoresmobile,
		a.hascontent,
		a.contentmastermediatype,
		a.contentmastervideoextension,
		a.contentstatus,
		a.contentmasterstatus,
		a.contentvideoreslq,
		a.contentvideoreshq
		b.email,
		a.status
	FROM
		recordings as a, users as b
	WHERE
		( a.status = \"" . $jconf['dbstatus_copystorage_ok'] . "\" OR a.status = \"" . $jconf['dbstatus_markedfordeletion'] . "\" ) AND
		( a.masterstatus = \"" . $jconf['dbstatus_copystorage_ok'] . "\" OR a.masterstatus = \"" . $jconf['dbstatus_markedfordeletion'] . "\" ) AND
		a.userid = b.id
	ORDER BY a.id";
//and a.id = 2972

try {
	$recordings = $db->Execute($query);
} catch (exception $err) {
	$msg = "[ERROR] Data integrity check interrupted due to error. Manual restart is required.\nCheck log files.\n\n";
// !!!
//	tools::log(LOGPATH_JOBS, LOG_FILE, $msg . "[ERROR] No connection to DB (getAdoDB() failed). Error message:\n" . $err, TRUE);
	exit;
}

$num_checked_recs = 0;
$num_onstorage_recs = 0;
$num_recordings = $recordings->RecordCount();

while ( !$recordings->EOF ) {

	$recording = $recordings->fields;

	$rec_id = $recording['id'];

	$recording_summary = "";

	$recording_path = $app->config['recordingpath'] . ( $rec_id % 1000 ) . "/" . $rec_id . "/";

	// Check recording directory
	if ( !file_exists($recording_path) ) {
		$recording_summary .= "ERROR: recording path does not exist (" . $recording_path . ")\n";
		$recordings->MoveNext();
		continue;
	}

	// Check media files
	unset($media_audio_only);
	unset($media_f4v_mobile);
	unset($media_f4v_low);
	unset($media_f4v_high);
	if ( $recording_element['mastermediatype'] != "videoonly" ) {
		$media_audio_only = $recording_path . $rec_id . "_" . $rec_element_id . ".mp3";
	}
	if ( $recording_element['mastermediatype'] != "audio" ) {
		if ( !empty($recording_element['videoresmobile']) ) {
			$media_f4v_mobile = $recording_path . $rec_id . "_" . $rec_element_id . "_mobile.mp4";
		}
		if ( !empty($recording_element['videoreslq']) ) {
			$media_f4v_low = $recording_path . $rec_id . "_" . $rec_element_id . ".f4v";
		}
		if ( !empty($recording_element['videoreshq']) ) {
			$media_f4v_high = $recording_path . $rec_id . "_" . $rec_element_id . "_hq.f4v";
		}
	}
	$media_master = $recording_path . "master/" . $rec_id . "_" . $rec_element_id . "." . $recording_element['mastervideoextension'];

	// Check audio only version
	if ( !empty($media_audio_only) ) {
		if ( !file_exists($media_audio_only) ) {
			$recording_summary .= "ERROR: audio only version does not exist (" . $media_audio_only . ")\n";
		} elseif ( filesize($media_audio_only) == 0 ) {
			$recording_summary .= "ERROR: audio only version zero size (" . $media_audio_only . ")\n";
		}
	}

	// Check mobile quality file
	if ( !empty($media_f4v_mobile) ) {
		if ( !file_exists($media_f4v_mobile) ) {
			$recording_summary .= "ERROR: media file does not exist (" . $media_f4v_mobile . ")\n";
		} elseif ( filesize($media_f4v_low) == 0 ) {
			$recording_summary .= "ERROR: media file zero size (" . $media_f4v_mobile . ")\n";
		}
	}

	// Check normal quality file
	if ( !empty($media_f4v_low) ) {
		if ( !file_exists($media_f4v_low) ) {
			$recording_summary .= "ERROR: media file does not exist (" . $media_f4v_low . ")\n";
		} elseif ( filesize($media_f4v_low) == 0 ) {
			$recording_summary .= "ERROR: media file zero size (" . $media_f4v_low . ")\n";
		}
	}

	// Check high quality file
	if ( !empty($media_f4v_high) ) {
		if ( !file_exists($media_f4v_high) ) {
			$recording_summary .= "ERROR: media file does not exist (" . $media_f4v_high . ")\n";
		} elseif ( filesize($media_f4v_high) == 0 ) {
			$recording_summary .= "ERROR: media file zero size (" . $media_f4v_high . ")\n";
		}
	}

	// Check master media file
	if ( !file_exists($media_master) ) {
		$recording_summary .= "ERROR: master media file does not exist (" . $media_master . ")\n";
	} elseif ( filesize($media_master) == 0 ) {
		$recording_summary .= "ERROR: master media file zero size (" . $media_master . ")\n";
	}

	// Check video thumbnails
	if ( check_video_thumbnails($rec_id, $rec_element_id, $recording_element['numberofindexphotos']) === FALSE ) {
		tools::log(LOGPATH_JOBS, LOG_FILE, "[ERROR] Data integrity check interrupted due to error. Manual restart is required.\nCheck log files.", TRUE);
		exit;
	}

	// Check content media files
	unset($content_mp4_low);
	unset($content_mp4_high);
	if ( $recording_element['contentstatus'] == DBSTATUS_COPYSTORAGE_OK ) {
		if ( !empty($recording_element['contentvideoreslq']) ) {
			$content_mp4_low = $recording_path . $rec_id . "_" . $rec_element_id . "_content.mp4";
		}
		if ( !empty($recording_element['contentvideoreshq']) ) {
			$content_mp4_high = $recording_path . $rec_id . "_" . $rec_element_id . "_content_hq.mp4";
		}

		// Check low resolution file
		if ( !empty($content_mp4_low) ) {
			if ( !file_exists($content_mp4_low) ) {
				$recording_summary .= "ERROR: content media file does not exist (" . $content_mp4_low . ")\n";
			} elseif ( filesize($content_mp4_low) == 0 ) {
				$recording_summary .= "ERROR: content media file zero size (" . $content_mp4_low . ")\n";
			}
		}

		// Check high resolution file
		if ( !empty($content_mp4_high) ) {
			if ( !file_exists($content_mp4_high) ) {
				$recording_summary .= "ERROR: content media file does not exist (" . $content_mp4_high . ")\n";
			} elseif ( filesize($content_mp4_high) == 0 ) {
				$recording_summary .= "ERROR: content media file zero size (" . $content_mp4_high . ")\n";
			}
		}
	}

	// Check content master media file
	if ( $recording_element['contentmasterstatus'] == DBSTATUS_COPYSTORAGE_OK ) {
		$content_master = $recording_path . "master/" . $rec_id . "_" . $rec_element_id . "_content." . $recording_element['contentmastervideoextension'];

		if ( !file_exists($content_master) ) {
			$recording_summary .= "ERROR: content master media file does not exist (" . $content_master . ")\n";
		} elseif ( filesize($content_master) == 0 ) {
			$recording_summary .= "ERROR: content master media file zero size (" . $content_master . ")\n";
		}
	}

	// Check all attachments
	if ( check_attachments($rec_id) === FALSE ) {
		tools::log(LOGPATH_JOBS, LOG_FILE, "[ERROR] Data integrity check interrupted due to error. Manual restart is required.\nCheck log files.", TRUE);
		exit;
	}

	if ( !empty($recording_summary) ) {
		$log_summary .= "Recording/element: " . $rec_id . " / " . $rec_element_id . " (user: " . $recording['email'] . ") - " . $recording['status'] . "\n\n";
		$log_summary .= $recording_summary . "\n";
	}

	$num_checked_recs++;

	if ( $recording['status'] == "onstorage" ) $num_onstorage_recs++;

	$recordings->MoveNext();
}

// Summarize check statistics
$log_summary .= "Number of recordings: " . $num_recordings . "\n";
$log_summary .= "Number of checked recordings: " . $num_checked_recs . "\n";
$log_summary .= "Number of \"onstorage\" recordings: " . $num_onstorage_recs . "\n\n";

// Calculate check duration
$duration = time() - $time_start;
$log_summary .= "Check duration: " . secs2hms($duration) . "\n";

// !!!!
tools::log(LOGPATH_JOBS, LOG_FILE, "Data integrity check results:\n\n" . $log_summary, TRUE);

//echo $log_summary . "\n";

if ( $db_close ) {
	$db->close();
}

exit;

function check_contributor_images() {
 global $api, $db, $log_summary;

	$contributor_image_path = "/srv/videotorium/httpdocs/contributors/";

	$query = "
		SELECT
			id,
			contributorid,
			filename
		FROM contributor_images
		WHERE 1
		ORDER BY contributorid, id ASC
	";

	try {
		$images = $db->Execute($query);
	} catch (exception $err) {
		tools::log(LOGPATH_JOBS, LOG_FILE, "[ERROR] SQL query failed. Query: \n" .  trim($query) . "\n\nError message: \n" . $err, FALSE);
		return FALSE;
	}

	$num_checked_images = 0;
	$num_images = $images->RecordCount();

	while ( !$images->EOF ) {

		$recording_summary = "";

		$id = $images->fields['id'];
		$contributor_id = $images->fields['contributorid'];
		$filename = $images->fields['filename'];

		$image_notfin = FALSE;
		if ( stripos($filename, "recordings/") === FALSE ) {
			$image_file = $contributor_image_path . ( $contributor_id % 1000 ) . "/" . $contributor_id . "/" . $contributor_id . "_" . $id . ".jpg";
		} else {
			$image_file = "/srv/videotorium/httpdocs/" . $filename;
			$image_notfin = TRUE;
		}

		if ( !file_exists($image_file) ) {
			$recording_summary .= "ERROR: contributor thumb does not exist (" . $image_file . ")\n";
		} elseif ( filesize($image_file) == 0 ) {
			$recording_summary .= "ERROR: contributor thumb zero size (" . $image_file . ")\n";
		}

		if ( $image_notfin ) {
			$recording_summary .= "WARNING: contributor image is not finalized!\n";
		}

		if ( !empty($recording_summary) ) {
			$log_summary .= "Contributor image: contribID = " . $contributor_id . " / imageID = " . $id . "\nFilename = " . $filename . "\n";
			$log_summary .= $recording_summary . "\n";
		}

		$num_checked_images++;

		$images->MoveNext();
	}

	$log_summary .= "Number of contributor images: " . $num_images . "\n";
	$log_summary .= "Number of checked OK images: " . $num_checked_images . "\n\n";

	return TRUE;
}

function check_video_thumbnails($rec_id, $rec_element_id, $num_thumbs) {
 global $recording_summary;

/*
$app->config['config_jobs']['videothumbnailresolutions']['4:3']
config.php:
  'videothumbnailresolutions' => array(
    '4:3'    => '220x130',
    'wide'   => '300x168',
    'player' => '618x348',
  ),
*/

// + Original thumb check: ./original/

	$thumb_path = RECORDINGPATH . ( $rec_id % 1000 ) . "/" . $rec_id . "/indexpics/";

	for ( $i = 1; $i <= $num_thumbs; $i++) {

		$thumb_filename = $rec_id . "_" . $rec_element_id . "_" . $i . ".jpg";

		$thumb_tocheck = $thumb_path . VIDEO_THUMB_43_RES . "/" . $thumb_filename;
		if ( !file_exists($thumb_tocheck) ) {
			$recording_summary .= "ERROR: thumb does not exist (" . $thumb_tocheck . ")\n";
		} elseif ( filesize($thumb_tocheck) == 0 ) {
			$recording_summary .= "ERROR: thumb zero size (" . $thumb_tocheck . ")\n";
		}

		$thumb_tocheck = $thumb_path . VIDEO_THUMB_WIDE_RES . "/" . $thumb_filename;
		if ( !file_exists($thumb_tocheck) ) {
			$recording_summary .= "ERROR: thumb does not exist (" . $thumb_tocheck . ")\n";
		} elseif ( filesize($thumb_tocheck) == 0 ) {
			$recording_summary .= "ERROR: thumb zero size (" . $thumb_tocheck . ")\n";
		}

		$thumb_tocheck = $thumb_path . VIDEO_THUMB_PLAYER_RES . "/" . $thumb_filename;
		if ( !file_exists($thumb_tocheck) ) {
			$recording_summary .= "ERROR: thumb does not exist (" . $thumb_tocheck . ")\n";
		} elseif ( filesize($thumb_tocheck) == 0 ) {
			$recording_summary .= "ERROR: thumb zero size (" . $thumb_tocheck . ")\n";
		}

	}

	return TRUE;
}

function check_attachments($rec_id) {
 global $db, $recording_summary, $attachment_ids;

	$attachment_ids = array();

	$attachment_path = RECORDINGPATH . ( $rec_id % 1000 ) . "/" . $rec_id . "/attached_documents/";

	$query = "
		SELECT
			id,
			recordingid,
			masterextension,
			status
		FROM attached_documents
		WHERE
			recordingid = " . $rec_id . " AND
			( status = \"onstorage\" OR status = \"markedfordeletion\" )
		ORDER BY id
	";

	try {
		$attachments = $db->Execute($query);
	} catch (exception $err) {
		tools::log(LOGPATH_JOBS, LOG_FILE, "[ERROR] SQL query failed. Query: \n" .  trim($query) . "\n\nError message: \n" . $err, FALSE);
		return FALSE;
	}

	while ( !$attachments->EOF ) {

		$attachment = $attachments->fields;
		$attachment_file = $attachment_path . $attachment['id'] . "." . $attachment['masterextension'];

		array_push($attachment_ids, $attachment['id']);

		if ( !file_exists($attachment_file) ) {
			$recording_summary .= "ERROR: attachment file missing (" . $attachment_file . ")\n";
		} elseif ( filesize($attachment_file) == 0 ) {
			$recording_summary .= "ERROR: attachment file zero size (" . $attachment_file . ")\n";
		}

		$attachments->MoveNext();
	}

	return TRUE;
}

?>
