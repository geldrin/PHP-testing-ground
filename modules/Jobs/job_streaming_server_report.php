<?php

// Videosquare streaming server status report script
// * Required packages (Debian): ifstat ethtool php5-cli php5-curl

$myversion = "2015-09-17";

set_time_limit(0);
date_default_timezone_set("Europe/Budapest");

if ($argc < 2) {
    echo "USAGE: Server settings has been not passed. Terminating.\n";
    exit -1;
} elseif (realpath($argv[1]) !== false) {
	include_once $argv[1];
} else {
	echo "ERROR: '" . $argv[1] . "' not found! Terminating.\n";
	exit -1;
}

// General config
$myjobid = pathinfo($argv[0])['filename'];
$debug = true;

// Log: check directory
if ( !file_exists($config['log_directory']) ) {
    echo "[ERROR] Log directory . " . $config['log_directory'] . " does not exists!\n";
    exit -1;
}

if ( $debug ) log_msg("[DEBUG] Streaming server reporting script started. Version: " . $myversion);

// Read sequence number
$sequence_number_file = $config['log_directory'] . "/" . $config['server'] . ".seq";
if ( !file_exists($sequence_number_file) ) {
    $reportsequencenum = rand(0, 999999);
    if ( $debug ) log_msg("[DEBUG] Previous sequence number not found. Initial sequence number generated: " . $reportsequencenum);
} else {

    if ( $debug ) log_msg("[DEBUG] Sequence number file: " . $sequence_number_file);

    $reportsequencenum = file_get_contents($sequence_number_file);
    if ( ( $reportsequencenum === false ) or ( !is_numeric($reportsequencenum) ) ) {
        log_msg("[ERROR] Report session ID read from sequence number file is not numeric. See: " . $sequence_number_file);
    }

    if ( $debug ) log_msg("[DEBUG] Previous report sequence number read from file: " . $reportsequencenum);
    $reportsequencenum++;
}

// ## API report data init
$api_report_data = array();
$api_report_data['date'] = date("Y-m-d H:i:s");
$api_report_data['reportsequencenum'] = $reportsequencenum;
$api_report_data['features'] = $config['features'];

// ## Network

// Host name
if ( empty($config['server']) ) $config['server'] = getMyFQDN();
$api_report_data['server'] = $config['server'];
if ( $debug ) log_msg("[DEBUG] Node FQDN is: " . $config['server']);

// Network interface check
//$network_interfaces = getNetworkInterfaces();
if ( $debug ) log_msg("[DEBUG] Node network interface is: " . $config['interface']);
$api_report_data['network'] = getNetworkInterfaceInfo($config['interface']);
if ( $debug ) log_msg("[DEBUG] Network interface info: " . print_r($api_report_data['network'], true));

// Ping upstream servers
$upstream_server_status = array();
foreach ( $config['upstream_servers'] as $key => $server) {
    if ( $debug ) log_msg("[DEBUG] Upstream ping to: " . $server);
    $upstream_server_status[$server] = pingAddress($server);
    if ( $debug ) log_msg("[DEBUG] Upstream ping results: " . print_r($upstream_server_status[$server], true));
}

$api_report_data['upstream_ping'] = $upstream_server_status;

// ## CDN node load

// CPU usage
$api_report_data['load']['cpu']['current'] = trim(`top -b -n 1 | grep "Cpu(s)" | awk '{ print $2+$4+$6; }'`);

// Load average
$load = trim(`cat /proc/loadavg | awk '{print $1 "#" $2 "#" $3}'`);
$tmp = explode("#", $load, 3);
$api_report_data['load']['cpu']['min'] = $tmp[0];
$api_report_data['load']['cpu']['min5'] = $tmp[1];
$api_report_data['load']['cpu']['min15'] = $tmp[2];
if ( $debug ) log_msg("[DEBUG] CPU load information: " . print_r($api_report_data['load']['cpu'], true));

// Streaming server load (NGINX)
if ( $config['node_type'] == "nginx" ) {
    $load = getNGINXLiveStreamingLoad($api_report_data['network']['ip_address']);
    $api_report_data['load']['clients'] = $load;
}

// Ezt tudjuk használni a pontosabb statisztikákhoz???
//nginxGetStatus();

