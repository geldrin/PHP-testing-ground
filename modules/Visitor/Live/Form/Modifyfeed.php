<?php
namespace Visitor\Live\Form;

class Modify extends \Visitor\HelpForm {
  public $configfile = 'Modify.php';
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
    
  }
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title'] = $l('live', 'modifyfeed_title');
    
  }
  
  public function onComplete() {
    
    $values = $this->form->getElementValues( 0 );
    
    $this->feedModel->updateRow( $values );
    
    $this->controller->redirect(
      $this->application->getParameter(
        'forward',
        'live/managefeeds?event' . $this->channelModel->id
      )
    );
    
  }
  
}
