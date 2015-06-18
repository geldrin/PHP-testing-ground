<?php
namespace Visitor\Debug;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'index'          => 'public',
    'browser'        => 'public',
  );
  
  public function browserAction() {
    $info = $this->bootstrap->getSession('browser');
    $info['mobile'] = (bool)$this->application->getParameter('mobile');
    $info['obsolete'] = false;
    $info['mobiledevice'] = $this->application->getParameter('device');
    $info['tablet'] = (bool)$this->application->getParameter('tablet');
  }
  
  public function indexAction() {
    die("");
  }
}
