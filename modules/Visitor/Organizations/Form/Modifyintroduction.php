<?php
namespace Visitor\Organizations\Form;

class Modifyintroduction extends \Visitor\Form {
  public $configfile = 'Modifyintroduction.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  public $organizationModel;
  
  public function init() {
    
    $this->organizationModel =
      $this->controller->modelIDCheck(
        'organizations',
        $this->controller->organization['id']
      )
    ;
    $this->values = $this->organizationModel->row;
    parent::init();
    
  }
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title'] = $l('organizations', 'modifyintroduction_title');
    
  }
  
  public function onComplete() {
    
    $values = $this->form->getElementValues( 0 );
    $this->organizationModel->updateRow( $values );
    
    foreach( $this->bootstrap->config['languages'] as $language ) {
      
      $this->bootstrap->getCache(
        $language . '-organizations-' . $this->controller->organization['domain'],
        null,
        true
      )->expire();
      
    }
    
    $this->controller->redirect(
      $this->application->getParameter('forward', 'index' )
    );
    
  }
  
}
