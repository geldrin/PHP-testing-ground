<?php
// Media conversion job v0 @ 2012/02/??

define('BASE_PATH',	realpath( __DIR__ . '/../../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');

// Init
$app = new Springboard\Application\Cli(BASE_PATH, PRODUCTION);

// Load jobs configuration file
$app->loadConfig('modules/Jobs/config_jobs.php');
$jconf = $app->config['config_jobs'];

// Log related init
$debug = Springboard\Debug::getInstance();

// Establish database connection
try {
	$db = $app->bootstrap->getAdoDB();
} catch (exception $err) {
	exit;
}

echo "Streaming servers identified for test:\n";
$nets = getStreamingServerClientNetworks();
var_dump($nets);

$ip_selection_manual = false;
$ips = array('10.1.1.1', '128.12.1.1', '91.120.12.12', '8.8.8.8');

echo "IP address subnets identified for test (from DB):\n";
$cnets = getClientNetworks();
var_dump($cnets);

if ( $ip_selection_manual ) {
    echo "IP addresses for test (manual):\n";
    var_dump($ips);
}

$streamingserverModel  = $app->bootstrap->getModel('streamingservers');

do {

    // Random selecting an IP address
    if ( $ip_selection_manual ) {
        $idx = rand(0, count($ips) - 1);
        $ip = $ips[$idx];
    } else {
        $idx = rand(0, count($cnets) - 1);
        $ip_start = ip2long($cnets[$idx]['ipaddressstart']);
        $ip_end = ip2long($cnets[$idx]['ipaddressend']);
        $ip_long = rand($ip_start, $ip_end);
        $ip = long2ip($ip_long);
        echo "Selected subnet is: id = " . $cnets[$idx]['id'] . " | " . $cnets[$idx]['ipaddressstart'] . " - " . $cnets[$idx]['ipaddressend'] . "\n";
    }
    
    $ss = $streamingserverModel->getServerByClientIP($ip, 'live');
    echo "IP: " . $ip . " | Server selected: " . print_r($ss, true) . "\n";
   
    $sidxs = recursive_array_search($ss['server'], $nets);
var_dump($sidxs);
    $found =  false;
    if ( $sidxs !== false ) {
        for ( $q = 0; $q < count($sidxs); $q++) {
            $i = $sidxs[$q];
            $ip_long = ip2long($ip);
            echo "SEARCH:";
            var_dump($nets[$i]);
            if ( ( ( ip2long($nets[$i]['ipaddressstart']) <= $ip_long ) and ( $ip_long <= ip2long($nets[$i]['ipaddressend']) ) ) or ( $nets[$i]['default'] == 1 ) ) $found = true;
        }
    }
    echo "Check result: ";
    if ( $found ) {
        echo "OK\n";
    } else {
        echo "ERROR\n";
    }
    
    usleep(500000);

} while (1);

function getStreamingServerClientNetworks() {
global $app, $db;
    
    $query = "
        SELECT
            css.id,
            css.server,
            css.serverstatus,
            css.default,
            ccn.name,
            ccn.ipaddressstart,
            ccn.ipaddressend 
        FROM
            cdn_streaming_servers AS css
        LEFT JOIN
            cdn_servers_networks AS csn
        ON
            css.id = csn.streamingserverid
        LEFT JOIN
            cdn_client_networks AS ccn
        ON
            csn.clientnetworkid = ccn.id
    ";
	
	try {
		$nets = $db->getArray($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], "streaming_server_selection_test.log", "[ERROR] SQL query failed.\n" . trim($query) . "\n" . $err . "\n", false);
		exit -1;
	}   

    return $nets;
}

function getClientNetworks() {
global $app, $db;

	$query = "
        SELECT
            ccn.id,
            ccn.name,
            ccn.ipaddressstart,
            ccn.ipaddressend 
        FROM
            cdn_client_networks AS ccn
        WHERE
            ccn.disabled = 0
    ";
	
	try {
		$cnets = $db->getArray($query);
	} catch (exception $err) {
		$debug->log($jconf['log_dir'], "streaming_server_selection_test.log", "[ERROR] SQL query failed.\n" . trim($query) . "\n" . $err . "\n", false);
		exit -1;
	}   

    return $cnets;

}

function recursive_array_search($needle, $haystack) {
    
    if ( !is_array($haystack) ) return false;
    
    $keys_matched = array();
    
    foreach( $haystack as $key => $value ) {
        $current_key = $key;
        if ( $needle === $value OR ( is_array($value) && recursive_array_search($needle, $value) !== false ) ) {
            array_push($keys_matched, $current_key);
        //            return $current_key;
        }
    }

    if ( count($keys_matched) < 1 ) {    
        return false;
    } else {
        return $keys_matched;
    }
}


?>
