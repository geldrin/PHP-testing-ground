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

if ( !PRODUCTION )
  $application->loadConfig('config_local.php');

echo "<pre>Inserting departmentids...\n";
flush();

$application->bootstrap();
$db           = $application->bootstrap->getAdoDB();
$rs           = $db->query("SELECT id AS userid, departmentid FROM users WHERE departmentid <> 0");
$usersdepartments = $application->bootstrap->getModel('users_departments');

foreach( $rs as $fields ) {
  
  $usersdepartments->insert( $fields );
  
}

echo "Done!";