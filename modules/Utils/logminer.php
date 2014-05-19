<?php
///////////////////////////////////////////////////////////////////////////////////////////////////
// Logminer v1.0
///////////////////////////////////////////////////////////////////////////////////////////////////
//
// A megadott idointervallumban, megkeresi a beadott userekre vonatkozo on-demand logbejegyzeseket.
// A szkript eloszor kikeresi az adatbazisbol a usereket, aztan a ../videosquare/data/logs mappaban
// atvizsgalja a login.txt fajlokat, es kigyujti beloluk az osszes sessionid-t, ami alapjan a
// wowza logokbol be tudja azonositani a userekhez tartozo bejegyzeseket.
// A wowzalogokat sorrol sorra beolvassa, es kigyujti beloluk a kovetekzo adatokat:
// 	datum/ido, userid, event, duration, recoriding id, filename, sessionid.
// Miutan feldolgozta a fajlokat, a megadott konyvtarba kiirja az eredmenyt egy CSV fajlba.
//
// A usereket a query tombben megadott MYSQL lekerdezesbol lehet kinyerni!!!
// (Igeny eseten at kellene alakitani parameterezhetove, a jelenlegi hardcoded megoldas helyett)
//
///////////////////////////////////////////////////////////////////////////////////////////////////
// ARGS
///////////////////////////////////////////////////////////////////////////////////////////////////
$date_start = '2014-04-01';                           // idointervallum kezdo datuma
$date_stop  = '2014-05-16';                           // idointervallum vegdatuma
$out_dir    = '/home/gergo/vod_stat_repair_2014_IV/';	// logfajl mentesi konyvtara
$query = array(                                       // userek listaja
	'conforg' => "
		SELECT id, email FROM users
		WHERE email REGEXP '3942|9968|1010|9207|9975|6094|6122|2606|8253|5026|6344|1983|2601|3692|1058|7853'",
	'user9211' => "
		SELECT id, email FROM users WHERE id = 9388",
);
///////////////////////////////////////////////////////////////////////////////////////////////////
// INIT
///////////////////////////////////////////////////////////////////////////////////////////////////
define('BASE_PATH',	realpath('/var/www/videosquare.eu') . '/' );
define('LOG_PATH',  realpath('/var/log/wowza') .'/');

include_once(BASE_PATH . 'libraries/Springboard/Application/Cli.php');
include_once(BASE_PATH . 'modules/Jobs/job_utils_base.php');

$app = new Springboard\Application\Cli(BASE_PATH, false);
$db = null;
$db = db_maintain(); // job_utils_base
///////////////////////////////////////////////////////////////////////////////////////////////////
// MAIN
///////////////////////////////////////////////////////////////////////////////////////////////////
$users = array();
$logs  = array();

if (!file_exists($out_dir)) {
	if (!mkdir($out_dir, 0755)) {
		print_r("[ERROR] Output directory cannot be created (". $out_dir .")\n");
		exit;
	}
}

$now = getdate();
$datetime_today = strtotime($now['year'] .'-'. $now['mon'] .'-'. $now['mday']);
$datetime_start = strtotime(date('Y-m-d', strtotime($date_start)));
$datetime_stop = strtotime(date('Y-m-d', strtotime($date_stop)));
$day = $datetime_start;
if (strtotime($date_stop) > $datetime_today) {
	$datetime_stop = $datetime_today;
	$date_stop = date('Y-m-d', $datetime_today);
}

print_r("Analyze interval: ". date('Y-m-d', $datetime_start) ." - ". date('Y-m-d', $datetime_stop) .".\n");

try {
	$users = $db->Execute($query['user9211']); // PUT THE ARRAY KEY OF YOUR SELECTED QUERY HERE
	$users = $users->GetArray();
	print_r("Number of users affected in the aforementioned interval: ". count($users). ".\n");
	$tmp = array();
	foreach($users as $u) {
		$tmp[] = $u['id'];
	}
	$users = $tmp;
	$db->Close();
	unset($tmp);
} catch (Exception $e) {
	print_r("[ERROR] executing mysql query!\n\n". $e->getMessage() ."\n");
	exit;
}

