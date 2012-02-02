<?php
$config = array(
  'siteid'       => 'teleconnect',
  'hashseed'     => 'ö923mfk3a,.dműteleconnect',
  'version'      => '_v20120118',
  'charset'      => 'UTF-8',
  'cacheseconds' => 3600,
  'errormessage' => 'An unexpected error has occured, our staff has been notified. Sorry for the inconvenience and thanks for your understanding!',
  //-----
  'docroot'      => BASE_PATH . 'httpdocs/',
  'baseuri'      => 'video.teleconnect.hu/', // protocol nelkul, peldaul "dotsamazing.com/"
  'staticuri'    => 'static.teleconnect.hu/',
  'adminuri'     => 'video.teleconnect.hu/a2143/',
  'loginuri'     => 'users/login',
  'cookiedomain' => '.teleconnect.hu',
  //-----
  'logpath'      => BASE_PATH . 'data/logs/',
  'cachepath'    => BASE_PATH . 'data/cache/',
  'modulepath'   => BASE_PATH . 'modules/',
  'libpath'      => BASE_PATH . 'libraries/',
  'templatepath' => BASE_PATH . 'views/',
  'modelpath'    => BASE_PATH . 'models/',
  //-----
  'logemails'    => array(
    'dev@dotsamazing.com',
  ),
  //-----
  'smtp'         => array(
    'auth'     => false,
    'host'     => 'localhost',
    'username' => '',
    'password' => '',
  ),
  'mail'         => array(
    'fromemail' => 'no-reply@teleconnect.hu',
    'fromname'  => 'Teleconnect',
    'errorsto'  => '',
    'type'      => 'text/html; charset="UTF-8"'
  ),
  //-----
  'defaultlanguage' => 'hu',
  'languages'       => array( 'hu' ),
  'locales'         => array( // setlocale-hez
    'hu' => array(
      'hu_HU.UTF-8',
      'Hungarian_Hungary.1250',
    ),
  ),
  'timezone' => 'Europe/Budapest',
  //-----
  'database' => array(
    'type'     => 'mysqli',
    'host'     => '127.0.0.1',
    'username' => 'teleconnect',
    'password' => '6NosJir7PWAanzo9hfv7',
    'database' => 'teleconnect',
    'reconnectonbusy' => true,
    'maxretries' => 30,
  ),
  //-----
  'redis' => array(
    'host' => '127.0.0.1',
    'port' => 6379,
  ),
  //----
  'disable_uploads' => false,
  'uploadpath' => '',
  'recordings_seconds_minlength' => 3,
  'mplayer_identify' => 'mplayer -ao null -vo null -frames 0 -identify %s 2>&1',
  
);

$config['phpsettings'] = array(
  'log_errors'       => 1,
  'display_errors'   => !PRODUCTION, // kikapcsolas utan johet fatal error, ugyhogy meg itt dontsuk el
  'output_buffering' => 0,
  'error_log'        => $config['logpath'] . date( 'Y-m-' ) . 'php.txt',
);

return $config;