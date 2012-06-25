<?php
namespace Visitor\Live\Form;

class Createfeed extends \Visitor\HelpForm {
  public $configfile = 'Createfeed.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  
  protected $channelModel;
  
  public function init() {
    
    $channelid = $this->application->getNumericParameter('event');
    $this->channelModel = $this->controller->modelOrganizationAndUserIDCheck(
      'channels',
      $channelid
    );
    
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
    
    $feedModel->insert( $values );
    
    $this->controller->redirect(
      $this->application->getParameter(
        'forward',
        'live/createstream?feed=' . $feedModel->id
      )
    );
    
  }
  
}
