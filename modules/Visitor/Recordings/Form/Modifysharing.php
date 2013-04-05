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
    
    if ( $this->values['visiblefrom'] )
      $this->values['visiblefrom']  = substr( $this->values['visiblefrom'], 0, 10 );
    else
      unset( $this->values['visiblefrom'] );
    
    if ( $this->values['visibleuntil'] )
      $this->values['visibleuntil'] = substr( $this->values['visibleuntil'], 0, 10 );
    else
      unset( $this->values['visibleuntil'] );
    
  }
  
  public function onComplete() {
    
    $values = $this->form->getElementValues( 0 );
    
    if ( !$values['wanttimelimit'] )
      $values['visibleuntil'] = $values['visiblefrom'] = null;
    
    $this->handleAccesstypeForModel( $this->recordingsModel, $values );
    
    unset( $values['departments'], $values['groups'] );
    $this->recordingsModel->updateRow( $values );
    $this->recordingsModel->updateFulltextCache( true );
    $this->recordingsModel->updateChannelIndexPhotos(); // a channel szamlalok miatt
    $this->recordingsModel->updateCategoryCounters();
    
    $this->controller->redirect(
      $this->application->getParameter('forward', 'recordings/myrecordings')
    );
    
  }
  
}
