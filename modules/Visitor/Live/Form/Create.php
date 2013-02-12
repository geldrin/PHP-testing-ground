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
      $values['starttimestamp'] .= ' 00:00:00';
    
    if ( @$values['endtimestamp'] )
      $values['endtimestamp'] .= ' 23:59:59';
    
    if ( $this->parentchannelModel )
      $values['parentid']     = $this->parentchannelModel->id;
    
    $channelModel->insert( $values );
    $channelModel->updateIndexFilename();
    
    $this->handleAccesstypeForModel( $channelModel, $values, false );
    
    if ( !$channelModel->getLiveFeedCountForChannel() )
      $url = 'live/createfeed/' . $channelModel->id;
    else
      $url = 'live/details/' . $channelModel->id;
    
    $this->controller->redirect( $url );
    
  }
  
}
