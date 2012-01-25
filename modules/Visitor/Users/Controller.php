<?php
namespace Visitor\Users;

class Controller extends \Springboard\Controller\Visitor {
  protected $permissions = array(
    'login'  => 'public',
    'index'  => 'public',
  );
  protected $forms = array(
    'login' => 'Visitor\\Users\\Form\\Login',
  );
  protected $paging = array(
    'index' => 'Visitor\\Users\\Paging',
  );
  
}
