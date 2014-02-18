<?php
define('BASE_PATH',	realpath( __DIR__ . '/../..' ) . '/' );
// define('BASE_PATH', realpath('/var/www/videosquare.eu/') .'/');
define('PRODUCTION', false );
define('DEBUG', false );
include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');
set_time_limit(0);
// Init
$app = new Springboard\Application\Cli(BASE_PATH, PRODUCTION);
$recordingsModel = $app->bootstrap->getModel('recordings');
$app->loadConfig('modules/Jobs/config_jobs.php');
$jconf = $app->config['config_jobs'];
// parse options
if ($argc > 1) {
  $debug = ($argv[1] == "debug" ? true : false );
  if ( $debug === false) {
    print_r("[ERROR] Invalid option: '". $argv[1] ."'\n");
    exit -1;
  }
  print_r("[NOTICE] Started in debug-mode.\n");
} else {
  $debug = false;
}
// Establish database connection
try {
	$db = $app->bootstrap->getAdoDB();
} catch (exception $err) {
	// Send mail alert, sleep for 15 minutes
	echo "[ERROR] No connection to DB (getAdoDB() failed). Error message:\n" . $err . "\n";
	exit -1;
}
// Open logfile
try {
  $logfile = $app->config['datapath'] ."logs/". date('Y-m-', time()) ."recording_version_transfer.log";
  $fh = fopen($logfile, 'w');
} catch (Exception $e) {
  print_r("[ERROR] file cannot be opened: ". $logfile ."\n". $e->getMessage());
}
// Init variables
$query_recordings = "
  SELECT
    id,
    mediatype,
    mastermediatype,
    masterstatus,
    contentmasterstatus,
    status,
    contentstatus,
    mobilestatus,
    masterlength,
    contentmasterlength,
    mastervideores,
    contentmastervideores,
    videoreslq,
    videoreshq,
    contentvideoreslq,
    contentvideoreshq,
    mobilevideoreslq,
    mobilevideoreshq
  FROM
    recordings
  WHERE
    ( status = '". $jconf['dbstatus_copystorage_ok'] . "' OR status = '". $jconf['dbstatus_markedfordeletion'] ."' ) AND
		( masterstatus = '". $jconf['dbstatus_copystorage_ok'] ."' OR masterstatus = '". $jconf['dbstatus_markedfordeletion'] ."' )";
    
$log = "\n=====[ ". date('Y-m-d H:i:s', time()) ." ]===========================================================================================\n";
$log .= $debug === true ? "[NOTICE] Started in debug-mode.\n" : "";
$recordings_done = 0;
$versions_created = 0;
// Read basic profile ids from database
try {
  $recordings = $db->Execute($query_recordings);
  $num_recordings = $recordings->RecordCount();
  
  $prof_audio = getProfile('_audio');
  $prof_vidlq = getProfile('_video_360p');
  $prof_vidhq = getProfile('_video_720p');
  $prof_conlq = getProfile('_content_480p');
  $prof_conhq = getProfile('_content_720p');
  $prof_moblq = getProfile('_mobile_320p');
  $prof_mobhq = getProfile('_mobile_480p');
} catch (Exception $e) {
  print_r($e->getMessage() ."\n");
  exit -1;
}

