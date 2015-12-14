<?php
// Media conversion job v0 @ 2012/02/??

define('BASE_PATH',	realpath( __DIR__ . '/../..' ) . '/' );
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
$cache = $app->bootstrap->getCache();

// Establish database connection
try {
	$db = $app->bootstrap->getAdoDB();
} catch (exception $err) {
	exit;
}

$ips = array('10.1.1.1', '128.12.1.1', '91.120.12.12', '8.8.8.8');
$streamingserverModel  = $app->bootstrap->getModel('streamingservers');

do {

    $idx = rand(0, count($ips) - 1);
    $ip = $ips[$idx];
    $ss = $streamingserverModel->getServerByClientIP($ip, 'live');
    echo "IP: " . $ip . " | Server selected: " . $ss . "\n";

} while (1);


?>
