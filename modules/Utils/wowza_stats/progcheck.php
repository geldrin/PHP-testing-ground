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

$recordings   = array();
$users        = array();
$sessioncheck = false;
$date_start   = false;
$options      = null;
$org          = null;
$org_id       = null;

$msg  = "# Videosquare ondemand statistics report\n\n";
$msg .= "# Log analization started: " . date("Y-m-d H:i:s") . "\n";
$msg .= "# Locations (location / stream name): (*) = encoder\n";

try {
  $db = $app->bootstrap->getAdoDB();
} catch (exception $err) {
  // Send mail alert
  $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] No connection to DB (getAdoDB() failed). Error message:\n" . $err, $sendmail = FALSE);
  exit;
}

// get organization id from user/argument
$options = getopt(
  's::d::h::?::',
  array("sessions", "date-start::", "help")
);

if ($argc > 1) {
  if (isset($options['s']) || isset($options['sessions'])) {
    $sessioncheck = true;
  }
  if (isset($options['d']) || isset($options['date-start'])) {
    $tmp = null;
    if (array_key_exists('d', $options)) {
      $tmp = $options['d'];
    } elseif(array_key_exists('date-start', $options)) {
      $tmp = $options['date-start'];
    } else {
      print_r("No parameter passed for \"date-start\"! See strtotime() function for proper syntax.\n");
    }

    if ($tmp) {
      $date_start = strtotime($tmp, time());
      if ($date_start === false) {
        print_r("Parameter cannot be parsed as date(". $tmp ."). See strtotime() function for proper syntax.\n");
      }
    }
  }
  if (isset($options['h']) || isset($options['help'])) {
    print_r(
      "\nSyntax:\n".
      "php progcheck.php [-[h,s,d=<date>] [--help] [--sessions] [--date-start] <organization_id>]\n".
      "For interactive menu, omit all arguments.".
      "Options:\n".
      " -h, --help: print this help.\n".
      " -d, --date-start=<date>: get from this date. Check strtotime() for more info.\n".
      " -s, --sessions: do session check too.\n\n".
      "E.g.:\n".
      " $ php progcheck.php - interactive mode.\n".
      " $ php progcheck.php 123 - check organization with id 123.\n".
      " $ php progcheck.php --sessions -d=\"-2 months\" 123 - check the users deactivated in the last two months.\n\n"
    );
  }
  
  $org = end($argv);
  if ($org && preg_match('/^\d+$/', $org) === 1) {
    $org = validateOrganization($org);
    
    if ($org === false) {
      print_r("Organization does not exist!");
      exit;
    }
    
    $org_id = $org['id'];
    print_r("Organization name = ". $org['name'] ." (". $org_id .")\n");
  } else {
    print_r("Failed to parse organization ID!\n");
    exit;
  }
}

if ($org_id === null) {
  print_r("OrgID:\n");
  while (true) {
    print_r(" > ");
    $input = fgets(STDIN);
    if (!preg_match('/[A-Za-z.#\\-$]/', $input)) {
      $input = intval($input);
      $org = validateOrganization($input);
      if ($org) {
        $org_id = intval($org['id']);
        print_r("Organization name = ". $org['name'] ." (". $org_id .")\n");
        break;
      } else {
        print_r("Organization does not exist! Please type again.\n");
      }
    }
    print_r('Invalid value! Please type again: ');
  }
  
  print_r("Date-start (optional):\n");
  while (true) {
    print_r(" > ");
    $input = trim(fgets(STDIN));
    
    if (empty($input)) {
      $date_start = false;
      break;
    }
    
    $date_start = strtotime(trim($input));
    if ($date_start === false) {
      print_r('Invalid value! Please type again: ');
    } else {
      break;
    }
  }
  
  print_r("Session check? [y/n]:\n");
  while (true) {
    print_r(" > ");
    $input = trim(fgets(STDIN));
    
    if ($input == 'y') {
      $sessioncheck = true;
      break;
    } elseif($input == 'n') {
      $sessioncheck = false;
      break;
    } else {
      print_r('Invalid value! Please type again: ');
    }
  }
}

if ($sessioncheck === false)
  $msg .= "# Legend: (email,watched duration,total duration)\n";
else
  $msg .= "# Legend: (email, watched duration, total duration, total percent,  session started, session started from, session terminated, session stopped at, session duration, session percent)\n";
$msg .= "\n";

// query database
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