print_r("Gathering wowza log files... ");
while($datetime_stop >= $day) {
	$postfix = (($day < $datetime_today) ? (".". date("Y-m-d", $day)) : (""));
	$file = LOG_PATH ."access.log". $postfix;
	if (file_exists($file)) {
		$logs[] = $file;
	}
	$day = strtotime("+1 day", $day);
}
print_r("done.\n");

print_r("Analyzing log files...\n");
try {
	$rowsWithoutUID = 0;
	$userlog = array();
	
	$sessionids = getSessionIDs($users, date('Y-m-d', $datetime_start), date('Y-m-d', $datetime_stop));
	if ($sessionids === false) {
		print_r("[ERROR] no sessionids!\n");
		exit;
	}
		
	foreach ($logs as $l) {
		$loghandle = fopen($l, 'r');
		if ($loghandle === false) print_r("CAN'T OPEN ( $l )\n");
		
		while (!feof($loghandle)) {
			$row = fgets($loghandle);
			if (empty($row)) continue;
			if (preg_match('/(?:\bplay\b|\b(?:un)?pause\b|\bstop\b|\bseek\b)/', $row) === 1 and
					preg_match('/(?:\d{1,4}_video_[lh]q)/', $row) === 1) {
				$activitydata = getActivityData($row);
				if (empty($activitydata['uid'])) {
					$rowsWithoutUID++;
					
					if ($rowsWithoutUID > 0) print_r("\r[WARN] missing UID in wowza log! (x$rowsWithoutUID)");
					
					$useridptr = array_search($activitydata['sid'], $sessionids['sid']);
					if ($useridptr === false) continue; // skip row, if the sessionid doesn't match with any of the stored ones
					
					$userid = $sessionids['uid'][$useridptr];
					$activitydata['uid'] = $userid;
				} else {
					$userid = $activitydata['uid'];
				}
				
				if (array_search($userid, $users) === false) continue; // skip row, if it's not what we're looking for
				
				if (array_key_exists($userid, $userlog) === false) {
					$userlog[$userid] = array();
				}
				$userlog[$userid][] = $activitydata;
			} else {
				continue;
			}
		}
		fclose($loghandle);
	}
	print_r("\nLogfile analization finished.\n");
	if ($rowsWithoutUID > 0) {
		print_r("Number of entries with missing UID = ". $rowsWithoutUID .".\n");
	}
	// var_dump($userlog[$users[rand(0, count($users)) - 1]['id']]);exit; // print out a random user's data
} catch (Exception $e) {
	print_r("\n[ERROR] opening file!\n\n". $e->getMessage() ."\n");
	exit;
}

print_r("Writing file...");
if (!dump2file($userlog, $out_dir .'userlog.csv')) {
	print_r("File writing failed!\n");
	exit;
}
print_r("File written to: '". $out_dir ."userlog.csv'\n");
exit;

