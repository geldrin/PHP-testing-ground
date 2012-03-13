<?php

define('BASE_PATH',  realpath( dirname( __FILE__ ) . '/../..' ) . '/' );
define('PRODUCTION', @$_SERVER['APPLICATION_ENV'] != 'developer' );

include_once( BASE_PATH . 'libraries/Springboard/Application.php');
include_once( BASE_PATH . 'libraries/Springboard/Application/Admin.php');
$application = new Springboard\Application\Admin( BASE_PATH, PRODUCTION, $_REQUEST );
$application->loadConfig('config.php');

if ( !PRODUCTION )
  $application->loadConfig('config_local.php');

$application->bootstrap();
$application->route();
