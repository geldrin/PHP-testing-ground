<?php
///////////////////////////////////////////////////////////////////////////////////////////////////
// ARGS
///////////////////////////////////////////////////////////////////////////////////////////////////

$date_start = '2015-01-01';   // idointervallum kezdo datuma
$date_stop  = '2015-04-20';   // idointervallum vegdatuma
$out_dir    = './';           // logfajl mentesi konyvtara
$userIDs    = null;           // azon felhasznalok ID-ja, akiket keresunk (null, int, arr)
$filters    = array(          // szuresi feltetelek. Az adott elemek elhagyhatok, ekkor teljesen kigyjuk a DB lekerdezesbol
  'organizations' => null,    // organization id-k tombje (null, arr)
  'isusergenerated' => true,  // (bool)
);
$logdir = null; // (str, null) a login.txt fajlok eleresi utvonala, amennyiben null, az alapertelmezett konyvtarban keres

///////////////////////////////////////////////////////////////////////////////////////////////////
// INIT
///////////////////////////////////////////////////////////////////////////////////////////////////

// define('BASE_PATH', realpath('/var/www/videosquare.eu') . '/' );
define('BASE_PATH',	realpath( __DIR__ . '/../..' ) . '/' );

include_once(BASE_PATH . 'libraries/Springboard/Application/Cli.php');
include_once(BASE_PATH . 'modules/Jobs/job_utils_base.php');


$app    = new Springboard\Application\Cli(BASE_PATH, false);
$db     = null;
$db     = db_maintain(); // job_utils_base

// var_dump(updateDBfirstLoggedin(15, 1427442070)); exit;
// var_dump(getFirstLogins(array(14, 11091), date('Y-m-d', strtotime($date_start)), date('Y-m-d', strtotime($date_stop)), $logdir));

///////////////////////////////////////////////////////////////////////////////////////////////////
// LAUNCH
///////////////////////////////////////////////////////////////////////////////////////////////////

main(); exit;

///////////////////////////////////////////////////////////////////////////////////////////////////
function main() {
///////////////////////////////////////////////////////////////////////////////////////////////////
//
///////////////////////////////////////////////////////////////////////////////////////////////////
global $date_start, $date_stop, $out_dir, $logdir, $db, $app, $userIDs, $filters;
  
  $logindata     = null;
  $logins_sorted = null;
  $mode = null;

  if (!file_exists($out_dir)) {
    if (!mkdir($out_dir, 0755)) {
      print_r("[ERROR] Output directory cannot be created (". $out_dir .")\n");
      exit;
    }
  }

  $now = getdate();
  $datetime_today = strtotime($now['year'] .'-'. $now['mon'] .'-'. $now['mday']);
  $datetime_start = date('Y-m-d H:i', strtotime($date_start));
  $datetime_stop  = date('Y-m-d H:i', strtotime($date_stop ));

  if (strtotime($date_stop) > $datetime_today) {
    $datetime_stop = $datetime_today;
    $date_stop = date('Y-m-d H:i', $datetime_today);
  }

  print_r("Analyze interval: ". $datetime_start ." - ". $datetime_stop .".\n");

  $logindata = getFirstLogins($userIDs = null, $datetime_start, $datetime_stop, $logdir);

  if (!$logindata) {
    print_r("Log file analization failed.\n");
    die;
  }

  $sortedlogins = sortLogins($logindata, $filters);

  switch( $mode ) {
    case "update":
      print_r("Updateing database.\n");
      $err = false;
      foreach($sortedlogins as $uid => $l) {
        $err = updateDBfirstLoggedin($uid, $l['ts']);
        if ($err === false) {
          print_r("[ERROR] DB updated failed!\n");
          break;
        }   
      }
      break;

    case "list":
    default:
      print_r("Listing data:\n");
      foreach($sortedlogins as $uid => $l) {
        print_r("\tuserID=". $uid ." | email='". $l['email'] ."' | date='". $l['date'] ."' | ts=". $l['ts'] ."\n");
      }
      print_r(" > usercount: ". count($sortedlogins) ."\n");
      break;

  }

}
///////////////////////////////////////////////////////////////////////////////////////////////////
function getLogins($userids = null, $date_start, $date_stop, $logpath = '') {
///////////////////////////////////////////////////////////////////////////////////////////////////
//
///////////////////////////////////////////////////////////////////////////////////////////////////
  if (empty($logpath)) $logpath = BASE_PATH . 'data/logs/';
  if (!file_exists($logpath)) {
    print_r("[WARN] Log directory doesn't exists ($logpath)\n");
    return false;
  }
  $logins     = array(
    'uid'   => array(),
    'email' => array(),
    'sid'   => array(),
    'ts'    => array(),
    'date'  => array(),
  );
  $logfiles   = array();
  $starttime  = strtotime($date_start);
  $stoptime   = strtotime($date_stop);
  $date_start = strtotime(date('Y-m', $starttime));
  $date_stop  = strtotime(date('Y-m', $stoptime));
  $month      = $date_start;

  do {
    $logfile = $logpath . date('Y-m', $month) . "-login.txt";
    $month = strtotime('next month', $month);

    if (!file_exists($logfile)) {
      print_r("[WARNING] File does not exists! (". $logfile .")\n");
      continue;
    }

    $logfiles[] = $logfile;
  } while($month <= $date_stop);

  if (empty($logfiles)) {
    print_r("[ERROR] No userlogs found!\nPlease check directory \"". $logpath ."\"!\n");
    return false;
  }

  try {
    foreach($logfiles as $log) {
      echo "parsing logfile: ". $log ."...";
      $handle = fopen($log, 'r');
      if ($handle) {
        do {
          $row   = fgets($handle); // olvass be egy sort
          $sid   = null;
          $uid   = null;
          $email = null;
          $ts    = null;
          $match = null;

          if (preg_match('/(?<=user#)\d+/', $row, $match) === 0) continue;
          if (empty($match)) {
            print_r("no UID!!\n");
            return false;
          }
          $uid = intval($match[0]);
          
          if (preg_match('/\b[\w\.]+@[\w\.]+\.[\w]{1,5}\b/', $row, $match) === 0) continue;
          if (empty($match)) {
            print_r("no email!!!\n");
            return false;
          }
          $email = $match[0];

          if (preg_match('/(?<=\bSESSIONID:\s)\w+/', $row, $match) === 0) continue;
          if (empty($match)) {
            print_r("no SID!!\n");
            return false;
          }
          $sid = $match[0];

          if (preg_match('/^[\d]{4}-[\d]{2}-[\d]{2}\s[\d]{2}:[\d]{2}:[\d]{2}/', $row, $match) === 0) continue;
          if (empty($match)) {
            print_r("no timestamp!!!\n");
            return false;
          }
          $ts = strtotime($match[0]);

          if ($ts === false) {
            print_r("[WARN] timestamp cannot be parsed!\n($row)\n");
            continue;
          }

          if ($userids !== null) {
            $key = array_search($uid, $userids);
            if ($key === false)
              continue; // ha nem ezt a usert keressuk, lepj tovabb a kovetkezo sorra
            if (in_array($sid, $logins['sid']) === true)
              continue; // ha felvettuk mar a sessionid, akkor lepjunk a kovetkezo sorra
          } // ures user-tomb eseten tarolj el minden userre vonatkozo sessionID-t

          if ($ts >= $starttime and $ts <= $stoptime) { // ha a vizsgalt idoszakon belul van, add hozza a tombhoz
            $logins['uid'  ][] = $uid;
            $logins['email'][] = $email;
            $logins['sid'  ][] = $sid;
            $logins['ts'   ][] = $ts;
            $logins['date' ][] = date('Y-m-d H:i:s', $ts);
          }
        } while(!feof($handle));
        fclose($handle);
        print_r(" done.\n");
      }
    }
    unset($logfiles);
  } catch(Exception $e) {
    print_r("[ERROR] File cannot be read! (". $log .")\n". $e->getMessage()) ."\n";
    exit;
  }

  return $logins;
}