// Streaming server load (WOWZA)
if ( $config['node_type'] == "wowza" ) {
    $load = getWowzaLiveStreamingLoad();
    $api_report_data['load']['clients'] = $load;
}

// ## CURL: commit data through Videosquare API
$api_report_data_json = json_encode($api_report_data);
$api_report_data_json_hash = hash_hmac('sha256', $api_report_data_json, $config['hash_salt'] . $reportsequencenum);

if ( $debug ) {
    log_msg("[DEBUG] Data to report:\n" . print_r($api_report_data, true));
    log_msg("[DEBUG] JSON data to report:\n" . $api_report_data_json);
    log_msg("[DEBUG] Data hash: " . $api_report_data_json_hash);
}

// Curl connection
$ch = curl_init();
$url  = $config['api_url'] . "?layer=controller&module=streamingservers&method=updatestatus";
$url .= "&server=" . $config['server'];
$url .= "&hash=" . $api_report_data_json_hash;
$url .= "&reportsequencenum=" . $reportsequencenum;
$url .= "&format=json";

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
curl_setopt($ch, CURLOPT_POSTFIELDS, $api_report_data_json);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Content-Length: ' . strlen($api_report_data_json))
);

if ( $debug ) log_msg("[DEBUG] Calling API at: " . $url);

$result = curl_exec($ch);

$flag_http_error = false;

// Handle connection error
if( curl_errno($ch) ){ 
    $err = curl_error($ch);
    curl_close($ch);
    log_msg("[ERROR] Server API " . $config['api_url'] . " is not reachable.");
    exit;
}

// Check HTTP error code
$header = curl_getinfo($ch);
if ( $header['http_code'] >= 400 ) {
    log_msg("[ERROR] Server error. HTTP error code: " . $header['http_code']);
    curl_close($ch);
    exit;
}

curl_close($ch);

// Log API return value
if ( $debug ) log_msg("[INFO] API returned value: " . $result);

// Check return value
$flag_save_sequence_number = true;
$result_array = json_decode($result, true);
if ( $result_array['result'] != "OK" ) {
    log_msg("[ERROR] API reported an error: " . $result_array['result'] . " / Message: '" . $result_array['data'] . "'");
    $flag_save_sequence_number = false;
}

// Report sequence number: write to file
if ( $flag_save_sequence_number ) {
    $err = file_put_contents($sequence_number_file, $reportsequencenum);
    if ( $err === false ) {
        log_msg("[ERROR] Cannot write updated sequence number to file: " . $sequence_number_file);
    } else {
        if ( $debug ) log_msg("[DEBUG] Report sequence number (" . $reportsequencenum . ") written to file: " . $sequence_number_file);
    }
} else {
    if ( $debug ) log_msg("[INFO] Sequence number not written to file due to an error.");
}

exit;

function getNetworkInterfaces($withV6 = false) {

    $lines = file('/proc/net/dev');
    $interfaces = array();
    for ($i = 2; $i < count($lines);  $i++) {
        $line = explode(':', $lines[$i]);
        $interface = trim($line[0]);
        if ( !preg_match("/^lo/", $interface) ) {
            preg_match_all('/inet'.($withV6 ? '6?' : '').' addr: ?([^ ]+)/', `/sbin/ifconfig "$interface"`, $ips);
            $interfaces[$interface]['ip_addresses'] = $ips[1];
            $interfaces[$interface]['traffic'] = getNetworkInterfaceTraffic($interface);
            $interfaces[$interface]['speed_mbps'] = getNetworkInterfaceSpeed($interface);
        }
    }    
    
    return($interfaces);
}

function getNetworkInterfaceInfo($interface, $withV6 = false) {

    if ( empty($interface) ) return false;
    
    $info = array();
    
    preg_match_all('/inet'.($withV6 ? '6?' : '').' addr: ?([^ ]+)/', `/sbin/ifconfig "$interface"`, $ips);
    $info['ip_address'] = $ips[1][0];
    $info['interface_speed'] = getNetworkInterfaceSpeed($interface);
    $traffic = getNetworkInterfaceTraffic($interface);
    $info['traffic_in'] = $traffic['traffic_in'];
    $info['traffic_out'] = $traffic['traffic_out'];
 
    return($info);
}


