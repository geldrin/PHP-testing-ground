<?php
namespace Visitor\Categories\Form;

class Modify extends \Visitor\Form {
  public $configfile = 'Modify.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  
  public function init() {
    $this->categoryModel = $this->controller->modelIDCheck(
      'categories',
      $this->application->getNumericParameter('id')
    );
    $this->values        = $this->categoryModel->row;
  }
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    $this->toSmarty['title'] = $l('categories', 'create_title');
    
  }
  
  public function onComplete() {
    
    $values = $this->form->getElementValues( 0 );
    $this->categoryModel->updateRow( $values );
    
    $this->redirect(
      $this->application->getParameter('forward', 'categories/index' )
    );
    
  }
  
}
