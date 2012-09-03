<?php
namespace Visitor\Live\Form;

class Modifyfeed extends \Visitor\HelpForm {
  public $configfile = 'Modifyfeed.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  
  protected $channelModel;
  protected $feedModel;
  
  public function init() {
    
    $this->feedModel    = $this->controller->modelIDCheck(
      'livefeeds',
      $this->application->getNumericParameter('id')
    );
    
    $this->channelModel = $this->controller->modelOrganizationAndUserIDCheck(
      'channels',
      $this->feedModel->row['channelid']
    );
    
    if ( !$this->channelModel->row['isliveevent'] )
      $this->controller->redirect();
    
    $this->values = $this->feedModel->row;
    
    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title']     = $l('live', 'modifyfeed_title');
    $this->controller->toSmarty['formclass'] = 'leftdoublebox';
    
    parent::init();
    
  }
  
  public function onComplete() {
    
    $values = $this->form->getElementValues( 0 );
    
    $this->feedModel->clearAccess();
    
    switch( $values['accesstype'] ) {
      
      case 'public':
      case 'registrations':
        break;
      
      case 'departments':
        
        if ( !empty( $values['departments'] ) )
          $this->feedModel->restrictDepartments( $values['departments'] );
        
        break;
      
      case 'groups':
        
        if ( !empty( $values['groups'] ) )
          $this->feedModel->restrictGroups( $values['groups'] );
        
        break;
      
      default:
        throw new \Exception('Unhandled accesstype');
        break;
      
    }
    
    unset( $values['departments'], $values['groups'] );
    
    $this->feedModel->updateRow( $values );
    
    $this->controller->redirect(
      $this->application->getParameter(
        'forward',
        'live/managefeeds/' . $this->channelModel->id
      )
    );
    
  }
  
}