function getNetworkInterfaceTraffic($iface_name) {

    unset($output);
    
    $command = "ifstat -i " . $iface_name . " -w -b -n -q 0.5 1";
    exec($command, $output, $result);
	$command_output = implode("\n", $output);
    
    if ( $result ) {
        log_msg("[ERROR] Error running command: " . $command . "\nOutput:\n" . $command_output);
        return false;
    }

    $traffic_temp = preg_split('/\s+/', $output[2], -1, PREG_SPLIT_NO_EMPTY);

    $traffic = array(
        'traffic_in'    => intval(trim($traffic_temp[0]) * 1000),
        'traffic_out'   => intval(trim($traffic_temp[1]) * 1000)
    );
        
    return($traffic);
    
}

function getNetworkInterfaceSpeed($iface_name) {

    unset($output);
    
    $command = "/sbin/ethtool " . $iface_name . " 2>&1 | grep 'Speed:'";
    exec($command, $output, $result);
	$command_output = implode("\n", $output);
    
    if ( $result ) {
        log_msg("[ERROR] Error running command: " . $command . "\nOutput:\n" . $command_output);
        return false;
    }

    preg_match("|\d+|", $command_output, $tmp);

    return $tmp[0];
}

function pingAddress($ip) {
    
    $ping_num = 5;
    
    $command = "/bin/ping -A -q -c " . $ping_num . " " . $ip . " 2>&1 | grep 'transmitted\|rtt'";
    exec($command, $output, $result);
    
    if ( $result != 0 ) {
        $ping_result = array( 'status' => false );
        return $ping_result;
    }

    $ping = array(
        'status'        => true
    );
    
    //var_dump($output);
    
    // Match numbers in line: "5 packets transmitted, 5 received, 0% packet loss, time 401ms"
    preg_match_all('/([\d]+)/', $output[0], $tmp);
    //var_dump($tmp);
    
    $ping['packets_sent'] = trim($tmp[0][0]);
    $ping['packets_received'] = trim($tmp[0][1]);
    $ping['packet_loss'] = trim($tmp[0][2]);
   
    // Match numbers in line: "rtt min/avg/max/mdev = 0.151/0.191/0.242/0.037 ms, ipg/ewma 200.689/0.184 ms"
    preg_match_all('/([\d]+.[\d]+)/', $output[1], $tmp);
    //var_dump($tmp);
    $ping['rtt_avg'] = trim($tmp[0][1]);
   
    // If packet loss is detected, status is false
    if ( $ping['packets_received'] != $ping_num) $ping['status'] = false;
   
    return $ping;
}

function nginxGetStatus() {
 global $config;

    $ch = curl_init();

	$url = $config['nginx_status_url'];

	curl_setopt($ch, CURLOPT_URL, $url); 
	curl_setopt($ch, CURLOPT_PORT, $config['nginx_status_port']); 
	curl_setopt($ch, CURLOPT_VERBOSE, 0); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 

	$data = curl_exec($ch); 
	if( curl_errno($ch) ){ 
		$err = curl_error($ch);
		curl_close($ch);
        //$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Server " . $monitor_servers[$i]['server'] . " is unreachable.\n" . $err, $sendmail = true);
		return false;
	}

	// Check if authentication failed
	$header = curl_getinfo($ch);
	if ( $header['http_code'] == 401 ) {
		curl_close($ch); 
        //$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] HTTP 401. Cannot authenticate to server " . $monitor_servers[$i]['server'], $sendmail = true);
		return false;
	}

    curl_close($ch);
    
    return true;

}

// This is quite limited.
// RTMP live: count netstat connections. Empty content channel is counted...
// HTTP live: nginx status interface?
function getNGINXLiveStreamingLoad() {
global $config, $api_report_data, $debug;

    $load = array(
        'rtmp'  => 0,
        'http'  => 0,
        'https' => 0,
        'rtsp'  => 0
    );
    
    foreach ( $load as $protocol => $clients) {

        switch ($protocol) {
            case 'http':
                $port = 80;
                break;
            case 'https':
                $port = 443;
                break;
            case 'rtsp':
                $port = 554;
                break;
            case 'rtmp':
                $port = 1935;
                break;
        }

        unset($output);
        $command = "netstat -n -p | grep nginx | grep " . $api_report_data['network']['ip_address'] . ":" . $port . " | wc -l";
        exec($command, $output, $result);
        
        if ( $debug ) log_msg("[DEBUG] NGINX live load command for '" . $protocol . "': " . $command . " (Clients: " . $output[0] . ")");

        $load['currentload'] = 0;
        $load['appload'] = 0;
        
        if ( !$result ) if ( is_numeric($output[0]) ) {
            $load[$protocol] = $output[0];
            $load['currentload'] += $output[0];
            $load['appload'] += $output[0];
        }
        
    }

    return $load;
}

