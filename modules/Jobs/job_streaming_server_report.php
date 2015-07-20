<?php

// Csomagok: ifstat ethtool

// Config: remove from here!!!
$config = array(
    'api_url'               => "https://dev.videosquare.eu/api",
    'hash_salt'             => "1323221323223232",
    'max_streaming_load'    => 100,
    'max_network_load'      => 100,                     // Mbps
    'node_type'             => "nginx",                 // wowza/nginx
    'upstream_servers'      => array(
                                    'stream.vsq.streamnet.hu',
                               ),                           
    );

// General config
$log_directory = "/var/log/videosquare";
$myjobid = pathinfo($argv[0])['filename'];

// Log file preparation
$vsq_sitename = parse_url($config["api_url"])["host"];
$log_file = $log_directory. "/" . $vsq_sitename . "-" . date("Y-m") . "-" . $myjobid . ".log";

if ( !file_exists($log_directory) ) {
    echo "[ERROR] Log directory . " . $log_directory . " does not exists!\n";
    exit -1;
}

if ( !file_exists($log_file) ) {
    if ( !touch($log_file) ) {
        echo "[ERROR] Cannot create log file " . $log_file . "\n";
        exit -1;
    }
}

// Network interface check
$network_interfaces = getNetworkInterfaces();
var_dump($network_interfaces);

// Ping upstream servers
$upstream_server_status = array();
foreach ( $config['upstream_servers'] as $key => $server) {
    $upstream_server_status[$server] = pingAddress($server);
}

var_dump($upstream_server_status);

//// CDN node load
$node = array();

// CPU usage
$node['load']['cpu_current'] = trim(`top -b -n 1 | grep "Cpu(s)" | awk '{ print $2+$4+$6; }'`);
// Load average
$load = trim(`cat /proc/loadavg | awk '{print $1 "#" $2 "#" $3}'`);
$tmp = explode("#", $load, 3);
$node['load']['min'] = $tmp[0];
$node['load']['min5'] = $tmp[1];
$node['load']['min15'] = $tmp[2];

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

$node['streaming_load'] = array();
foreach ( $network_interfaces as $interface => $interface_data ) {
    foreach ( $interface_data["ip_addresses"] as $key => $ip_address) {
        if ( !isset($node['streaming_load'][$ip_address]) ) {        
            $node['streaming_load'][$ip_address] = array(
                'http'  => 0,
                'https' => 0,
                'rtmp'  => 0
            );
        }
    }
}
    
foreach ( $node['streaming_load'] as $ip_address => $load_stats) {
echo $ip_address . "\n";
    foreach ( $load_stats as $protocol => $value) {
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
        $command = "netstat -n -p | grep " . $process . " | grep " . $ip_address . ":" . $port . " | wc -l";
        //echo $command . "\n";
        exec($command, $output, $result);
//var_dump($output);     
//$command_output = implode("\n", $output);

        if ( !$result ) {
            $node['streaming_load'][$ip_address][$protocol] = $output[0];
        }
  
    }
}

var_dump($node);

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
        'in_bps'    => intval(trim($traffic_temp[0]) * 1000),
        'out_bps'   => intval(trim($traffic_temp[1]) * 1000)
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

?>