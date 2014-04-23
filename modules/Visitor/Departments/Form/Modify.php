<?php
namespace Visitor\Departments\Form;

class Modify extends \Visitor\HelpForm {
  public $configfile = 'Modify.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  
  public function init() {
    $this->departmentModel = $this->controller->modelIDCheck(
      'departments',
      $this->application->getNumericParameter('id')
    );
    $this->values        = $this->departmentModel->row;
    parent::init();
  }
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title'] = $l('departments', 'create_title');
    
  }
  
  public function onComplete() {
    
    $values = $this->form->getElementValues( 0 );
    $this->departmentModel->updateRow( $values );
    
    $this->controller->redirect(
      $this->application->getParameter('forward', 'departments/admin' )
    );
    
  }
  
}
