<?php
namespace Visitor\Channels\Form;

class Modify extends \Visitor\HelpForm {
  public $configfile = 'Modify.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  
  protected $parentchannelModel;
  protected $channelModel;
  
  public function init() {
    
    $this->channelModel = $this->controller->modelOrganizationAndUserIDCheck(
      'channels',
      $this->application->getNumericParameter('id')
    );
    
    $this->values = $this->channelModel->row;
    
    if ( $this->channelModel->row['parentid'] ) {
      
      $this->parentchannelModel = $this->controller->modelOrganizationAndUserIDCheck(
        'channels',
        $this->channelModel->row['parentid']
      );
      
      $this->values['ispublic'] = $this->parentchannelModel->row['ispublic'];
      
    }
    
    $this->controller->toSmarty['formclass'] = 'leftdoublebox';
    
  }
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title'] = $l('channels', 'modify_title');
    
  }
  
  public function onComplete() {
    
    $values = $this->form->getElementValues( 0 );
    
    $this->channelModel->clearAccess();
    
    switch( $values['accesstype'] ) {
      
      case 'public':
      case 'registrations':
        break;
      
      case 'departments':
        
        if ( !empty( $values['departments'] ) )
          $this->channelModel->restrictDepartments( $values['departments'] );
        
        break;
      
      case 'groups':
        
        if ( !empty( $values['groups'] ) )
          $this->channelModel->restrictGroups( $values['groups'] );
        
        break;
      
      default:
        throw new \Exception('Unhandled accesstype');
        break;
      
    }
    
    unset( $values['departments'], $values['groups'] );
    
    $this->channelModel->updateRow( $values );
    
    $this->controller->redirect(
      $this->application->getParameter('forward', 'channels/mychannels')
    );
    
  }
  
}