///////////////////////////////////////////////////////////////////////////////////////////////////
	function getSessionIDs($userids = null, $date_start, $date_stop, $logpath = '') {
///////////////////////////////////////////////////////////////////////////////////////////////////
// Kikeresi a portal bejelentkezesi logjaibol a kulonbozo sessionid-ket, amelyeket userek
// szerint csoportosit.
// Argumentumok:
//  userids (arr): azoknak a felhasznaloknak id-jei, akiknek a sid-jeit keressuk
//  date_start/date_stop (str, y-m-d): a vizsgalodas kezdo es vegdatuma. A megadott intervallumba
//  beleeso logokat elemzi ki.
//  logpath (str, path): opcionalis, a logfile-ok konyvtara.
// Visszateresi ertek:
// A kulonbozo userekhez tartozo sessionID-k, egy tombbe rendezve. A userek altombjeit a userID
// segitsegevel lehet megcimezni.
///////////////////////////////////////////////////////////////////////////////////////////////////
	if (empty($logpath)) $logpath = BASE_PATH . 'data/logs/';
	if (!file_exists($logpath)) {
		print_r("[WARN] Log directory doesn't exists ($logpath)\n");
		return false;
	}
	$logins     = array(
    'uid' => array(),
    'sid' => array(),
		'ts'  => array(),
		'date'=> array(),
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
					$ts    = null;
					$match = null;

					if (preg_match('/(?<=user#)\d+/', $row, $match) === 0) continue;
					if (empty($match)) {
						print_r("no UID!!\n");
						return false;
					}
					$uid = $match[0];

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
						$logins['uid' ][] = $uid;
						$logins['sid' ][] = $sid;
						$logins['ts'  ][] = $ts;
						$logins['date'][] = date('Y-m-d H:i:s', $ts);
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
	function getActivityData($string) {
///////////////////////////////////////////////////////////////////////////////////////////////////
// Kielemzi a megadott wowza logbejegyzesbol(string) a kovetkezo adatokat:
// datum/ido (masodperc pontosan), userid, fajlnev, recordingid, esemeny tipusa (play,stop,...stb.),
// illetve duration (session megnyitasa ota eltelt ido).
//
// A kinyert adatokat egy tombben adja vissza.
//
// Ures string eseten a fuggveny 0 hosszusagu stringekkel feltoltott tombot ad vissza.
///////////////////////////////////////////////////////////////////////////////////////////////////
	$values = array(
		'date'  => '',
		'uid'   => '',
		'sid'   => '',
		'file'  => '',
		'recid' => '',
		'event' => '',
		'dur'   => ''
	);
	if (gettype($string) != "string" || empty($string)) return $values; // if no data supplied, return empty array
	$match = null;

	$values['date']  = preg_match('/(^[\d]{4}-[\d]{2}-[\d]{2})[\s]+([\d]{2}:[0-5][\d]:[0-6][\d])/', $string, $match) == 0 ? // date
	null : $match[1] .' '. $match[2];

	if (preg_match('/(?:uid=)([\d]{1,4})/', $string, $match) === 1) // userid
		$values['uid']   = $match[1];

	if (preg_match('/sessionid[\/:.,&=?\w\d^]+_(\w+)_.*\b/', $string, $match) === 1) {
		$values['sid'] = $match[1];
	}

	if (preg_match('/([\d]{1,4})(?:_video_[lh]q\.mp4)/', $string, $match) === 1) {	// recordingid
		$values['file']  = $match[0];
		$values['recid'] = $match[1];
	}

	if (preg_match('/play|(?:un)?pause|stop|seek/', $string, $match) === 1)
		$values['event'] = $match[0];

	if (preg_match('/(?:\S+\s+){4}([0-9]+\.[0-9]+)/', $string, $match) === 1) // duration, wowza log 13. mezoje
		$values['dur']   = $match[1];

	return $values;
}

///////////////////////////////////////////////////////////////////////////////////////////////////
	function dump2file($data, $path) {
///////////////////////////////////////////////////////////////////////////////////////////////////
// A metodus CSV formatumhoz hasonloan, pontosvesszovel elvalasztva elmenti a kulonbozo
// felhasznalokra kigyujtott, finomitott adatokat.
// Argumentumok: data - tomb, path - logfile eleresi utvonala.
// A bemeneti tomb szerkezete:
// array(
// 	[uid] => array(
// 		[n] => array(['date'] => '',
// 			['uid']   => '',
// 			['sid']   => '',
// 			['file']  => '',
// 			['recid'] => '',
// 			['event'] => '',
// 			['dur']   => ''),
// 		[n+1] => array(...)
// 		)
// 	)
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
		
		foreach ($data as $users) {
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
