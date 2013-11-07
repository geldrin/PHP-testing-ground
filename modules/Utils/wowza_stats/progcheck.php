<?php
define('BASE_PATH', realpath( __DIR__ . '/../../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

include_once( BASE_PATH .'libraries/Springboard/Application/Cli.php');
// Utils
include_once(BASE_PATH . 'modules/Jobs/job_utils_log.php');
include_once(BASE_PATH . 'modules/Jobs/job_utils_base.php');
include_once(BASE_PATH . 'modules/Jobs/job_utils_media.php');
set_time_limit(0);

// Init
$app = new Springboard\Application\Cli(BASE_PATH, PRODUCTION);

$app->loadConfig('modules/Jobs/config_jobs.php');
$jconf = $app->config['config_jobs'];

$recordings = array();
$users = array();

try {
  $db = $app->bootstrap->getAdoDB();
} catch (exception $err) {
  // Send mail alert, sleep for 15 minutes
  $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] No connection to DB (getAdoDB() failed). Error message:\n" . $err, $sendmail = TRUE);
  exit;
}

// Query channels of org
$org_id = 200; // conforg
//$org_id = 222; // infoszfera
//$org_id = 2;   // videosquare
$query = "
  SELECT
    ch.id,
    ch.title,
    DATE(ch.starttimestamp) as starttimestamp
  FROM
    channels AS ch
  WHERE
    ch.organizationid = " . $org_id . "
  ORDER BY
    ch.starttimestamp
  ";

//echo $query . "\n";

try {
  $org_channels = $db->getArray($query);
} catch ( Exception $e ) {
  echo "false!\n";
  exit -1;
}

$msg  = "# Videosquare ondemand statistics report\n\n";
$msg .= "# Log analization started: " . date("Y-m-d H:i:s") . "\n";
//$msg .= "# Customer: " . $event_info['name'] . " - " . $event_info['url'] . "\n";
//$msg .= "# Domain: " . $event_info['domain'] . "\n#\n";
$msg .= "# Locations (location / stream name): (*) = encoder\n";

foreach ($org_channels as $ch) {

  $query = "
    SELECT
      rec.id,
      rec.title,
      rec.subtitle,
      rec.masterlength,
      chrec.channelid
    FROM
      recordings AS rec,
      channels_recordings AS chrec
    WHERE
      chrec.channelid = " . $ch['id'] . " AND
      chrec.recordingid = rec.id AND
      rec.isseekbardisabled = 1
    ORDER BY
      rec.recordedtimestamp,
      rec.id
  ";

  try {
    $recs = $db->getArray($query);
  } catch ( Exception $e) {
    print_r("false!!");
    exit -1;
  }

  if ( empty($recs) ) continue;

  // Prepare channel log message
  $msg_ch = toUpper($ch['title']) . " (" . $ch['starttimestamp'] . ") [ID=" . $ch['id'] . "]:\n\n";

  foreach ($recs as $rec) {

    $query = "
      SELECT
        prog.userid,
        prog.recordingid,
        prog.position,
        prog.timestamp,
        u.email
      FROM
        recording_view_progress AS prog,
        users AS u
      WHERE
        prog.recordingid = " . $rec['id'] . " AND
        prog.userid = u.id AND
        u.organizationid = " . $org_id;

//echo $query . "\n";
  
    try {
      $user_progress = $db->getArray($query);
    } catch ( Exception $e) {
      print_r("false!!");
      exit -1;
    }

    if ( empty($user_progress) ) continue;

    $msg_rec = $rec['title'] . " / " . $rec['subtitle'] . " [ID=" . $rec['id'] . "]:\n";

    $user_added = false;
    foreach ($user_progress as $up) {
    
      // Log channel header for this recording
      if ( !empty($msg_ch) ) {
        $msg .= $msg_ch;
        unset($msg_ch);
      }

      // Log recording header for users' progress
      if ( !empty($msg_rec) ) {
        $msg .= $msg_rec;
        unset($msg_rec);
      }

      $position_percent = round( ( 100 / $rec['masterlength'] ) * $up['position'], 2);
		if ( $position_percent > 100 ) $position_percent = 100;
      $msg .= $up['email'] . ";" . secs2hms($up['position']) . ";" . secs2hms($rec['masterlength']) . ";" . $position_percent . "%\n";

      $user_added = true;
    }

    if ( $user_added ) $msg .= "\n";
  }

}

//echo $msg;

// Open log file
$result_file = "vsq_ondemand_statsh_" . date("Y-m-d") . ".txt";
$fh = fopen($result_file, "w");

if ( fwrite($fh, $msg) === FALSE ) {
  echo "Cannot write to file (" . $result_file . "\n";
  exit;
}

fclose($fh);

exit;

/////////////////////////////////////////////////////// functions ///////////////////////////////////////////////////////////

function toUpper($string) { 
  return (strtoupper(strtr($string, 'áéíóöőüű','ÁÉÍÓÖŐÜŰ'))); 
};

?>