///////////////////////////////////////////////////////////////////////////////////////////////////
function getFirstLogins($userids = null, $datetime_start, $datetime_stop, $logdir) {
///////////////////////////////////////////////////////////////////////////////////////////////////
//
///////////////////////////////////////////////////////////////////////////////////////////////////
  if ( !is_int($userids) && !is_null($userids) && !is_array($userids)) return false;
  if (is_int($userids)) $userids = array($userids);
  $logins      = array();
  $firstlogins = array();
  $last        = array();            // user's last instected timestamp

  $logins = getLogins($userids, $datetime_start, $datetime_stop, $logdir);
  if ($logins === false || empty($logins)) return false;

  foreach ($logins['uid'] as $key => $uid) {
    
    $email = $logins['email'][$key];
    $sid   = $logins['sid'  ][$key];
    $ts    = $logins['ts'   ][$key];
    $date  = $logins['date' ][$key];

    if (isset($userids)) {

      if (!in_array($uid, $userids))
        continue;
      
    }

    if (!array_key_exists($uid, $firstlogins)) {
      $firstlogins[$uid] = null;
      $last[$uid] = PHP_INT_MAX;
    }

    if ($ts < $last[$uid]) {
      $last[$uid] = $ts;
      $firstlogins[$uid]['email'] = $email;
      $firstlogins[$uid]['ts'   ] = $ts;
      $firstlogins[$uid]['date' ] = $date;
      $firstlogins[$uid]['sid'  ] = $sid;
    }

  }
  
  unset($logins, $last);

  return $firstlogins;

}
///////////////////////////////////////////////////////////////////////////////////////////////////
function sortLogins($data, $filter = null) {
///////////////////////////////////////////////////////////////////////////////////////////////////
// filter
//  => organizations
//  => isusergenerated
///////////////////////////////////////////////////////////////////////////////////////////////////
global $app, $db;

  $sorted = array();
  $params = array();
  $p  = array();
  $_p = null;
  
  if (!isset($data)) return false;
  if ($filter === null) $filter = array();
  
  if (!isset($db) || !is_resource($db->_connectionID)) {
    print_r("Reopening DB connection...\n");
    $db = db_maintain();
  }

  if (!empty($filter)) {
    if (array_key_exists('organizations', $filter) && $filter['organizations'] !== null) {
      foreach ($filter['organizations'] as $id => $org) {
        $p[] = $db->Param($id);
        $params[] = $org;
      }
      $_p = implode(', ', $p);
    }
    if (array_key_exists('isusergenerated', $filter)) {
      $params[] = (bool) $filter['isusergenerated'];
    }
  }
  
  foreach ($data as $uid => $d) {
    try {
      $dataset = null;

      $checkqry = "
        SELECT id, email, organizationid
        FROM users
        WHERE id = ". $db->Param('uid');
      
      if (array_key_exists('organizationid', $filter)) $checkqry .= " AND organizationid IN (". $_p .")";
      if (array_key_exists('isusergenerated', $filter)) $checkqry .= " AND isusergenerated = ". $db->Param('isusergenerated');
      
      $checkqry .= " AND firstloggedin IS NULL";
      
      $stmnt = array_merge( array($uid), $params );

      $rs = $db->Prepare($checkqry);
      $rs = $db->Execute($checkqry, $stmnt);
      
      // print_r("query:\n". $rs->sql ."\n");

      if ($rs === false) {
        print_r("[ERROR] Query failed!\n". $rs->sql ."\nErrormessage: ". $rs->ErrorMsg() ."\n");
        return false;
      }

      $dataset = $rs->getArray();
      // var_dump($dataset);
      
      if (empty($dataset)) {
        continue; // ha a felhasznalo nem illeszkedik a filterre, lepjunk tovabb
      }

      $sorted[$uid] = $d;      
      $sorted[$uid]['email'] = $dataset[0]['email'];
      
    } catch( Excetpion $e) {
      print_r("[ERROR] ". __FUNCTION__ ." failed! Message: ". $e->getMessage() . PHP_EOL);
      return false;
    }
  }

  return $sorted;

}
///////////////////////////////////////////////////////////////////////////////////////////////////
function updateDBfirstLoggedin($user, $ts) {
///////////////////////////////////////////////////////////////////////////////////////////////////
//
///////////////////////////////////////////////////////////////////////////////////////////////////
  global $db;
  
  if ((!isset($user) && !isset($ts)) || (!is_int($user) && !is_int($user))) return false;
  
  if (!is_resource($db->_connectionID)) $db = db_maintain();

  try {
    $updateparams = array(date('Y-m-d H:i:s', $ts), $user);
    $updatequery = trim("
      UPDATE users 
      SET firstloggedin = ". $db->Param('firstloggedin') ."
      WHERE id = ". $db->Param('id') ." AND firstloggedin IS NULL"
    );

    $rs = $db->Prepare($updatequery);
    $rs = $db->Execute($updatequery, $updateparams);
    
    if ($rs === false) {
      print_r("[ERROR] DB update failed! Error message:\n". $rs->errorMsg());
      return false;
    }
    print_r(">". $rs->sql);
    
  } catch (Exception $ex) {
    print_r( __FUNCTION__ ." failed! Errormessage: ". $ex->getMessage());
    return false;
  }

  return true;

}

