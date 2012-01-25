<?php
namespace Visitor\Users;

class Controller extends \Springboard\Controller\Visitor {
  public $permissions = array(
    'login'  => 'public',
    'signup' => 'public',
    'modify' => 'member',
    'index'  => 'public',
  );
  
  public $forms = array(
    'login' => 'Visitor\\Users\\Form\\Login',
  );
  
  public function indexAction() {
    echo 'Nothing here yet';
  }
  
}
