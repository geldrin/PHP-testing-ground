<?php
namespace Visitor\Live\Form;

class Modifystream extends \Visitor\HelpForm {
  public $configfile = 'Modifystream.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  
  protected $channelModel;
  protected $feedModel;
  protected $streamModel;
  
  public function init() {
    
    $this->streamModel   = $this->controller->modelIDCheck(
      'livefeed_streams',
      $this->application->getNumericParameter('id')
    );
    
    $this->feedModel    = $this->controller->modelIDCheck(
      'livefeeds',
      $this->streamModel->row['livefeedid']
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
    $this->controller->toSmarty['title'] = $l('live', 'modifystream_title');
    
  }
  
  public function onComplete() {
    
    $values = $this->form->getElementValues( 0 );
    
    if ( !$this->streamModel->row['keycode'] )
      $values['keycode'] = $this->streamModel->generateUniqueKeycode();
    
    if (
         $this->feedModel->row['numberofstreams'] == 2 and
         !$this->streamModel->row['contentkeycode']
       )
      $values['contentkeycode'] =
        $this->streamModel->generateUniqueKeycode()
      ;
    
    $this->streamModel->updateRow( $values );
    
    $this->controller->redirect(
      $this->application->getParameter(
        'forward',
        'live/managefeeds/' . $this->channelModel->id
      )
    );
    
  }
  
}
