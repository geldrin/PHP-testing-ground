<?php

define('BASE_PATH',	realpath( __DIR__ . '/../../../../..' ) . '/' );
define('DEBUG', false );

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');

// Init
$app = new Springboard\Application\Cli(BASE_PATH, false);

// Load jobs configuration file
$app->loadConfig('modules/Jobs/config_jobs.php');
$jconf = $app->config['config_jobs'];
$myjobid = $jconf['jobid_wowza_monitor'];

// Log init
$debug = Springboard\Debug::getInstance();

set_time_limit(0);

$islive = true;

// Establish database connection
try {
	$db = $app->bootstrap->getAdoDB();
} catch (exception $err) {
    $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot connect to DB.\n" . $err, $sendmail = false);
	exit -1;
}

// Query streaming servers and passwords
$query = "
	SELECT
		ss.id,
		ss.server,
		ss.serverip,
		ss.shortname,
		ss.servicetype,
		ss.adminuser,
		ss.monitoringpassword as password,
        ss.monitoroverhttps,
		ss.disabled
	FROM
		cdn_streaming_servers AS ss
	WHERE
		ss.disabled = 0 AND
        ss.type = 'wowza' AND
        adminuser IS NOT NULL AND
        monitoringpassword IS NOT NULL
    ";

try {
	$monitor_servers = $db->getArray($query);
} catch ( \Exception $err ) {
    $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot query streaming servers.\n" . $err, $sendmail = false);
	exit -1;
}

// Munin: generate labels for munin plugin "config"
$munin_labels = "";
foreach( $monitor_servers as $server ) {
	$munin_labels .= $server['shortname'] . ".label " . $server['shortname'] . "\n";
}

// Wowza app: vsq or devvsq for on demand, vsqlive or devvsqlive for live analysis
$wowza_app = "vsq";
if ( isset($app->config['production']) && $app->config['production'] === false ) $wowza_app = "dev" . $wowza_app;

// Wowza app: live or on demand config
$islive = false;
if ( ( ( count($argv) > 1 ) && ( $argv[1] == 'live' ) ) or ( (count($argv) > 2) && ($argv[2] == 'live') ) ) {
	$islive = true;
	$wowza_app .= "live";
}

// Munin config. See: http://munin-monitoring.org/wiki/protocol-config
if ( (count($argv) > 1) && ($argv[1] == 'config') ) {
	$graph_title = "Videosquare streaming server load (" . $wowza_app . ")";
// total.label All server load
	print("graph_title " . $graph_title . "
graph_category videosquare
graph_vlabel Clients
apptotal.label Total " . $wowza_app . "
" . $munin_labels);
    exit();
}

$total_currentconnections = 0;
$total_currentconnections_perapp = 0;

for ($i = 0; $i < count($monitor_servers); $i++ ) {

	$curl = curl_init();

    $ishttpsenabled = false;
    if ( $monitor_servers[$i]['monitoroverhttps'] > 0 ) {
        $ishttpsenabled = true;
    }
	$wowza_url = ( $ishttpsenabled?"https":"http" ) . "://" . $monitor_servers[$i]['server'] . ":8086/connectioncounts";

	curl_setopt($curl, CURLOPT_URL, $wowza_url); 
	curl_setopt($curl, CURLOPT_PORT, 8086); 
	curl_setopt($curl, CURLOPT_VERBOSE, 0); 
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt($curl, CURLOPT_USERPWD, $monitor_servers[$i]['adminuser'] . ":" . $monitor_servers[$i]['password']);
	curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
    if ( $ishttpsenabled ) {
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);   // false: only for testing!
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        //curl_setopt($ch, CURLOPT_CAINFO, "/CAcerts/BuiltinObjectToken-EquifaxSecureCA.crt"); // For self signed: point to root cert
    }

	$data = curl_exec($curl); 
	if( curl_errno($curl) ){ 
		$err = curl_error($curl);
		curl_close($curl);
		$monitor_servers[$i]['currentconnections'] = "0";		// Munin: undefined value
		streamingServerUpdateDB($monitor_servers[$i]['id'], "unreachable", 0);
        $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Server " . $monitor_servers[$i]['server'] . " is unreachable.\n" . $err, $sendmail = true);
		continue;
	}

	// Check if authentication failed
	$header = curl_getinfo($curl);
	if ( $header['http_code'] == 401 ) {
		curl_close($curl); 
		$monitor_servers[$i]['currentconnections'] = "0";		// Munin: undefined value
		streamingServerUpdateDB($monitor_servers[$i]['id'], "autherror", 0);
        $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] HTTP 401. Cannot authenticate to server " . $monitor_servers[$i]['server'], $sendmail = true);
		continue;
	}

	// Process XML output

	// Open XML data
    libxml_use_internal_errors(true);
	$wowza_xml = simplexml_load_string($data);

    // Valid XML?
    $xml = explode("\n", $data);
    if ( $wowza_xml === false ) {
        
        $err = "";
        foreach(libxml_get_errors() as $error) {
            $err .= $error->message . "\t";
        }
        libxml_clear_errors();
        
        $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot parse XML returned by Wowza. Error:\n\n" . $err, $sendmail = false);
        continue;
    }
    
	// Total number of clients connected to server
	$currentconnections = 0 + (string)$wowza_xml->ConnectionsCurrent;

	if ( is_numeric($currentconnections) ) {
		$monitor_servers[$i]['total_currentconnections'] = $currentconnections;
		// Update current load into database
		streamingServerUpdateDB($monitor_servers[$i]['id'], "ok", $currentconnections);
		$total_currentconnections += $currentconnections;
	}

	// Search for Wowza on demand (dev, non-dev) applications and record number of current connections
	foreach ($wowza_xml->VHost as $w_vhost) {
		$wowza_app_currentconnections = -1;
		foreach ($w_vhost->Application as $w_app) {
			// Wowza load for specific app
			if ( strcmp($w_app->Name, $wowza_app ) == 0 ) $wowza_app_currentconnections = 0 + (string)$w_app->ConnectionsCurrent;
		}
		$monitor_servers[$i][$wowza_app . '_currentconnections'] = $wowza_app_currentconnections;
		$total_currentconnections_perapp += $wowza_app_currentconnections;
	}

	curl_close($curl); 
}

// Per app total load
if ( $total_currentconnections_perapp >= 0 ) {
	echo "apptotal.value " . $total_currentconnections_perapp . "\n";
} else {
	echo "apptotal.value 0\n";
}

foreach( $monitor_servers as $server ) {
	if ( isset($server[$wowza_app . '_currentconnections']) ) {
		if ( $server[$wowza_app . '_currentconnections'] >= 0 ) {
			echo $server['shortname'] . ".value " . $server[$wowza_app . '_currentconnections'] . "\n";
		} else {
			echo $server['shortname'] . ".value 0\n";
		}
	}
}

exit;

function streamingServerUpdateDB($id, $reachable, $currentload) {
 global $db, $debug, $jconf, $myjobid;

	// Query streaming servers and passwords
	$query = "
		UPDATE
			cdn_streaming_servers
		SET
			serverstatus = \"". $reachable . "\",
			currentload = " . $currentload . "
		WHERE
			id = " . $id;

	try {
		$rs = $db->Execute($query);
	} catch ( \Exception $e ) {
		echo "[ERROR]: Cannot update streaming server record.\n" .  $err . "\n";
        $debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Cannot update streaming server record.\n" .  $err, $sendmail = false);
		return false;
	}

	return true;
}

?>
