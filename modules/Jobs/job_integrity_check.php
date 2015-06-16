<?php
///////////////////////////////////////////////////////////////////////////////////////////////////
// Data integrity check for converter2.0
///////////////////////////////////////////////////////////////////////////////////////////////////
//  1. Check contributor images (placeholder)
//  2. Check recordings: media files, thumbnails
//  3. Check recording attachments
///////////////////////////////////////////////////////////////////////////////////////////////////

define('BASE_PATH', realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false);
define('DEBUG', false);
define('THRESHOLD', 0.005);

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
$jconf   = $app->config['config_jobs'];
$logdir  = $jconf['log_dir'];
$logpath = $jconf['jobid_integrity_check'] .".log";


// Log related init
$debug = Springboard\Debug::getInstance();
$debug->log($logdir, $logpath, "Data integrity job started", $sendmail = FALSE);
$recordingsModel = $app->bootstrap->getModel('recordings');

$num_errors         = 0;
$num_checked_recs   = 0;
$num_onstorage_recs = 0;
$num_recordings     = 0;

// Check operating system - exit if Windows
if ( iswindows() ) {
  echo "[ERROR] Non-Windows process started on Windows platform\n";
  exit;
}

clearstatcache();

// Exit if any STOP file appears
if ( is_file( $app->config['datapath'] . 'jobs/job_integrity_check.stop' ) or is_file( $app->config['datapath'] . 'jobs/all.stop' ) ) exit;

// Establish database connection
$db = null;
$db = db_maintain();
$db_close = TRUE;

$log_summary  = "NODE: " . $app->config['node_sourceip'] . "\n";
$log_summary .= "SITE: " . $app->config['baseuri'] . "\n";
$log_summary .= "JOB: " . $jconf['jobid_integrity_check'] . "\n\n";

$time_start = time();