///////////////////////////////////////////////////////////////////////////////////////////////////
  function dump2file($data, $path) {
///////////////////////////////////////////////////////////////////////////////////////////////////
// A metodus CSV formatumhoz hasonloan, pontosvesszovel elvalasztva elmenti a kulonbozo
// felhasznalokra kigyujtott, finomitott adatokat.
// Argumentumok: data - tomb, path - logfile eleresi utvonala.
// Visszateresi erteke sikertelen fajl iras eseten FALSE, egybkent TRUE.
///////////////////////////////////////////////////////////////////////////////////////////////////
  if (!is_array($data)) {
    print_r("[ERROR] Data must be a valid array!\n");
    return false;
  }
  
  try {
    $row = "Date;UID;Event;Duration;RecordingID;File;SID\n";
    $handle = fopen($path, 'w');
    if (!fwrite($handle, $row)) throw new Exception("[ERROR] String ('". $row . "') cannot be written to file (". $path .").\n");
    
    foreach ($data as $key => $array) {
      if (empty($users)) continue; // don't insert a blank line if encountered an empty data field
      foreach($users as $useractivity) {
        $row = $useractivity['date'] .';'
          . $useractivity['uid']     .';'
          . $useractivity['event']   .';'
          . $useractivity['dur']     .';'
          . $useractivity['recid']   .';'
          . $useractivity['file']    .';'
          . $useractivity['sid']     ."\n";
        fwrite($handle, $row);
      }
    }

    fclose($handle);
    return true;
  } catch(Exception $e) {
    print_r("[ERROR] while writing file (". $path .")\n". $e->getMessage() ."\n");
    if (gettype($handle) === 'resource') fclose($handle);
    return false;
  }
}

?>
