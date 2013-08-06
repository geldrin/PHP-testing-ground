#!/usr/bin/env php
<?php
define('BASE_PATH',  realpath( dirname( __FILE__ ) ) . '/' );
set_time_limit(0);

if ( isset( $_SERVER['APPLICATION_ENV'] ) and $_SERVER['APPLICATION_ENV'] == 'developer' )
  define('PRODUCTION', false );
else
  define('PRODUCTION', true );

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');

$app     = new Springboard\Application\Cli( BASE_PATH, false );
$migrate = new Springboard\DBMigrate( $app->bootstrap );

if ( isset( $argv[1] ) and $argv[1] == 'initdb' ) {
  
  if ( !$migrate->isDBEmpty() )
    throw new Exception("Database is not empty, refusing to init!");
  
  $migrate->loadSchema();
  $migrate->migrate();
  exit(0);
  
} else {

  try {
    $currentversion = $migrate->getCurrentVersion();
  } catch(Exception $e) {
    
    echo "Refusing to migrate, there is no database version! Original exception is shown below:\n\n";
    throw $e;
    
  }
  
  $migrate->migrate();
  exit(0);
  
}
