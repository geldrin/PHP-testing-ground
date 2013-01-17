<?php
namespace Visitor\Contributors\Form;
class Create extends \Visitor\Form {
  public $configfile = 'Create.php';
  public $template   = 'Visitor/genericform.tpl';
  
  public function init() {
    
    parent::init();
    
  }
  
  public function onComplete() {
    
    $values = $this->form->getElementValues( 0 );
    $l      = $this->bootstrap->getLocalization();
    $user   = $this->bootstrap->getSession('user');
    
    $values['timestamp'] = date('Y-m-d H:i:s');
    $values['userid']    = $user['id'];
    
    $output = array(
    );
    
    $this->controller->jsonoutput( $output );
    
  }
  
}
