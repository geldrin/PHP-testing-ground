<?php
// Dataintegrity check job for VideoSquare
//	1. Check contributor images
//	2. Check recordings: media files, thumbnails
//	3. Check recording attachments
define('BASE_PATH',	realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false);
define('DEBUG', false);

include_once(BASE_PATH . 'libraries/Springboard/Application/Cli.php');

// Utils
include_once(BASE_PATH . 'modules/Jobs/job_utils_base.php');
include_once(BASE_PATH . 'modules/Jobs/job_utils_log.php');
include_once(BASE_PATH . 'modules/Jobs/job_utils_status.php');
include_once(BASE_PATH . 'modules/Jobs/job_utils_media.php');

set_time_limit(0);

// Init
$app = new Springboard\Application\Cli(BASE_PATH, DEBUG);

// Load jobs configuration file
$app->loadConfig('modules/Jobs/config_jobs.php');
$jconf = $app->config['config_jobs'];
$myjobid = $jconf['jobid_integrity_check'];

// Log related init
$debug = Springboard\Debug::getInstance();
$debug->log($jconf['log_dir'], $myjobid . ".log", "Data integrity job started", $sendmail = FALSE);
$recordingsModel = $app->bootstrap->getModel('recordings');
$num_errors = 0;

// Check operating system - exit if Windows
if ( iswindows() ) {
	echo "ERROR: Non-Windows process started on Windows platform\n";
	exit;
}

clearstatcache();

// Exit if any STOP file appears
if ( is_file( $app->config['datapath'] . 'jobs/job_integrity_check.stop' ) or is_file( $app->config['datapath'] . 'jobs/all.stop' ) ) exit;

// Establish database connection
$db = null;
$db = db_maintain();
/*try {
	$db = $app->bootstrap->getAdoDB();
} catch (exception $err) {
	// Send mail alert, sleep for 15 minutes
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] No connection to DB (getAdoDB() failed). Error message:\n" . $err, $sendmail = TRUE);
	exit;
}*/
$db_close = TRUE;

$log_summary  = "NODE: " . $app->config['node_sourceip'] . "\n";
$log_summary .= "SITE: " . $app->config['baseuri'] . "\n";
$log_summary .= "JOB: " . $myjobid . "\n\n";

$time_start = time();

// Check contributor images
// Avatars: TODO
// 			!!	Not yet implemented in Videosquare	!!
/*if ( check_contributor_images() === FALSE ) {
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Data integrity check interrupted due to error. Manual restart is required.\nCheck log files.", $sendmail = FALSE);
	exit;
} */

// Check recordings one by one
$rec = array();
$recordings = array();

