#!/usr/bin/env php
<?php
define('BASE_PATH',  realpath( dirname( __FILE__ ) ) . '/' );
set_time_limit(0);

if ( isset( $_SERVER['APPLICATION_ENV'] ) and $_SERVER['APPLICATION_ENV'] == 'developer' )
  define('PRODUCTION', false );
else
  define('PRODUCTION', true );

include_once( BASE_PATH . 'libraries/Springboard/Application/Cli.php');

$app    = new Springboard\Application\Cli( BASE_PATH, false );
if ( count( $argv ) == 1 )
  echo "Please provide a string to be hashed!\n";

$crypt = $app->bootstrap->getEncryption();
echo "Hash for: ", var_export( $argv[1] ), " is: ", $crypt->getHash( $argv[1] ), "\n";
