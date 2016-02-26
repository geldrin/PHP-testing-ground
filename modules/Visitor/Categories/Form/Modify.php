<?php
namespace Visitor\Categories\Form;

class Modify extends \Visitor\HelpForm {
  public $configfile = 'Modify.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  
  public function init() {
    $this->categoryModel = $this->controller->modelIDCheck(
      'categories',
      $this->application->getNumericParameter('id')
    );
    $this->values        = $this->categoryModel->row;
    parent::init();
  }
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title'] = $l('categories', 'modify_title');
    $this->controller->toSmarty['helpclass'] = 'fullwidth left';
    
  }
  
  public function onComplete() {
    
    $values = $this->form->getElementValues( 0 );
    $this->categoryModel->updateRow( $values );
    $this->categoryModel->expireCategoryTreeCache(
      $this->controller->organization['id']
    );
    
    $this->controller->redirect(
      $this->application->getParameter('forward', 'categories/admin' )
    );
    
  }
  
}
