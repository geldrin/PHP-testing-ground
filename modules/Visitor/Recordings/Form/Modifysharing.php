<?php
namespace Visitor\Recordings\Form;

class Modifysharing extends \Visitor\Recordings\ModifyForm {
  public $configfile   = 'Modifysharing.php';
  public $template     = 'Visitor/genericform.tpl';
  public $needdb       = true;
  
  public function init() {
    
    parent::init();
    
    if ( $this->recordingsModel->row['visiblefrom'] )
      $this->values['wanttimelimit'] = 1;
    
  }
  
  public function onComplete() {
    
    $values = $this->form->getElementValues( 0 );
    
    if ( !$values['wanttimelimit'] )
      $values['visibleuntil'] = $values['visiblefrom'] = null;
    
    $this->handleAccesstypeForModel( $this->recordingsModel, $values );
    
    unset( $values['departments'], $values['groups'] );
    $this->recordingsModel->updateRow( $values );
    $this->recordingsModel->updateFulltextCache( true );
    $this->recordingsModel->updateCategoryCounters();
    $this->recordingsModel->updateChannelIndexPhotos(); // a channel szamlalok miatt
    
    $this->controller->redirect(
      'recordings/myrecordings',
      array( 'forward' => $values['forward'] )
    );
    
  }
  
}
