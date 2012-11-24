<?php
namespace Visitor\Recordings\Form;

class Modifybasics extends \Visitor\Recordings\ModifyForm {
  public $configfile   = 'Modifybasics.php';
  public $template     = 'Visitor/genericform.tpl';
  
  public function init() {
    $this->controller->toSmarty['formclass'] = 'leftdoublebox';
    parent::init();
  }
  
  public function onComplete() {
    
    $values = $this->form->getElementValues( 0 );
    
    $this->recordingsModel->updateRow( $values );
    $this->recordingsModel->updateFulltextCache( true );
    
    if ( $this->recordingsModel->row['isintrooutro'] )
      $this->controller->redirect(
        $this->application->getParameter('forward', 'recordings/myrecordings' )
      );
    else
      $this->controller->redirect(
        'recordings/modifyclassification/' . $this->recordingsModel->id,
        array( 'forward' => $values['forward'] )
      );
    
  }
  
}