try {
  $org_channels = $db->getArray($query);
} catch ( Exception $e ) {
  echo "false!\n";
  exit -1;
}

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

  $durationcheck = $date_start ? ("users.sessionlastupdated >= '". date('Y-m-d H:i:s', $date_start) ."' AND\n") : ('');
  foreach ($recs as $rec) {
    $query = array(
      'progress' => "SELECT
          prog.userid,
          prog.recordingid,
          prog.position,
          prog.timestamp,
          u.email,
          u.firstloggedin
        FROM
          recording_view_progress AS prog,
          users AS u
        WHERE
          prog.recordingid = " . $rec['id'] . " AND
          prog.userid = u.id AND
          ". $durationcheck ."u.organizationid = $org_id",

      'sessions' => "SELECT
          session.userid,
          users.email,
          session.recordingid,
          session.timestampfrom,
          session.timestampuntil,
          session.positionfrom,
          session.positionuntil,
          prog.position
        FROM
          recording_view_sessions AS session,
          recording_view_progress AS prog,
          users
        WHERE
          (". $durationcheck ."users.id = session.userid AND
          users.organizationid = $org_id AND
          session.recordingid = ". $rec['id'] .") AND (
          prog.userid = session.userid AND
          prog.recordingid = session.recordingid)"
    );

    try {
      if ($sessioncheck === true) {
        $user_progress = $db->getArray($query['sessions']);
      } else {
        $user_progress = $db->getArray($query['progress']);
      }
    } catch ( Exception $e) {
      print_r("false!!");
      exit -1;
    }

    if ( empty($user_progress) ) continue;

    $msg_rec = $rec['title'] . " / " . $rec['subtitle'] . " [ID=" . $rec['id'] . "]:\n";

    $user_added = false;
    foreach ($user_progress as $up) {
      if (array_key_exists('positionfrom', $up)) {
        
        $session_watched = abs($up['positionuntil'] - $up['positionfrom']);
        $session_duration = abs(strtotime($up['timestampfrom']) - strtotime($up['timestampuntil']));
        
        if ($session_watched > 0 === false) continue; // Skip headers if the session length was 0.
      }
      $row = '';
      $tmp = array();
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
      if ( $up['position'] > $rec['masterlength'] ) $up['position'] = $rec['masterlength'];

      $tmp[] = $up['email'];
      $tmp[] = secs2hms($rec['masterlength']);
      $tmp[] = secs2hms($up['position']);
      $tmp[] = $position_percent ."%";

      if ($sessioncheck === true) { // when checking sessions, append the following data to the log:
        $session_percent = $session_watched / $rec['masterlength'] * 100;

        $tmp[] = $up['timestampfrom'];
        $tmp[] = secs2hms($up['positionfrom']);
        $tmp[] = $up['timestampuntil'];
        $tmp[] = secs2hms($up['positionuntil']);
        $tmp[] = secs2hms($session_duration);
        $tmp[] = round($session_percent, 2) ."%";

        unset($session_percent, $session_duration, $position_percent, $session_watched);
      }
      
      $row = implode(";", $tmp) . PHP_EOL;
      // echo $row;
      $msg .= $row;
      $user_added = true;
      
      unset($tmp);
    }

    if ( $user_added ) $msg .= "\n";

  }
}

// Open log file
$result_file = "vsq_ondemand_statsh_" . date("Y-m-d") . ".txt";
$fh = fopen($result_file, "w");

if ( fwrite($fh, $msg) === FALSE ) {
  echo "Cannot write to file (" . $result_file . "\n";
  exit;
}

fclose($fh);
print_r("File written to: ". $result_file ."\n");
exit;

/////////////////////////////////////////////////////// functions ///////////////////////////////////////////////////////////

function toUpper($string) { 
  return (strtoupper(strtr($string, 'áéíóöőüű','ÁÉÍÓÖŐÜŰ'))); 
};

function db_query($qry) {
  global $db;
  $returnarray = array(
    'success'     => false,
    'returnvalue' => null,
    'message'     => null
  );
  try {
    $arr = $db->getArray($qry);
    $returnarray['returnvalue'] = !empty($arr) ? $arr[0] : false;
    $returnarray['message'] = !$returnarray['returnvalue'] ? "Database query failed!\nQuery:\n". $qry ."\n" : "Success.";
    if ($returnarray['returnvalue'] === false) {
      $returnarray['message'] = "The query returned an empty array.";
      return $returnarray;
    }
    $returnarray['success'] = true;
  } catch (Exception $ex) {
    $returnarray['returnvalue'] = false;
    $returnarray['message'] = "Database query failed!\nQuery:\n". $qry;
  }
  return $returnarray;
}

function validateOrganization($aString = null) {
// return FALSE when the organization doesn't exist, or an array with it's ID and name
  $orgInfo = array(
    'id'   => null,
    'name' => null,
  );
  
  if ($aString && preg_match('/^\d+$/', $aString) === 1) {
    $data = db_query("SELECT id, name FROM organizations WHERE id=". intval($aString));
    if ($data['success'] === true && $data['returnvalue'] !== false) {
      $orgInfo['id'  ] = intval($data['returnvalue']['id']);
      $orgInfo['name'] = $data['returnvalue']['name'];
      return $orgInfo;
    } else {
      print_r("[ERROR] ". $data['message'] . PHP_EOL);
    }
  }
  return false;
}

?>
