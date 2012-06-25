<?php
namespace Visitor\Recordings\Form;

class Modifydescription extends \Visitor\Recordings\ModifyForm {
  public $configfile   = 'Modifydescription.php';
  public $template     = 'Visitor/genericform.tpl';
  
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
