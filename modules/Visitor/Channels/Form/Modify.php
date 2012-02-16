<?php
namespace Visitor\Channels\Form;

class Modify extends \Visitor\HelpForm {
  public $configfile = 'Modify.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  
  protected $channelModel;
  
  public function init() {
    
    $this->channelModel = $this->controller->modelOrganizationAndUserIDCheck(
      'channels',
      $this->application->getNumericParameter('id')
    );
    
    $this->values = $this->channelModel->row;
    
  }
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    $this->toSmarty['title'] = $l('channels', 'modify_title');
    
  }
  
  public function onComplete() {
    
    $values = $this->form->getElementValues( 0 );
    $this->channelModel->updateRow( $values );
    
    $this->redirect(
      $this->application->getParameter('forward', 'channels/mychannels')
    );
    
  }
  
}
