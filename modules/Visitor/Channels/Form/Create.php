<?php
namespace Visitor\Channels\Form;

class Create extends \Visitor\HelpForm {
  public $configfile = 'Create.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  
  public $parentchannelModel;
  public $channelroot;
  
  public function init() {
    
    $parentid = $this->application->getNumericParameter('parent');
    
    if ( $parentid ) {
      
      $this->parentchannelModel = $this->controller->modelOrganizationAndUserIDCheck(
        'channels',
        $parentid
      );
      
      $this->values['ispublic'] = $this->parentchannelModel->row['ispublic'];
      
    }
    
    $this->controller->toSmarty['formclass'] = 'leftdoublebox';
    parent::init();
    
  }
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title'] = $l('channels', 'create_title');
    
  }
  
  public function onComplete() {
    
    $values       = $this->form->getElementValues( 0 );
    $channelModel = $this->bootstrap->getModel('channels');
    $user         = $this->bootstrap->getSession('user');
    
    $values['userid']         = $user['id'];
    $values['organizationid'] = $user['organizationid'];
    
    if ( $this->parentchannelModel ) {
      
      $values['parentid']     = $this->parentchannelModel->id;
      
      if ( !$this->parentchannelModel->row['ispublic'] )
        $values['ispublic'] = $this->parentchannelModel->row['ispublic'];
      
    }
    
    $channelModel->insert( $values );
    $channelModel->updateIndexFilename();
    
    $this->controller->redirect(
      $this->application->getParameter('forward', 'channels/mychannels')
    );
    
  }
  
}