while (!$recordings->EOF) {
  $rec = $recordings->fields;
  $msg = "\n> Recording #". $rec['id'] ."\n";
  $recording_path = $app->config['recordingpath'] . ( $rec['id'] % 1000 ) . "/" . $rec['id'] . "/";
  
  if (!file_exists($recording_path)) {
    $msg .= print_r("[ERROR] recording path doesn't exists! [ ". $recording_path ." ]\n", true);
    continue;
  }
  
  // Build filenames
  $audio      = $recording_path . $rec['id'] ."_audio.mp3";
  $video_lq   = $recording_path . $rec['id'] ."_video_lq.mp4";
  $video_hq   = $recording_path . $rec['id'] ."_video_hq.mp4";
  $content_lq = $recording_path . $rec['id'] ."_content_lq.mp4";
  $content_hq = $recording_path . $rec['id'] ."_content_hq.mp4";
  $mobile_lq  = $recording_path . $rec['id'] ."_mobile_lq.mp4";
  $mobile_hq  = $recording_path . $rec['id'] ."_mobile_hq.mp4";
  
  // Detect possible recordings_versions
  $is_audio_exists      = ( $rec['masterstatus'] == $jconf['dbstatus_copystorage_ok'] && $rec['mastermediatype'] != "videoonly");
  $is_video_lq_exists   = ( $rec['masterstatus'] == $jconf['dbstatus_copystorage_ok'] );
  $is_video_hq_exists   = ( $is_video_lq_exists && !empty($rec['videoreshq']) );
  $is_content_lq_exists = ( $rec['contentstatus'] == $jconf['dbstatus_copystorage_ok'] );
  $is_content_hq_exists = ( $is_content_lq_exists && !empty($rec['contentvideoreshq']) );
  $is_mobile_lq_exists  = ( $rec['mobilestatus'] == $jconf['dbstatus_copystorage_ok'] );
  $is_mobile_hq_exists  = ( $is_content_lq_exists && !empty($rec['mobilevideoreshq']) );
  
  // Create labels for versions
  $versions = array();
  if ($is_audio_exists)
    $versions[] = array('filename' => $audio, 'profile' => $prof_audio['id'], 'name' => $prof_audio['shortname'], 'status' => 'status', 'resolution' => null);
  if ($is_video_lq_exists)
    $versions[] = array('filename' => $video_lq, 'profile' => $prof_vidlq['id'], 'name' => $prof_vidlq['shortname'], 'status' => 'status', 'resolution' => 'videoreslq');
  if ($is_video_hq_exists)
    $versions[] = array('filename' => $video_hq, 'profile' => $prof_vidhq['id'], 'name' => $prof_vidhq['shortname'], 'status' => 'status', 'resolution' => 'videoreshq');
  if ($is_content_lq_exists)
    $versions[] = array('filename' => $content_lq, 'profile' => $prof_conlq['id'], 'name' => $prof_conlq['shortname'], 'status' => 'contentstatus', 'resolution' => 'contentvideoreslq');
  if ($is_content_hq_exists)
    $versions[] = array('filename' => $content_hq, 'profile' => $prof_conhq['id'], 'name' => $prof_conhq['shortname'], 'status' => 'contentstatus', 'resolution' => 'contentvideoreshq');
  if ($is_mobile_lq_exists)
    $versions[] = array('filename' => $mobile_lq, 'profile' => $prof_moblq['id'], 'name' => $prof_moblq['shortname'], 'status' => 'mobilestatus', 'resolution' => 'mobilevideoreslq');
  if ($is_mobile_hq_exists)
    $versions[] = array('filename' => $mobile_hq, 'profile' => $prof_mobhq['id'], 'name' => $prof_mobhq['shortname'], 'status' => 'mobilestatus', 'resolution' => 'mobilevideoreshq');
  
  // Iterate trough versions
  $versions_inserted = 0;
  $err = false;
  foreach($versions as $ver) {
    // check and skip existing versions
    // $checkversions = query("SELECT id, encodingprofileid, name, status FROM recording_versions WHERE id = ". $rec['id']); //// ENCODINGPROFILEID
    $checkversions = query("SELECT id, name, status FROM recordings_versions WHERE id = ". $rec['id']);
    // $checkversions = query("SELECT * FROM recordings WHERE id = 10");
    if ($checkversions['result'] === true && !is_null($checkversions['data'])) {
      $data = $checkversions['data'];
      if (!empty($data) && $data[0]['encodingprofileid'] == $ver['profile']) {
        $msg .= "Recording version '". $ver['filename'] ."' already exists, skipping entry\n";
        continue;
      }
    } else {
      $msg .= "[ERROR] Check ". $ver['name'] ." version failed! ". $checkversions['message'];
      $err = true;
      break;
    }
    
    $is_desktop_compatible = stripos($ver['resolution'], 'mobile') !== false ? false : true ;  // search for string in 'mobilevideores'
    $is_content            = stripos($ver['resolution'], 'content') !== false ? true : false ;
    $is_audio_only         = stripos($ver['filename'], '.mp3') !== false ? true : false ;
    $is_mobile_compatible  = !$is_desktop_compatible || $is_audio_only ? true : false ;
    try {
      $meta = analyze($ver['filename']);
      $result = insertRecordingVersions(
        array(
          'recid'    => $rec['id'],
          'name'     => $ver['name'],
          'profile'  => $ver['profile'],
          'file'     => $ver['filename'],
          'iscont'   => $is_content ? 1 : 0,
          'status'   => $rec[$ver['status']],
          'res'      => $rec[$ver['resolution']],
          'bandw'    => $is_audio_only ? $meta['masteraudiobitrate'] : $meta['mastervideobitrate'] ,
          'desktopc' => $is_desktop_compatible ? 1 : 0,
          'mobilec'  => $is_mobile_compatible ? 1 : 0
        )
      );
      // fwrite($fh, "recording ID: ". $rec['id'] ."\n". print_r($rec, true) ."\n". print_r($ver, true) ."\n-----------------\n"); //// DEBUG
      $msg .= "\nInserting new item in database:\n". trim($result['querystr']) ."\n";
      $msg .= "result: ". $result['message'] ."\n";
      if ($result['success'] === false) {
        $msg .= "[WARNING] Query failed, skipping.\n";
        $err = true;
        continue;
      } 
      $versions_inserted++;
      $versions_created++;
    } catch(Exception $ex) {
      print_r(print_r($ver, true) . $ex->getMessage());
      fwrite($fh, print_r(trim($ex->getMessage()), true));
      exit -1;
    }
  }
  $recordings_done = $recordings_done + ($err === true ? 0 : 1 );
  $msg .= "\nVersions inserted: ". $versions_inserted ."/". count($versions) ."\n";
  $msg .= "-----------------------------------------------------------------------------------------------------------------------\n";
  $log .= $msg;
  $recordings->MoveNext();
}

