<?php

// Generate given number of Videosquare users with random username, password and validated status

define('BASE_PATH',	realpath( __DIR__ . '/../..' ) . '/' );
define('PRODUCTION', false );
define('DEBUG', false );

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');

set_time_limit(0);

$iscommit = true;

// Init
$app = new Springboard\Application\Cli(BASE_PATH, PRODUCTION);

// Establish database connection
try {
	$db = $app->bootstrap->getAdoDB();
} catch (exception $err) {
	// Send mail alert, sleep for 15 minutes
	echo "[ERROR] No connection to DB (getAdoDB() failed). Error message:\n" . $err . "\n";
	exit -1;
}

$channelModel = $app->bootstrap->getModel('channels');
$channels     = $db->query("
SELECT *
FROM channels
");

foreach( $channels as $channel ) {
  $channelModel->id  = $channel['id'];
  $channelModel->row = $channel;
  $channelModel->updateVideoCounters();
}

$categoryModel = $app->bootstrap->getModel('categories');
$categories     = $db->query("
SELECT *
FROM categories
");

foreach( $categories as $category ) {
  $categoryModel->id  = $category['id'];
  $categoryModel->row = $category;
  $categoryModel->updateVideoCounters();
}

exit;

?>
