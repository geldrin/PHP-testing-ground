<?php
define('BASE_PATH',  realpath( __DIR__ . '/../../..' ) . '/' );
define('PRODUCTION', 0 );
//define('DEBUG', true );
include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');

$app = new Springboard\Application\Cli();
$fd  = fopen('genres.csv', 'r');

$genres = array();

while ( ( $data = fgetcsv( $fd, 1000, ';') ) !== false )
  $genres[] = array(
    'name'          => $data[0],
    'name_stringid' => 0,
    'namehungarian' => $data[0],
    'nameenglish'   => $data[1],
    'origparentid'  => $data[2],
  );

$genreModel = $app->bootstrap->getModel('genres');
$parentid   = '0';

foreach ( $genres as $data ) {
  
  if ( empty( $data ) )
    continue;
  
  $strings = array(
    'name_stringid' => array(
      'hu' => $data['namehungarian'],
      'en' => $data['nameenglish']
    ),
  );
  
  if ( $data['origparentid'] !== '0' )
    $data['parentid'] = $parentid;
  else
    $data['parentid'] = 0;
  
  $row = $genreModel->insert( $data, $strings, false );
  
  if ( $data['origparentid'] == 0 )
    $parentid = $row['id'];
  
}
