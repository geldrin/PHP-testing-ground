<?php
define('BASE_PATH',  realpath( __DIR__ . '/../../..' ) . '/' );
define('PRODUCTION', 0 );
//define('DEBUG', true );
include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');

$app = new Springboard\Application\Cli( BASE_PATH, PRODUCTION );

$langfd = fopen('lang.csv', 'r');
$languages = array();

while ( ( $data = fgetcsv( $langfd, 1000, ';') ) !== false )
  $languages[] = array(
    'shortname'     => $data[0],
    'originalname'  => $data[1],
    'namehungarian' => $data[2],
    'nameenglish'   => $data[3],
    'weight'        => $data[4],
    'name'          => $data[2],
    'name_stringid' => 0,
  );

$langModel = $app->bootstrap->getModel('languages');

foreach ( $languages as $data ) {
  
  if ( empty( $data ) )
    continue;
  
  $strings = array(
    'name_stringid' => array(
      'hu' => $data['namehungarian'],
      'en' => $data['nameenglish']
    ),
  );
  
  $langModel->insert( $data, $strings, false );
  
}
