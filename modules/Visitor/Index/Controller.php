<?php
namespace Visitor\Index;

class Controller extends \Springboard\Controller\Visitor {
  public $permissions = array(
    'index'  => 'public',
  );
  
  public function indexAction() {
    
    $smarty = $this->bootstrap->getSmarty();
    $this->output( $smarty->fetch('Visitor/Index/index.tpl') );
    
  }
  
}