$log .= "\nTotal number of recordings done: ". $recordings_done ."/". $num_recordings ."\nTotal number of versions created: ". $versions_created ."\n";
print_r("\nWriting log file: \"". $logfile ."\"\n");
fwrite($fh, $log);
fclose($fh);

/////////////////////////////////////////// FUNCTIONS ///////////////////////////////////////////
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
  if ( $rs->RecordCount() < 1 ) {
    $results['data'] = $rs->GetRows();
    $results['result'] = true;
    $results['message'] = "Query returned an empty array.\n";
  } else {
    $results['result'] = true;
    $results['data'] = $rs->GetRows();
  }
  return $results;
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

function insertRecordingVersions($rv) {
  global $db;
  global $debug;
  $retval = array(
    'success'  => false,
    'querystr' => null,
    'message'  => null,
  );
  //
  // 'ENCODINGPROFILEID' MEZO MEG NEM LETEZIK A DB-BEN, LETRE KELL HOZZNI!!!!
  //
  $querystr =
    "INSERT INTO recording_versions( recordingid, encodingprofileid, name, filename, iscontent, status, resolution, bandwidth, isdesktopcompatible, ismobilecompatible )
    VALUES (".
      $rv['recid'] .",".
      $rv['profile'] .",".
      $rv['name'] .",".
      $rv['file'] .",".
      $rv['iscont'] .",".
      $rv['status'] .",".
      $rv['res'] .",".
      $rv['bandw'] .",".
      $rv['desktopc'] .",".
      $rv['mobilec'] .")";
  $retval['querystr'] = $querystr;
  try {
    if ($debug === false) {
      print_r("Updating recording #". $rv['file'] ."-". $rv['name'] ."\n");
      ////////////////// AMIG NINCS VEGLEGESITVE AZ ADATBAZIS STRUKTURA, ADDIG KI VAN VEVE AZ INSERT //////////////////
      // $db->Execute($querystr);
      /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    } else {
      if ($debug)
        print_r(trim($querystr) . "\n");
    }
    $retval['success'] = true;
    $retval['message'] = "ok";
    return $retval;
  } catch (Exception $ex) {
    print_r("[ERROR] DB insert failed.\n");
    $retval['message'] = $ex->getMessage();
    return $retval;
  }
}

function analyze($mediafile) {
  global $recordingsModel;
  try {
    $recordingsModel->analyze($mediafile);
    $metadata = $recordingsModel->metadata;
    if (empty($metadata)) {
      throw new Exception("[ERROR] Return array is empty!\n");
      return false;
    }
    return $metadata;
  } catch (Exception $e) {
    throw new Exception($e->getMessage());
  }
  return false;
}

function getProfile($profilename) {
  global $db;
  $qry = "SELECT id, shortname, filenamesuffix FROM encoding_profiles WHERE filenamesuffix LIKE '". $profilename ."'\n";
  try {
    $profiles = $db->Execute($qry);
    if ($profiles->RecordCount() < 1) {
      throw new Exception("[ERROR] Profile \"". $profilename ."\" cannot be found.\n");
      return false;
    }
    return $profiles->fields;
  } catch(Exception $ex) {
    throw new Exception($ex->getMessage());
  }
}

?>