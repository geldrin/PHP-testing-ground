<?php

define('BASE_PATH',  realpath( dirname( __FILE__ ) . '/..' ) . '/' );
if ( isset( $_SERVER['APPLICATION_ENV'] ) and $_SERVER['APPLICATION_ENV'] == 'developer' )
  define('PRODUCTION', false );
else
  define('PRODUCTION', true );

include_once( BASE_PATH . 'libraries/Springboard/Application.php');
$application = new Springboard\Application( BASE_PATH, PRODUCTION, $_REQUEST );
$application->loadConfig('config.php');

if ( !PRODUCTION )
  $application->loadConfig('config_local.php');

$application->bootstrap();
$application->route();
