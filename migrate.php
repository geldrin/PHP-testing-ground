#!/usr/bin/env php
<?php
define('BASE_PATH',  realpath( dirname( __FILE__ ) ) . '/' );
set_time_limit(0);

if ( isset( $_SERVER['APPLICATION_ENV'] ) and $_SERVER['APPLICATION_ENV'] == 'developer' )
  define('PRODUCTION', false );
else
  define('PRODUCTION', true );

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');

$app     = new Springboard\Application\Cli( BASE_PATH, PRODUCTION );
$migrate = new Springboard\DBMigrate( $app->bootstrap );

if ( isset( $argv[1] ) and $argv[1] == 'initdb' ) {
  
  if ( !$migrate->isDBEmpty() )
    throw new Exception("Database is not empty, refusing to init!");
  
  $migrate->loadSchema();
  $migrate->migrate();
  exit(EXIT_SUCCESS);
  
} else {

  $currentversion = $migrate->getCurrentVersion();
  if ( !$currentversion )
    throw new Exception("Refusing to migrate, there is no database version!");

  $migrate->migrate();
  exit(EXIT_SUCCESS);
  
}
