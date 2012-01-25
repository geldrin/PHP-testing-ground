<?php

define('BASE_PATH',  realpath( dirname( __FILE__ ) . '/..' ) . '/' );
define('PRODUCTION', @$_ENV['APPLICATION_ENV'] != 'nonprod' );

include_once( BASE_PATH . 'libraries/Springboard/Application.php');
$application = new Springboard\Application( $_REQUEST );
$application->loadConfig('config.php');

if ( !PRODUCTION )
  $application->loadConfig('config_local.php');

$application->bootstrap();
$application->route();
