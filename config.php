<?php
$config = array(
  'siteid'       => 'springboard',
  'hashseed'     => 'űö24ik2ö4kőőokasdf>*>bip',
  'version'      => '_v20120118',
  'charset'      => 'UTF-8',
  //-----
  'docroot'      => BASE_PATH . 'httpdocs/',
  'baseuri'      => '/', // protocol nelkul, peldaul "dotsamazing.com/"
  'staticuri'    => '/',
  'adminuri'     => '/a2143/',
  'loginuri'     => 'users/login',
  'cookiedomain' => '.',
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
    'type'     => 'mysql',
    'host'     => 'localhost',
    'username' => '',
    'password' => '',
    'database' => '',
    'reconnectonbusy' => true,
    'maxretries' => 30,
  ),
);

$config['phpsettings'] = array(
  'log_errors'       => 1,
  'display_errors'   => !PRODUCTION, // kikapcsolas utan johet fatal error, ugyhogy meg itt dontsuk el
  'output_buffering' => 0,
  'error_log'        => $config['logpath'] . date( 'Y-m-' ) . 'php.txt',
);

return $config;