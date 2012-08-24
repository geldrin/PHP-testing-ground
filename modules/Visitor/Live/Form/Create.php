<?php
namespace Visitor\Live\Form;

class Create extends \Visitor\HelpForm {
  public $configfile = 'Create.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  
  protected $parentchannelModel;
  
  public function init() {
    
    $parentid = $this->application->getNumericParameter('parent');
    
    if ( $parentid ) {
      
      $this->parentchannelModel = $this->controller->modelOrganizationAndUserIDCheck(
        'channels',
        $parentid
      );
      
    }
    
    $this->controller->toSmarty['formclass'] = 'leftdoublebox';
    parent::init();
    
  }
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    if ( $this->parentchannelModel )
      $this->controller->toSmarty['title'] = $l('live', 'create_subchannel_title');
    else
      $this->controller->toSmarty['title'] = $l('live', 'create_title');
    
  }
  
  public function onComplete() {
    
    $values       = $this->form->getElementValues( 0 );
    $channelModel = $this->bootstrap->getModel('channels');
    $user         = $this->bootstrap->getSession('user');
    
    $values['userid']         = $user['id'];
    $values['organizationid'] = $user['organizationid'];
    
    if ( @$values['starttimestamp'] )
      $values['starttimestamp'] .= ' 08:00:00';
    
    if ( @$values['endtimestamp'] )
      $values['endtimestamp'] .= ' 20:00:00';
    
    if ( $this->parentchannelModel )
      $values['parentid']     = $this->parentchannelModel->id;
    
    $channelModel->insert( $values );
    $channelModel->updateIndexFilename();
    
    switch( $values['accesstype'] ) {
      
      case 'public':
      case 'registrations':
        // kiuritettuk mar elobb az `access`-t az adott recordinghoz
        // itt nincs tobb dolgunk
        break;
      
      case 'organizations':
        
        if ( !empty( $values['organizations'] ) )
          $channelModel->restrictOrganizations( $values['organizations'] );
        
        break;
      
      case 'groups':
        
        if ( !empty( $values['groups'] ) )
          $channelModel->restrictGroups( $values['groups'] );
        
        break;
      
      default:
        throw new \Exception('Unhandled accesstype');
        break;
      
    }
    
    if ( !$channelModel->getLiveFeedCountForChannel() )
      $url = 'live/createfeed/' . $channelModel->id;
    else
      $url = 'live/details/' . $channelModel->id;
    
    $this->controller->redirect( $url );
    
  }
  
}
