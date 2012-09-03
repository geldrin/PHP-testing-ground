<?php
namespace Visitor\Departments\Form;
class Create extends \Visitor\Form {
  public $configfile = 'Create.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title'] = $l('departments', 'create_title');
    
  }
  
  public function onComplete() {
    
    $values        = $this->form->getElementValues( 0 );
    $departmentModel = $this->bootstrap->getModel('departments');
    
    $departmentModel->insert( $values );
    
    $this->controller->redirect(
      $this->application->getParameter('forward', 'departments/admin' )
    );
    
  }
  
}
