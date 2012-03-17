<?php
namespace Visitor\Categories\Form;
class Create extends \Visitor\Form {
  public $configfile = 'Create.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title'] = $l('categories', 'create_title');
    
  }
  
  public function onComplete() {
    
    $values        = $this->form->getElementValues( 0 );
    $categoryModel = $this->bootstrap->getModel('categories');
    
    $categoryModel->insert( $values );
    
    $this->controller->redirect(
      $this->application->getParameter('forward', 'categories/index' )
    );
    
  }
  
}
