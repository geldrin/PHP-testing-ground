<?php
define('BASE_PATH', realpath( __DIR__ . '/../../') . '/' );

function setupAutoloader() {
  
  include_once( BASE_PATH . 'libraries/Springboard/Autoloader.php');
  $bootstrap = new stdClass();
  $bootstrap->config = array(
    'libpath'    => BASE_PATH . 'libraries/',
    'modulepath' => BASE_PATH . 'modules/',
    'modelpath'  => BASE_PATH . 'models/',
  );
  
  $autoloader = Springboard\Autoloader::getInstance( $bootstrap );
  $autoloader->register();
  
}
