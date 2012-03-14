<?php
namespace Visitor\Recordings\Form;

class Modifysharing extends \Visitor\Recordings\ModifyForm {
  public $configfile   = 'Modifysharing.php';
  public $template     = 'Visitor/genericform.tpl';
  public $needdb       = true;
  
  public function onComplete() {
    
    $values = $this->form->getElementValues( 0 );
    
    if ( !$values['wanttimelimit'] )
      $values['visibleuntil'] = $values['visiblefrom'] = null;
    
    $this->recordingsModel->clearAccess();
    
    switch( $values['accesstype'] ) {
      
      case 'public':
      case 'registrations':
        // kiuritettuk mar elobb a recordings_access-t az adott recordinghoz
        // itt nincs tobb dolgunk
        break;
      
      case 'organizations':
        
        if ( !empty( $values['organizations'] ) )
          $this->recordingsModel->restrictOrganizations( $values['organizations'] );
        
        break;
      
      case 'groups':
        
        if ( !empty( $values['groups'] ) )
          $this->recordingsModel->restrictGroups( $values['groups'] );
        
        break;
      
      default:
        throw new \Exception('Unhandled accesstype');
        break;
      
    }
    
    $this->recordingsModel->updateRow( $values );
    $this->recordingsModel->updateFulltextCache( true );
    $this->recordingsModel->updateCategoryCounters();
    
    $this->controller->redirect(
      'recordings/myrecordings',
      array( 'forward' => $values['forward'] )
    );
    
  }
  
}
