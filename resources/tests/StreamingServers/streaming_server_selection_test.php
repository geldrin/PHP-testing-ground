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
//$cache = $app->bootstrap->getCache();
$debug = false;

// Establish database connection
try {
	$db = $app->bootstrap->getAdoDB();
} catch (exception $err) {
	exit;
}

// IP list for random selection
$ips = array('10.1.1.1', '128.12.1.1', '91.120.12.12', '8.8.8.8', '172.19.1.100', '172.19.0.1', '172.19.3.12', '172.20.0.12');

$streamingserverModel  = $app->bootstrap->getModel('streamingservers');

$i = 0;
$stats = array();

do {

    $idx = rand(0, count($ips) - 1);
    $ip = $ips[$idx];
    $ss = $streamingserverModel->getServerByClientIP($ip, 'live');
    if ( $debug ) {
        $myserver = print_r($ss, true);
    } else {
        $myserver = $ss['server'];
    }
    echo "IP: " . $ip . " | Server selected: " . $myserver . "\n";

    if ( isset($stats[$ip][$ss['server']]) ) {
        $stats[$ip][$ss['server']]++;
    } else {
        $stats[$ip][$ss['server']] = 0;        
    }
    
    $i++;
    
} while ( $i < 1000 );

echo "Stats for IPs/servers:\n";

print_r($stats);

?>