// Check contributor images
// Avatars: TODO
//      !!  Not yet implemented in Videosquare  !!
/*if ( check_contributor_images() === FALSE ) {
  $debug->log($logdir, $logpath, "[ERROR] Data integrity check interrupted due to error. Manual restart is required.\nCheck log files.", $sendmail = FALSE);
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
  (
    a.status = \"" . $jconf['dbstatus_copystorage_ok'] . "\" OR
    a.status = \"" . $jconf['dbstatus_markedfordeletion'] . "\"
  ) AND (
    a.masterstatus = \"" . $jconf['dbstatus_copystorage_ok'] . "\" OR
    a.masterstatus = \"" . $jconf['dbstatus_markedfordeletion'] . "\" OR
    a.masterstatus = \"" . $jconf['dbstatus_uploaded'] ."\"
  ) AND
    a.userid = b.id
  ORDER BY a.id";

try {
  $recordings = $db->Execute($query);
} catch (exception $err) {
  $msg = "[ERROR] Data integrity check interrupted due to error. Manual restart is required.\nCheck log files.\n\n";
  $debug->log($logdir, $logpath, "[ERROR] No connection to DB (getAdoDB() failed). Error message:\n" . $err, $sendmail = TRUE);
  exit (1);
}

$num_recordings = $recordings->RecordCount();

// MAIN CYCLE /////////////////////////////////////////////////////////////////////////////////////
while ( !$recordings->EOF ) {
  // Get current field from the query
  $rec = $recordings->fields;
  $rec_id = $rec['id'];
  
  // init log string
  $recording_summary = "";

  $recording_path = $app->config['recordingpath'] . ( $rec_id % 1000 ) . "/" . $rec_id . "/";
  $upload_path = $app->config['uploadpath'];

  // Check recording directory
  if ( !file_exists($recording_path) ) {
    $recording_summary .= "[ERROR] recording path does not exist (" . $recording_path . ")\n";
    $recordings->MoveNext();
    continue;
  } elseif  ($rec['status'] !== $jconf['dbstatus_copystorage_ok']) {
    $recordings->MoveNext();
    continue;
  }
  // Threshold variable
  $threshold = 0;

  if ($rec['mastermediatype'] == "audio") {
    $masterfilename = $rec_id . "_audio." . $rec['mastervideoextension'];
  } else {
    $masterfilename = $rec_id . "_video." . $rec['mastervideoextension'];
  }
 
  switch ($rec['masterstatus']) {
    case $jconf['dbstatus_uploaded']:
      $master_record = $upload_path ."recordings/". $masterfilename;
      break;
    case $jconf['dbstatus_copystorage_ok']:
    default:
      $master_record = $recording_path . "master/" . $masterfilename;
  }
  unset($masterfilename);
  // Check master media file
  if ( !file_exists($master_record) ) {
    $recording_summary .= "[ERROR] master media file does not exist (" . $master_record . ")\n";
  } elseif ( filesize($master_record) == 0 ) {
    $recording_summary .= "[ERROR] master media file zero size (" . $master_record . ")\n";
  } elseif ($rec['mastermediatype'] != "audio") {
    $tmp = getLength($master_record);
    if ($tmp['result'] === false) {
      $recording_summary .= "[ERROR] Master recording analyzation failed: ".  $tmp['message'] ."(". $master_record .")\n";
      $debug->log($logdir, $logpath, $recording_summary, $sendmail = false);
      // break(1);
      $num_errors++;
      $recordings->MoveNext();
      continue;
    }
    $mastervideolength = $tmp['length'];
    unset($tmp);
    $threshold = ( round($mastervideolength * THRESHOLD) < 1.5 ? 1.5 : round($mastervideolength * THRESHOLD) );
    
    if (abs($mastervideolength - $rec['masterlength']) > $threshold) {
      $recording_summary .= "[WARNING] invalid database value (". $master_record ." - ". $mastervideolength ."sec, db - ". $rec['masterlength'] ."s)\n";
    }
  }
  
  // Check content master media file
  if ( $rec['contentmasterstatus'] == $jconf['dbstatus_copystorage_ok'] || $rec['contentmasterstatus'] == $jconf['dbstatus_uploaded']) {
    // masterstatus = "onstorage"|"uploaded"

    if ($rec['contentmastermediatype'] == "audio") {
      $contentmasterfilename = $rec_id . "_content." . $rec['contentmastervideoextension'];
    } else {
      $contentmasterfilename = $rec_id . "_content." . $rec['contentmastervideoextension'];
    }
    
    switch ($rec['contentmasterstatus']) {
      case $jconf['dbstatus_uploaded']:
        $content_master = $upload_path . $contentmasterfilename;
        break;
      case $jconf['dbstatus_copystorage_ok']:
      default:
        $content_master = $recording_path . "master/" . $contentmasterfilename;
    }
    unset($contentmasterfilename);

    if ( !file_exists($content_master) ) {
      $recording_summary .= "[ERROR] content master media file does not exist (" . $content_master . ")\n";
    } elseif ( filesize($content_master) == 0 ) {
      $recording_summary .= "[ERROR] content master media file zero size (" . $content_master . ")\n";
    } else {
      $tmp = getLength($content_master);
      if ($tmp['result'] === false) {
        $recording_summary .= "[ERROR] Content master analyzation failed: ".  $tmp['message'] ."(". $content_master .")\n";
        $debug->log($logdir, $logpath, $recording_summary, $sendmail = false);
        // break(1);
        $num_errors++;
        $recordings->MoveNext();
        continue;
      }
      $mastercontentlength = $tmp['length'];
      $threshold = ( round($mastercontentlength * THRESHOLD) < 1.5 ? 1.5 : round($mastercontentlength * THRESHOLD) );
      unset($tmp);
      if (abs($mastercontentlength - $rec['contentmasterlength']) > $threshold) {
        $recording_summary .= "[WARNING] invalid database value (". $content_master ." - ". $mastercontentlength ."sec, db - ". $rec['contentmasterlength'] ."s)\n";
      }
    }
  }

  // Get associated recordings_versions
  $query = "
  SELECT
    rv.id, rv.converternodeid, rv.recordingid, rv.encodingprofileid, rv.qualitytag, rv.filename, rv.iscontent, rv.status, rv.resolution, rv.bandwidth, rv.isdesktopcompatible, rv. ismobilecompatible, ep.type, ep.mediatype, ep.shortname, ep.name
  FROM
    recordings_versions AS rv, encoding_profiles AS ep
  WHERE
    rv.encodingprofileid = ep.id AND rv.status = '". $jconf['dbstatus_copystorage_ok'] ."' AND rv.recordingid = ". $rec_id;

  $tmp = query($query);
  if ($tmp['result'] === false) {
    $debug->log($logdir, $logpath, "[ERROR] Querying recording #". $rec_id ." failed. Error message:\n". $tmp['message'], $sendmail = TRUE);
    exit (1);
  }
  $recordings_versions = $tmp['data'];
  unset($tmp, $query);

  foreach ($recordings_versions as $rv) {
    if (!file_exists($recording_path . $rv['filename'])) {
      $recording_summary .= "[ERROR] recording version ". $rv['shortname'] ." does not exist (". $recording_path . $rv['filename'] .")";
      $recording_summary .= ", id#". $rv['id'] .".\n";
    } elseif(filesize($recording_path . $rv['filename']) == 0) {
      $recording_summary .= "[ERROR] recording version ". $rv['shortname'] ." has zero size (". $rv['filename'] .")";
      $recording_summary .= ", id#". $rv['id'] .".\n";
    } else {
      $msg = '';
      $tmp = getLength($recording_path . $rv['filename']);
      if ($tmp['result'] === false) {
        $debug->log($logdir, $logpath, "[ERROR] ". $tmp['message'] ."(recordings_version #". $rv['id'] .")" , $sendmail = false);
        break 2;
      }
      $duration = $tmp['length'];
      unset($tmp);
      switch($rv['type']) {
        case('recording'):
          $masterduration = $rec['masterlength'];
        break;
        
        case('content'):
          $masterduration = $rec['contentmasterlength'];
        break;
        
        case('pip'):
          $masterduration = max($rec['masterlength'], $rec['contentmasterlength']);
        break;
      }
      $threshold = ( round($masterduration * THRESHOLD) < 1.5 ? 1.5 : round($masterduration * THRESHOLD) );
      if (abs($masterduration - $duration) > $threshold) {
        $recording_summary .= "[WARNING] invalid ". $rv['name'] ." duration (". $rv['filename'] ." - ". $duration ."s, db - ";
        $recording_summary .= $masterduration ."s), id#". $rv['id'] .".\n";
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
// MAIN CYCLE END ///////////////////////////////////////////////////////////////////////////////

// Summarize check statistics
$log_summary .= "\nNumber of recordings: " . $num_recordings . "\n";
$log_summary .= "Number of checked recordings: " . $num_checked_recs . "\n";
$log_summary .= "Number of \"onstorage\" recordings: " . $num_onstorage_recs . "\n";
$log_summary .= "Number of faulty recordings: ". $num_errors ."\n\n";

// Calculate check duration
$duration = time() - $time_start;
$log_summary .= "Check duration: " . secs2hms($duration) . "\n";

$debug->log($logdir, $logpath, "Data integrity check results:\n\n" . $log_summary, $sendmail = TRUE);

// Close DB connection if open
if ( is_resource($db->_connectionID) ) $db->close();

exit;
//////////////////////////////////////////// FUNCTIONS ////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
// function query(querystring)
///////////////////////////////////////////////////////////////////////////////////////////////////
//
// Return values:
//   results['query']: the requested mysql query string
//   results['result']: (bool) true if success
//   results['message']: (string) message
//   results['data']: (array) an array on success, NULL if an error occured. Note that if the
//   query was executed, resulting no rows at all, the return value will still be an (empty) array.
//
///////////////////////////////////////////////////////////////////////////////////////////////////
function query($query) {
  global $db;
  $results = array(
    'query' => $query,
    'result' => false,
    'message' => "Ok!",
    'data' => null
  );
  try {
    $rs = $db->Execute($query);
  } catch (exception $err) {
    $results['message'] = "SQL query failed.\n". trim($err->getMessage()) ."\n";
    return $results;
  }
  if (method_exists($rs, "GetRows")) {
    if ( $rs->RecordCount() < 1 ) {
      $results['data'] = $rs->GetRows();
      $results['result'] = true;
      $results['message'] = "Query returned an empty array.\n";
    } else {
      $results['result'] = true;
      $results['data'] = $rs->GetRows();
    }
  } else {
  $results['message'] = "Cannot get rows from recordset.\n";
  }
  return $results;
}
///////////////////////////////////////////////////////////////////////////////////////////////////
  function getLength($mediafile) {
///////////////////////////////////////////////////////////////////////////////////////////////////
//
// Analyzes input file's length via Recordigs->analyze method.
// Return values:
//   result (bool): true on success, false on faliure
//   length (int): playing time of the input video
//   message (string): error message on failure
//
///////////////////////////////////////////////////////////////////////////////////////////////////
  global $recordingsModel;
  $results = array(
  'result'  => false,
  'length'  => null,
  'message' => '',
  );
  if (!file_exists($mediafile)) {
    $results['message'] = "File doesn't exists! (". $mediafile .")\n";
    return $results;
  }
  
  try {
    $recordingsModel->analyze($mediafile);
    $metadata = $recordingsModel->metadata;
    if (empty($metadata['masterlength'])) {
      $results['message'] = "Metadata cannot be retrieved (". $mediafile .")\n";
    } else {
      $results['length' ] = $metadata['masterlength'];
      $results['result' ] = true;
      $results['message'] = 'ok!';
    }
  } catch ( Exception $ex) {
    $results['message'] = "Recording analysis failed ( ". $mediafile ." ):\n". $ex->getMessage() ."\n";
  }
  return $results;
}
///////////////////////////////////////////////////////////////////////////////////////////////////
  function check_contributor_images() {
///////////////////////////////////////////////////////////////////////////////////////////////////
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
    $debug->log($logdir, $logpath, "[ERROR] SQL query failed. Query: \n" .  trim($query) . "\n\nError message: \n" . $err, $sendmail = TRUE);
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
      $recording_summary .= "[ERROR] contributor thumb does not exist (" . $image_file . ")\n";
    } elseif ( filesize($image_file) == 0 ) {
      $recording_summary .= "[ERROR] contributor thumb zero size (" . $image_file . ")\n";
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

///////////////////////////////////////////////////////////////////////////////////////////////////
  function check_video_thumbnails($rec_id, $num_thumbs) {
///////////////////////////////////////////////////////////////////////////////////////////////////
  global  $recording_summary;
  global  $app;
  
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
      $recording_summary .= "[ERROR] thumb does not exist (" . $thumb_tocheck . ")\n";
    } elseif ( filesize($thumb_tocheck) == 0 ) {
      $recording_summary .= "[ERROR] thumb zero size (" . $thumb_tocheck . ")\n";
    } else {
      $num_thumbs_small++;
    }
    // check 16:9[] thumbnails
    $thumb_tocheck = $thumb_path . $app->config['videothumbnailresolutions']['wide'] . "/" . $thumb_filename;
    if ( !file_exists($thumb_tocheck) ) {
      $recording_summary .= "[ERROR] thumb does not exist (" . $thumb_tocheck . ")\n";
    } elseif ( filesize($thumb_tocheck) == 0 ) {
      $recording_summary .= "[ERROR] thumb zero size (" . $thumb_tocheck . ")\n";
    } else {
      $num_thumbs_wide++;
    }
    // check normal[] thumbnails
    $thumb_tocheck = $thumb_path . $app->config['videothumbnailresolutions']['player'] . "/" . $thumb_filename;
    if ( !file_exists($thumb_tocheck) ) {
      $recording_summary .= "[ERROR] thumb does not exist (" . $thumb_tocheck . ")\n";
    } elseif ( filesize($thumb_tocheck) == 0 ) {
      $recording_summary .= "[ERROR] thumb zero size (" . $thumb_tocheck . ")\n";
    } else {
      $num_thumbs_player++;
    }
    // check original thumbnails
    $thumb_tocheck = $thumb_path . "original/" . $thumb_filename;
    if ( !file_exists($thumb_tocheck) ) {
      $recording_summary .= "[ERROR] thumb does not exist (" . $thumb_tocheck . ")\n";
    } elseif ( filesize($thumb_tocheck) == 0 ) {
      $recording_summary .= "[ERROR] thumb zero size (" . $thumb_tocheck . ")\n";
    } else {
      $num_thumbs_original++;
    }
  }
  // check missing thumbnails and report possible errors
  if ($num_thumbs != ($num_thumbs_small & $num_thumbs_wide & $num_thumbs_player & $num_thumbs_original)) {
    $recording_summary .= "Found thumbnails:\n\tsmall: " . $num_thumbs_small ."\n\twide: ". $num_thumbs_wide ." \n\tplayer: ".  $num_thumbs_player ."\n\toriginal: ". $num_thumbs_original ."\n\tnumber of index photos: ". $num_thumbs .".\n";
    return FALSE;
  }
  return TRUE;
}

///////////////////////////////////////////////////////////////////////////////////////////////////
  function check_attachments($rec_id) {
///////////////////////////////////////////////////////////////////////////////////////////////////
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
    $debug->log($logdir, $logpath, "[ERROR] SQL query failed. Query: \n" .  trim($query) . "\n\nError message: \n" . $err, $sendmail = TRUE);
    return FALSE;
  }
  $num_attachments = 0;
  
  while ( !$attachments->EOF ) {
    $attachment = $attachments->fields;
    $attachment_file = $attachment_path . $attachment['id'] . "." . $attachment['masterextension'];
    
    $attachment_ids[] = $attachment['id'];
    
    if ( !file_exists($attachment_file) ) {
      $recording_summary .= "[ERROR] attachment file missing (" . $attachment_file . ")\n";
    } elseif ( filesize($attachment_file) == 0 ) {
      $recording_summary .= "[ERROR] attachment file zero size (" . $attachment_file . ")\n";
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

///////////////////////////////////////////////////////////////////////////////////////////////////
  function is_res1_gt_res2($res1, $res2) {
///////////////////////////////////////////////////////////////////////////////////////////////////
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
