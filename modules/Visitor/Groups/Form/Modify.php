<?php
namespace Visitor\Groups\Form;

class Modify extends \Visitor\Form {
  public $configfile = 'Modify.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  
  public function init() {
    $this->groupModel = $this->controller->modelIDCheck(
      'groups',
      $this->application->getNumericParameter('id')
    );
    $this->values     = $this->groupModel->row;
  }
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title'] = $l('groups', 'create_title');
    
  }
  
  public function onComplete() {
    
    $values = $this->form->getElementValues( 0 );
    $this->groupModel->updateRow( $values );
    
    $this->controller->redirect(
      $this->application->getParameter('forward', 'groups' )
    );
    
  }
  
}
