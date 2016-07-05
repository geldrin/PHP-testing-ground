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

$len = $application->bootstrap->config['livepinlength'];
$min = pow( 10, $len - 1 );
$max = pow( 10, $len ) - 1;

$db              = $application->bootstrap->getAdoDB();
$rs              = $db->query("
  SELECT id, pin
  FROM livefeeds
  WHERE
    pin IS NULL OR
    pin < '$min' OR
    pin > '$max'
");

echo "<pre>Updating livefeeds with NULL pins:\n";
flush();

$pins = array();

foreach( $rs as $row )
  $pins[ $row['id'] ] = mt_rand( $min, $max );

$rs->close();

foreach( $pins as $id => $pin ) {
  while(true) {
    try {

      if ( !$pin )
        $pin = mt_rand( $min, $max );

      $db->execute("
        UPDATE livefeeds
        SET pin = '$pin'
        WHERE id = '$id'
        LIMIT 1
      ");
      echo ".";
      flush();
      break;
    } catch( \Exception $e ) {
      $errno = $this->db->ErrorNo();
      // mysql unique constraint error code 1586/1062/893
      if ( $errno == 1586 or $errno == 1062 or $errno == 893 ) {
        $pin = 0;
        continue;
      } else // valami mas hiba, re-throw
        throw $e;
    }
  }
}
