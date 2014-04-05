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
      
      $this->channelroot = $this->parentchannelModel->findRoot( $this->parentchannelModel->row );
      $this->values['ispublic'] = $this->channelroot['ispublic'];
      
    }
    
    $this->controller->toSmarty['formclass'] = 'leftdoublebox';
    $this->controller->toSmarty['helpclass'] = 'rightbox small';
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
      
      $values['parentid'] = $this->parentchannelModel->id;
      $values['ispublic'] = $this->channelroot['ispublic'];
      
    }
    
    $channelModel->insert( $values );
    $channelModel->updateIndexFilename();
    $channelModel->updateModification();

    $this->controller->redirect(
      $this->application->getParameter('forward', 'channels/mychannels')
    );
    
  }
  
}
