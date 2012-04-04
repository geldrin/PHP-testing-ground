<?php
namespace Visitor\Organizations\Form;

class Modifynews extends \Visitor\Form {
  public $configfile = 'Modifynews.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  public $newsModel;
  
  public function init() {
    
    $id              = $this->application->getNumericParameter('id');
    $user            = $this->bootstrap->getSession('user');
    
    if (
         !$user['iseditor'] or
         $this->controller->organization['id'] != $user['organizationid']
       )
      $this->controller->redirect('index');
    
    $this->newsModel = $this->controller->modelIDCheck('organizations_news', $id );
    $this->values    = $this->newsModel->row;
    parent::init();
    
  }
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title'] = $l('organizations', 'modifynews_title');
    $this->controller->toSmarty['formclass'] = 'leftdoublebox';
    
  }
  
  public function onComplete() {
    
    $values = $this->form->getElementValues( 0 );
    $this->newsModel->updateRow( $values );
    
    $this->controller->redirect(
      $this->application->getParameter('forward', 'organizations/listnews' )
    );
    
  }
  
}