$query = "
	SELECT
		a.id,
		a.userid,
		a.status,
		a.contentstatus,
		a.masterstatus,
		a.masterlength,
		a.contentmasterlength,
		a.mobilestatus,
		a.contentmasterstatus,
		a.mastermediatype,
		a.mastervideores,
		a.mastervideoextension,
		a.numberofindexphotos,
		a.contentmastermediatype,
		a.contentmastervideores,
		a.contentmastervideoextension,
		b.email
	FROM
		recordings as a, users as b
	WHERE
		( a.status = \"" . $jconf['dbstatus_copystorage_ok'] . "\" OR a.status = \"" . $jconf['dbstatus_markedfordeletion'] . "\" ) AND
		( a.masterstatus = \"" . $jconf['dbstatus_copystorage_ok'] . "\" OR a.masterstatus = \"" . $jconf['dbstatus_markedfordeletion'] . "\" ) AND
		a.userid = b.id
	ORDER BY a.id";

try {
	$recordings = $db->Execute($query);
} catch (exception $err) {
	$msg = "[ERROR] Data integrity check interrupted due to error. Manual restart is required.\nCheck log files.\n\n";
	$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] No connection to DB (getAdoDB() failed). Error message:\n" . $err, $sendmail = TRUE);
	exit;
}

$num_checked_recs = 0;
$num_onstorage_recs = 0;
$num_recordings = $recordings->RecordCount();

while ( !$recordings->EOF ) {
	// Get current field from the query
	$rec = $recordings->fields;
	$rec_id = $rec['id'];
	
	// init log string
	$recording_summary = "";

	$recording_path = $app->config['recordingpath'] . ( $rec_id % 1000 ) . "/" . $rec_id . "/";

	// Check recording directory
	if ( !file_exists($recording_path) ) {
		$recording_summary .= "ERROR: recording path does not exist (" . $recording_path . ")\n";
		$recordings->MoveNext();
		continue;
	} elseif  ($rec['status'] !== $jconf['dbstatus_copystorage_ok']) {
		$recordings->MoveNext();
		continue;
	}
	// Threshold variable
	$threshold = 0;

	// Check media files	
	$hq_record_available = FALSE;
	$hq_content_available = FALSE;
	$hq_mobile_available = FALSE;
	
	// build filenames
	$record_audio_only = $recording_path . $rec_id . "_audio.mp3";
	$record_mobile_lq = $recording_path . $rec_id . "_mobile_lq.mp4";
	$record_mobile_hq = $recording_path . $rec_id . "_mobile_hq.mp4";
	$record_lq = $recording_path . $rec_id . "_video_lq.mp4";	
	$record_hq = $recording_path . $rec_id . "_video_hq.mp4";	
	$content_lq = $recording_path . $rec_id . "_content_lq.mp4";
	$content_hq = $recording_path . $rec_id . "_content_hq.mp4";

	if ($rec['mastermediatype'] == "audio") {
		$master_record = $recording_path . "master/" . $rec_id . "_audio." . $rec['mastervideoextension'];
	} else {
		$master_record = $recording_path . "master/" . $rec_id . "_video." . $rec['mastervideoextension'];
	}
	
	// Check audio only version
	if ( $rec['mastermediatype'] != "videoonly" ) {
		if ( !file_exists($record_audio_only) ) {
			$recording_summary .= "ERROR: audio only version does not exist (" . $record_audio_only . ")\n";
		} elseif ( filesize($record_audio_only) == 0 ) {
			$recording_summary .= "ERROR: audio only version zero size (" . $record_audio_only . ")\n";
		}
	}
	
	// Check master media file
	if ( !file_exists($master_record) ) {
		$recording_summary .= "ERROR: master media file does not exist (" . $master_record . ")\n";
	} elseif ( filesize($master_record) == 0 ) {
		$recording_summary .= "ERROR: master media file zero size (" . $master_record . ")\n";
	} elseif ($rec['mastermediatype'] != "audio") {
		if (is_res1_gt_res2($rec['mastervideores'], $jconf['profile_video_lq']['video_bbox'])) {
			$hq_record_available = TRUE;
		}
		if (is_res1_gt_res2($rec['mastervideores'], $jconf['profile_mobile_lq']['video_bbox'])) {
			$hq_mobile_available = TRUE;
		}
		
		$mastervideolength = check_length($master_record, $msg);
		$threshold = ( round($mastervideolength * 0.005) < 1 ? 1 : round($mastervideolength * 0.005) );
		
		if (abs($mastervideolength - $rec['masterlength']) > $threshold) {
			$recording_summary .= "ERROR: invalid database value (". $master_record ." - ". $mastervideolength ."s, db - ". $rec['masterlength'] ."s)\n";
		}
	}
	
	if ( $rec['mastermediatype'] != "audio" ) {
		// Check normal quality file
		if ( !file_exists($record_lq) ) {
			$recording_summary .= "ERROR: media file does not exist (" . $record_lq . ")\n";
		} elseif ( filesize($record_lq) == 0 ) {
			$recording_summary .= "ERROR: media file zero size (" . $record_lq . ")\n";
		} else {
			$record_lq_duration = check_length($record_lq, $msg);
			if (abs($mastervideolength - $record_lq_duration) > $threshold) {
				$recording_summary .= "ERROR: invalid video duration (". $record_lq ." - ". $record_lq_duration ."s, db - ". $rec['masterlength'] ."s)\n";
			}
		}
	
		// Check high quality file
		if ( $hq_record_available) {
			if ( !file_exists($record_hq) ) {
				$recording_summary .= "ERROR: media file does not exist (" . $record_hq . ")\n";
			} elseif ( filesize($record_hq) == 0 ) {
				$recording_summary .= "ERROR: media file zero size (" . $record_hq . ")\n";
			} else {
				$record_hq_duration = check_length($record_hq, $msg);
				if (abs($mastervideolength - $record_hq_duration) > $threshold) {
					$recording_summary .= "ERROR: invalid video duration (". $record_hq ." - ". $record_hq_duration ."s, db - ". $rec['masterlength'] ."s)\n";
				}
			}
		}
	}

	// Check content master media file
	if ( $rec['contentmasterstatus'] == $jconf['dbstatus_copystorage_ok'] ) {	// masterstatus = "onstorage"
		$content_master = $recording_path . "master/" . $rec_id . "_content." . $rec['contentmastervideoextension'];

		if ( !file_exists($content_master) ) {
			$recording_summary .= "ERROR: content master media file does not exist (" . $content_master . ")\n";
		} elseif ( filesize($content_master) == 0 ) {
			$recording_summary .= "ERROR: content master media file zero size (" . $content_master . ")\n";
		} else {
			if(is_res1_gt_res2($rec['contentmastervideores'], $jconf['profile_content_lq']['video_bbox'])) {
				$hq_content_available = TRUE;
			}
			
			$mastercontentlength = check_length($content_master, $msg);
			if (abs($mastercontentlength - $rec['contentmasterlength']) > $threshold) {
				$recording_summary .= "ERROR: invalid database value (". $content_master ." - ". $mastercontentlength ."s, db - ". $rec['contentmasterlength'] ."s)\n";
			}
		}
	
	}
	
	if ( $rec['contentstatus'] == $jconf['dbstatus_copystorage_ok'] ) {
		// Check low resolution file
		if ( !file_exists($content_lq) ) {
			$recording_summary .= "ERROR: content media file does not exist (". $content_lq .")\n";
		} elseif ( filesize($content_lq) == 0 ) {
			$recording_summary .= "ERROR: content media file zero size (". $content_lq .")\n";
		} else {
			$content_lq_duration = check_length($content_lq, $msg);
			if (abs($mastercontentlength - $content_lq_duration) > $threshold) {
				$recording_summary .= "ERROR: invalid content duration (". $content_lq ." - ". $content_lq_duration ."s, db - ". $rec['masterlength'] ."s)\n";
			}
		}

		// Check high resolution file
		if ($hq_content_available) {
			if ( !file_exists($content_hq) ) {
				$recording_summary .= "ERROR: content media file does not exist (" . $content_hq . ")\n";
			} elseif ( filesize($content_hq) == 0 ) {
				$recording_summary .= "ERROR: content media file zero size (" . $content_hq . ")\n";
			} else {
				$content_hq_duration = check_length($content_hq, $msg);
				if (abs($mastercontentlength - $content_hq_duration) > $threshold) {
					$recording_summary .= "ERROR: invalid content duration (". $content_hq ." - ". $content_hq_duration ."s, db - ". $rec['contentmasterlength'] ."s)\n";
				}
			}
		}
	}
	
	if ($rec['mobilestatus'] == $jconf['dbstatus_copystorage_ok']) {
		// Calculate mobile duration
		if ($rec['contentstatus'] == $jconf['dbstatus_copystorage_ok']) {
			$iscontentlonger = ($mastervideolength > $mastercontentlength ? false : true);
			$lengthfull = ($iscontentlonger === true ? $mastercontentlength : $mastervideolength);
		} else {
			$lengthfull = $mastervideolength;
			$iscontentlonger = false;
		}
		
		// Check mobile normal quality file
		if ( !file_exists($record_mobile_lq) ) {
			$recording_summary .= "ERROR: media file does not exist (" . $record_mobile_lq . ")\n";
		} elseif ( filesize($record_mobile_lq) == 0 ) {
			$recording_summary .= "ERROR: media file zero size (" . $record_mobile_lq . ")\n";
		} else {
			$mobile_lq_duration = check_length($record_mobile_lq, $msg);
			if (abs($lengthfull - $mobile_lq_duration) > $threshold) {
				$recording_summary .= "ERROR: invalid mobile duration (". $record_mobile_lq ." - ". $mobile_lq_duration ."s, db/". ($iscontentlonger ? "content" : "video") ." - ". $lengthfull ."s)\n";
			}
		}
		// Check mobile high quality file
		if ($hq_mobile_available) {
			if ( !file_exists($record_mobile_hq) ) {
				$recording_summary .= "ERROR: media file does not exist (" . $record_mobile_hq . ")\n";
			} elseif ( filesize($record_mobile_hq) == 0 ) {
				$recording_summary .= "ERROR: media file zero size (" . $record_mobile_hq . ")\n";
			} else {
				$mobile_hq_duration = check_length($record_mobile_hq, $msg);
				if (abs($lengthfull - $mobile_hq_duration) > $threshold) {
					$recording_summary .= "ERROR: invalid mobile duration (". $record_mobile_hq ." - ". $mobile_hq_duration ."s, db/". ($iscontentlonger ? "content" : "video") ." - ". $lengthfull ."s)\n";
				}
			}
		}
	}
	
	// Check video thumbnails
	check_video_thumbnails($rec_id, $rec['numberofindexphotos']);
	
	// Check all attachments
	check_attachments($rec_id);
	
	if ( !empty($recording_summary) ) {
		$log_summary .= "Recording: " . $rec_id . " (user: " . $rec['email'] . ") - " . $rec['status'] . "\n\n";
		$log_summary .= $recording_summary . "\n";
		$num_errors++;
	}

	$num_checked_recs++;

	if ( $rec['status'] == "onstorage" ) $num_onstorage_recs++;
	
	$recordings->MoveNext();
}

// Summarize check statistics
$log_summary .= "\nNumber of recordings: " . $num_recordings . "\n";
$log_summary .= "Number of checked recordings: " . $num_checked_recs . "\n";
$log_summary .= "Number of \"onstorage\" recordings: " . $num_onstorage_recs . "\n";
$log_summary .= "Number of faulty recordings: ". $num_errors ."\n\n";

// Calculate check duration
$duration = time() - $time_start;
$log_summary .= "Check duration: " . secs2hms($duration) . "\n";

$debug->log($jconf['log_dir'], ($myjobid . ".log"), "Data integrity check results:\n\n" . $log_summary, $sendmail = TRUE);

// Close DB connection if open
if ( is_resource($db->_connectionID) ) $db->close();

exit;

//---< Functions >---------------------------------------------------------------------------------
function check_length($mediafile, &$message) {
	global $recordingsModel;
	$length = 0;
	try {
		$recordingsModel->analyze($mediafile);
		$metadata = $recordingsModel->metadata;
		if (empty($metadata['masterlength'])) {
			$message .= "ERROR: metadata cannot be retrieved (". $mediafile .")\n";
			return false;
		} else {
			$length = $metadata['masterlength'];
		}
	} catch ( Exception $ex) {
		$message .= "ERROR: Recording analysis failed ( ". $mediafile ." ):\n". $ex->getMessage() ."\n";
	}
	return $length;
}

function check_contributor_images() {
 global $db, $log_summary;

	$db = db_maintain();
 
	$contributor_image_path = realpath("/srv/storage/videosquare.eu/contributors/");

	$query = "
		SELECT
			id,
			contributorid,
			indexphotofilename
		FROM contributor_images
		WHERE 1
		ORDER BY contributorid, id ASC
	";

	try {
		$images = $db->Execute($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed. Query: \n" .  trim($query) . "\n\nError message: \n" . $err, $sendmail = TRUE);
		return FALSE;
	}
	
	$num_checked_images = 0;
	$num_images = $images->RecordCount();

	while ( !$images->EOF ) {

		$recording_summary = "";

		$id = $images->fields['id'];
		$contributor_id = $images->fields['contributorid'];
		$filename = $images->fields['indexphotofilename'];

		$image_notfin = FALSE;
		if ( stripos($filename, "recordings/") === FALSE ) {
			$image_file = $contributor_image_path . ( $contributor_id % 1000 ) . "/" . $contributor_id . "/" . $contributor_id . "_" . $id . ".jpg";
		} else {
			$image_file = "/srv/storage/videosquare.eu/contributors/" . $filename;
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

function check_video_thumbnails($rec_id, $num_thumbs) {
	global	$recording_summary;
	global	$app;
	
	$thumb_path = $app->config['recordingpath'] . ( $rec_id % 1000 ) . "/" . $rec_id . "/indexpics/";
	$num_thumbs_small = 0;
	$num_thumbs_wide = 0;
	$num_thumbs_player = 0;
	$num_thumbs_original = 0;

	for ( $i = 1; $i <= $num_thumbs; $i++) {

		$thumb_filename = $rec_id . "_" . $i . ".jpg";
		
		// check 4:3[] thumbnails
		//$thumb_tocheck = $thumb_path . $app->config_jobs['thumb_video_small'|'thumb_video_medium'|'thumb_video_large']
		$thumb_tocheck = $thumb_path . $app->config['videothumbnailresolutions']['4:3'] . "/" . $thumb_filename;
		if ( !file_exists($thumb_tocheck) ) {
			$recording_summary .= "ERROR: thumb does not exist (" . $thumb_tocheck . ")\n";
		} elseif ( filesize($thumb_tocheck) == 0 ) {
			$recording_summary .= "ERROR: thumb zero size (" . $thumb_tocheck . ")\n";
		} else {
			$num_thumbs_small++;
		}
		// check 16:9[] thumbnails
		$thumb_tocheck = $thumb_path . $app->config['videothumbnailresolutions']['wide'] . "/" . $thumb_filename;
		if ( !file_exists($thumb_tocheck) ) {
			$recording_summary .= "ERROR: thumb does not exist (" . $thumb_tocheck . ")\n";
		} elseif ( filesize($thumb_tocheck) == 0 ) {
			$recording_summary .= "ERROR: thumb zero size (" . $thumb_tocheck . ")\n";
		} else {
			$num_thumbs_wide++;
		}
		// check normal[] thumbnails
		$thumb_tocheck = $thumb_path . $app->config['videothumbnailresolutions']['player'] . "/" . $thumb_filename;
		if ( !file_exists($thumb_tocheck) ) {
			$recording_summary .= "ERROR: thumb does not exist (" . $thumb_tocheck . ")\n";
		} elseif ( filesize($thumb_tocheck) == 0 ) {
			$recording_summary .= "ERROR: thumb zero size (" . $thumb_tocheck . ")\n";
		} else {
			$num_thumbs_player++;
		}
		// check original thumbnails
		$thumb_tocheck = $thumb_path . "original/" . $thumb_filename;
		if ( !file_exists($thumb_tocheck) ) {
			$recording_summary .= "ERROR: thumb does not exist (" . $thumb_tocheck . ")\n";
		} elseif ( filesize($thumb_tocheck) == 0 ) {
			$recording_summary .= "ERROR: thumb zero size (" . $thumb_tocheck . ")\n";
		} else {
			$num_thumbs_original++;
		}
	}
	// check missing thumbnails and report possible errors
	if ($num_thumbs != ($num_thumbs_small & $num_thumbs_wide & $num_thumbs_player & $num_thumbs_original)) {
		$recording_summary .= "Found thumbnails:\n\tsmall: " . $num_thumbs_small ."\n\twide: ". $num_thumbs_wide ." \n\tplayer: ". 	$num_thumbs_player ."\n\toriginal: ". $num_thumbs_original ."\n\tnumber of index photos: ". $num_thumbs .".\n";
		return FALSE;
	}
	return TRUE;
}

function check_attachments($rec_id) {
 global $db, $recording_summary, $app;

	$db = db_maintain();

	$attachment_ids = array();

	$attachment_path = $app->config['recordingpath'] . ( $rec_id % 1000 ) . "/" . $rec_id . "/attachments/";

	$query = "
		SELECT
			id,
			recordingid,
			masterfilename,
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
		$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] SQL query failed. Query: \n" .  trim($query) . "\n\nError message: \n" . $err, $sendmail = TRUE);
		return FALSE;
	}
	$num_attachments = 0;
	while ( !$attachments->EOF ) {
		$attachment = $attachments->fields;
		$attachment_file = $attachment_path . $attachment['id'] . "." . $attachment['masterextension'];
		
		$attachment_ids[] = $attachment['id'];
		
		if ( !file_exists($attachment_file) ) {
			$recording_summary .= "ERROR: attachment file missing (" . $attachment_file . ")\n";
		} elseif ( filesize($attachment_file) == 0 ) {
			$recording_summary .= "ERROR: attachment file zero size (" . $attachment_file . ")\n";
		} else {
			$num_attachments++;
		}

		$attachments->MoveNext();
	}
	
	if ($num_attachments <> $attachments->RecordCount()) {
		$recording_summary .= "Found attachments: ". $num_attachments ."/". count($attachment_ids) ." in ". $rec_id ."\n";
		return FALSE;
	}
	
	return TRUE;
}

function is_res1_gt_res2($res1, $res2) {
	$tmp = explode('x', strtolower($res1));
	$resx1 = $tmp[0] + 0;
	$resy1 = $tmp[1] + 0;
	$tmp = explode('x', strtolower($res2));
	$resx2 = $tmp[0] + 0;
	$resy2 = $tmp[1] + 0;
	
	if (($resx1 > $resx2) && ($resy1 > $resy2))
		return true;
	else 
		return false;
}

?>
