<?php
namespace Visitor\Organizations\Form;
class Createnews extends \Visitor\Form {
  public $configfile = 'Createnews.php';
  public $template   = 'Visitor/genericform.tpl';
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title'] = $l('organizations', 'createnews_title');
    $this->controller->toSmarty['formclass'] = 'leftdoublebox';
    
  }
  
  public function onComplete() {
    
    $values    = $this->form->getElementValues( 0 );
    $newsModel = $this->bootstrap->getModel('organizations_news');
    
    $values['timestamp']      = date('Y-m-d H:i:s');
    $values['organizationid'] = $this->controller->organization['id'];
    
    $newsModel->insert( $values );
    
    $this->controller->redirect(
      $this->application->getParameter('forward', 'organizations/index' )
    );
    
  }
  
}
