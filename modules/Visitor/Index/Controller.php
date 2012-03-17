<?php
namespace Visitor\Index;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'index'  => 'public',
  );
  
  public function indexAction() {
    $this->smartyoutput('Visitor/Index/index.tpl');
  }
  
}
