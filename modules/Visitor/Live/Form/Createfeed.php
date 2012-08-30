<?php
namespace Visitor\Live\Form;

class Createfeed extends \Visitor\HelpForm {
  public $configfile = 'Createfeed.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  
  protected $channelModel;
  
  public function init() {
    
    $this->channelModel = $this->controller->modelOrganizationAndUserIDCheck(
      'channels',
      $this->application->getNumericParameter('id')
    );
    
    $this->controller->toSmarty['formclass'] = 'leftdoublebox';
    parent::init();
    
  }
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title'] = $l('live', 'createfeed_title');
    
  }
  
  public function onComplete() {
    
    $values    = $this->form->getElementValues( 0 );
    $user      = $this->bootstrap->getSession('user');
    $feedModel = $this->bootstrap->getModel('livefeeds');
    
    $values['channelid']      = $this->channelModel->id;
    $values['userid']         = $user['id'];
    $values['organizationid'] = $this->controller->organization['id'];
    unset( $values['id'] );
    
    $feedModel->insert( $values );
    
    switch( $values['accesstype'] ) {
      
      case 'public':
      case 'registrations':
        break;
      
      case 'organizations':
        
        if ( !empty( $values['organizations'] ) )
          $feedModel->restrictOrganizations( $values['organizations'] );
        
        break;
      
      case 'groups':
        
        if ( !empty( $values['groups'] ) )
          $feedModel->restrictGroups( $values['groups'] );
        
        break;
      
      default:
        throw new \Exception('Unhandled accesstype');
        break;
      
    }
    
    $this->controller->redirect(
      $this->application->getParameter(
        'forward',
        'live/createstream/' . $feedModel->id
      )
    );
    
  }
  
}
