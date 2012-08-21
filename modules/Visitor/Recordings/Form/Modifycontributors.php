<?php
namespace Visitor\Recordings\Form;

class Modifycontributors extends \Visitor\Recordings\ModifyForm {
  public $configfile   = 'Modifycontributors.php';
  public $template     = 'Visitor/Recordings/Modifycontributors.tpl';
  
  public function init() {
    
    parent::init();
    $this->controller->toSmarty['contributors'] = $this->contributors =
      $this->recordingsModel->getContributorsWithRoles()
    ;
    
  }
  
  public function onComplete() {
    
    $values = $this->form->getElementValues( 0 );
    
    $this->recordingsModel->updateRow( $values );
    $this->recordingsModel->updateFulltextCache( true );
    
    $this->controller->redirect(
      'recordings/modifysharing/' . $this->recordingsModel->id,
      array( 'forward' => $values['forward'] )
    );
    
  }
  
}
