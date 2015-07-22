<?php

// Csomagok: ifstat ethtool php5-cli php5-curl

/* JSON példa

{
    "reportsequencenum": "13456789",
    "hash": "b8e7ae12510bdfb1812e463a7f086122cf37e4f7",
    "node_fqdn": "stream-edge1.videosquare.eu",
    "node_ip": "172.19.1.8",   
    "type": "nginx",                                     // v. wowza
    "features": {
        "live": {
            "rtmp": true,
            "rtmpt": false,
            "rtmps": false,
            "hls": true,
            "hlss": true,
            "hds": true,
            "hdss": true
        },
        "ondemand": {
            "rtmp": false,
            "rtmpt": false,
            "rtmps": false,
            "hls": true,
            "hlss": true,
            "hds": true,
            "hdss": true
        }
    },
    "network": {
        "interface_speed": 100,                         // Mbps
        "traffic_in": 1324343,                          // bps
        "traffic_out": 1232244                          // bps
    
    },
    "load": {
        "cpu": {
            "current": 15,                              // %
            "load_min": 0.96,                           // load  1 min
            "load_min5": 0.58,                          // load  5 min
            "load_min15": 0.30                          // load 15 min
        },
        "stream": {
            "http": 0,
            "https": 0,
            "rtmp": 12
        }
    }
    "upstream_ping": {
        "stream.vsq.streamnet.hu": {
            "ping_status": "OK",                        // ERR, stb.
            "rtt": 10.108                               // RTT msec
        
        },
        "stream-2.vsq.streamnet.hu": {
        ...
        }
    }
}

*/


// Config: remove from here!!!
$config = array(
    // Node basics
    'hostname'              => 'stream-edge1.videosquare.eu',
    'interface'             => 'eth0',
    // Capacity
    'max_streaming_load'    => 100,     // Max number of clients 
    'max_network_load'      => 100,     // Max permitted network load
    // Node
    'node_type'             => "nginx",                             // nginx/wowza
    'nginx_status_url'      => "http://localhost/nginx_status",
    'nginx_status_port'     => 80,
    // Upstream servers
    'upstream_servers'      => array(
                                'stream.vsq.streamnet.hu',
                            ),                           
    // Security
    'hash_salt'             => "PGHOzVv1vyokz9oLtEiWkRA2tpsT0kX1",
    // Features: supported protocols
    'features' => array(
        'live' => array(
            'rtmp'  => true,
            'rtmpt' => false,
            'rtmps' => false,
            'hls'   => true,
            'hlss'  => false,
            'hds'   => true,
            'hdss'  => false
        ),
        'ondemand' => array(
            'rtmp'  => false,
            'rtmpt' => false,
            'rtmps' => false,
            'hls'   => true,
            'hlss'  => false,
            'hds'   => true,
            'hdss'  => false
        )
    ),

    // Other
    'api_url'               => "https://dev.videosquare.eu/api",    // Videosquare API URL
);

    
// General config
$log_directory = "/var/log/videosquare";
$myjobid = pathinfo($argv[0])['filename'];
$debug = true;

// Log: check directory
if ( !file_exists($log_directory) ) {
    echo "[ERROR] Log directory . " . $log_directory . " does not exists!\n";
    exit -1;
}

// Read sequence number
$sequence_number_file = $log_directory . "/" . $config['hostname'] . ".seq";
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
if ( empty($config['hostname']) ) $config['hostname'] = getMyFQDN();
$api_report_data['hostname'] = $config['hostname'];
if ( $debug ) log_msg("[DEBUG] Node FQDN is: " . $config['hostname']);

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

// Streaming server listen IP for load measurement
if ( $config["node_type"] == "nginx" ) {
    $process = "nginx";
} else {
    $process = "java";
}

unset($output);
$command = "netstat -n -p -l | grep '^tcp' | grep " . $process . " | awk '{print $4}'";
exec($command, $output, $result);
    
if ( $result ) {
    echo "error:\n";
    var_dump($output);
    exit;
}

$api_report_data['load']['clients'] = array(
    'http'  => 0,
    'https' => 0,
    'rtmp'  => 0
);
    
foreach ( $api_report_data['load']['clients'] as $protocol => $clients) {
    switch ($protocol) {
        case 'http':
            $port = 80;
            break;
        case 'https':
            $port = 443;
            break;
        case 'rtmp':
            $port = 1935;
            break;
    }

    unset($output);
    $command = "netstat -n -p | grep " . $process . " | grep " . $api_report_data['network']['ip_address'] . ":" . $port . " | wc -l";
    //echo $command . "\n";
    exec($command, $output, $result);
//var_dump($output);     
//$command_output = implode("\n", $output);

    if ( !$result ) {
        $api_report_data['load']['clients'][$protocol] = $output[0];
        if ( $debug ) log_msg("[DEBUG] Streaming load for " . $protocol . ": " . $api_report_data['load']['clients'][$protocol]);
    }

}

// NGINX status
//nginxGetStatus();

var_dump($api_report_data);

$api_report_data_json = json_encode($api_report_data);
$api_report_data_json_hash = hash_hmac('sha256', $api_report_data_json, $config['hash_salt'] . $reportsequencenum);

echo $api_report_data_json . "\n";
echo $api_report_data_json_hash . "\n";


// Report sequence number: write to file
$err = file_put_contents($sequence_number_file, $reportsequencenum);
if ( $err === false ) {
    log_msg("[ERROR] Cannot write updated report session ID to file: " . $sequence_number_file);
} else {
    if ( $debug ) log_msg("[DEBUG] Report sequence number (" . $reportsequencenum . ") written to file: " . $sequence_number_file);
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
        echo "error:\n" . $command_output . "\n";
        return false;
    }

    $traffic_temp = preg_split('/\s+/', $output[2], -1, PREG_SPLIT_NO_EMPTY);
    //var_dump($traffic_temp);
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
        echo "error:\n" . $command_output . "\n";
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

    $curl = curl_init();

	$url = $config['nginx_status_url'];

	curl_setopt($curl, CURLOPT_URL, $url); 
	curl_setopt($curl, CURLOPT_PORT, $config['nginx_status_port']); 
	curl_setopt($curl, CURLOPT_VERBOSE, 0); 
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); 

	$data = curl_exec($curl); 
	if( curl_errno($curl) ){ 
		$err = curl_error($curl);
		curl_close($curl);
        //$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] Server " . $monitor_servers[$i]['server'] . " is unreachable.\n" . $err, $sendmail = true);
		return false;
	}

	// Check if authentication failed
	$header = curl_getinfo($curl);
	if ( $header['http_code'] == 401 ) {
		curl_close($curl); 
        //$debug->log($jconf['log_dir'], $myjobid . ".log", "[ERROR] HTTP 401. Cannot authenticate to server " . $monitor_servers[$i]['server'], $sendmail = true);
		return false;
	}

    var_dump($data);

exit;    
    return true;

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
 global $config, $log_directory, $myjobid;
 
    if ( empty($msg) ) return true;
    
    // Log file preparation
    $vsq_sitename = parse_url($config["api_url"])["host"];
    $log_file = $log_directory. "/" . $vsq_sitename . "-" . date("Y-m") . "-" . $myjobid . ".log";

    if ( !file_exists($log_file) ) {
        if ( !touch($log_file) ) {
            echo "[ERROR] Cannot create log file " . $log_file . "\n";
            return false;
        }
    }

    $date = date("Y-m-d H:i:s.u");
    
    file_put_contents($log_file, $date . ": " . trim($msg) . "\n", FILE_APPEND | LOCK_EX);
    
    return true;
}
    
?>