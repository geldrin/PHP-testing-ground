<?php

define('BASE_PATH',  realpath( dirname( __FILE__ ) . '/../..' ) . '/' );
set_time_limit(0);

if ( isset( $_SERVER['APPLICATION_ENV'] ) and $_SERVER['APPLICATION_ENV'] == 'developer' )
  define('PRODUCTION', false );
else
  define('PRODUCTION', true );

include_once( BASE_PATH . 'libraries/Springboard/Application.php');
$application = new Springboard\Application( BASE_PATH, PRODUCTION, array() );
$application->loadConfig('config.php');
$application->loadConfig('config_local.php');

$application->bootstrap();
$db              = $application->bootstrap->getAdoDB();
$rs              = $db->query("SELECT id FROM recordings");
$recordingsModel = $application->bootstrap->getModel('recordings');

echo "<pre>Updating recording metadata:\n";
flush();

foreach( $rs as $fields ) {
  
  $recordingsModel->select( $fields['id'] );
  $recordingsModel->updateFulltextCache();
  echo ".";
  flush();
  
}

echo "\nDone!";