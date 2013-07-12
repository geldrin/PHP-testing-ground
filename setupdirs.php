#!/usr/bin/env php
<?php
define('BASE_PATH',  realpath( dirname( __FILE__ ) ) . '/' );
error_reporting(E_ALL);
ini_set('display_errors', true);

$production = null;
if ( $argc == 2 )
  $production = (bool)$argv[1];

include_once( BASE_PATH . 'libraries/Springboard/Setupdirs.php');
$setup = new \Springboard\Setupdirs( BASE_PATH, $production );
$setup->setup();