function getWowzaLiveStreamingLoad() {
global $config, $api_report_data, $debug;

    $load = array(
        'rtmp'  => 0,
        'http'  => 0,
        'https' => 0,
        'rtsp'  => 0,
        'currentload' => 0,
        'appload' => 0
    );

	$ch = curl_init();
    $wowza_url = sprintf($config['wowza_status_url'], $config['server']);
	curl_setopt($ch, CURLOPT_URL, $wowza_url);
	curl_setopt($ch, CURLOPT_PORT, $config['wowza_status_port']);
	curl_setopt($ch, CURLOPT_VERBOSE, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt($ch, CURLOPT_USERPWD, $config['wowza_user'] . ":" . $config['wowza_password']);
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
    if ( stripos($wowza_url, "https") !== false ) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    }

    $data = curl_exec($ch); 
	if( curl_errno($ch) ){ 
		$err = curl_error($ch);
		curl_close($ch);
        log_msg("[ERROR] Wowza server " . $wowza_url . " is not reachable.");
		return false;
	}

	// Check if authentication failed
	$header = curl_getinfo($ch);
	if ( $header['http_code'] >= 400 ) {
        curl_close($ch);
        log_msg("[ERROR] HTTP 401. Cannot authenticate to server " . $config['wowza_status_url']);
		return false;
	}

	// Process XML output
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
        
        log_msg("[ERROR] Cannot parse XML returned by Wowza. Error:\n\n" . $err);
        
        return false;
    }

    // Total number of clients connected to server
	$load['currentload'] = 0 + (string)$wowza_xml->ConnectionsCurrent;
    
	// Search for Wowza application and record number of connections
	foreach ($wowza_xml->VHost as $w_vhost) {
		foreach ($w_vhost->Application as $w_app) {
			// Wowza load for specific app           
			if ( strcmp($w_app->Name, $config['wowza_app_live'] ) == 0 ) {
                $load['appload'] = 0 + (string)$w_app->ConnectionsCurrent;
                $load['currentload'] += $load['appload'];
                foreach ($w_app->ApplicationInstance as $w_appinst) {
                    $load['rtmp'] += 0 + (string)$w_appinst->Stream->SessionsFlash;
                    $load['http'] += 0 + (string)$w_appinst->Stream->SessionsCupertino + (string)$w_appinst->Stream->SessionsSanJose + (string)$w_appinst->Stream->SessionsSmooth + (string)$w_appinst->Stream->SessionsMPEGDash;
                    $load['rtsp'] += 0 + (string)$w_appinst->Stream->SessionsRTSP;
                }
            }
		}
	}

	curl_close($ch); 
    
    return $load;
}

function getMyFQDN() {

    $command = "hostname --fqdn";
    exec($command, $output, $result);
    
    if ( $result != 0 ) {
        return false;
    }
    
    return $output[0];
}

function log_msg($msg) {
 global $config, $config, $myjobid;
 
    if ( empty($msg) ) return true;
    
    // Log file preparation
    $vsq_sitename = parse_url($config['api_url'])['host'];
    $log_file = $config['log_directory']. "/" . date("Y-m") . "-" . $myjobid . "-" . $vsq_sitename . ".log";

    if ( !file_exists($log_file) ) {
        if ( !touch($log_file) ) {
            echo "[ERROR] Cannot create log file " . $log_file . "\n";
            exit;
        }
    }

    $date = date("Y-m-d H:i:s");
    
    file_put_contents($log_file, $date . ": " . trim($msg) . "\n", FILE_APPEND | LOCK_EX);
    
    return true;
}
    
?>
