<?php
namespace Visitor\Live\Form;

class Createstream extends \Visitor\HelpForm {
  public $configfile = 'Createstream.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  
  protected $parentchannelModel;
  protected $feedModel;
  protected $streamModel;
  
  public function init() {
    
    $feedid = $this->application->getNumericParameter('feed');
    
    $this->feedModel    = $this->controller->modelIDCheck(
      'livefeeds',
      $feedid
    );
    
    $this->channelModel = $this->controller->modelOrganizationAndUserIDCheck(
      'channels',
      $this->feedModel->row['channelid']
    );
    
    // a configban hasznaljuk
    $this->streamModel = $this->bootstrap->getModel('livefeed_streams');
    parent::init();
    
  }
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title'] = $l('live', 'createstream_title');
    
  }
  
  public function onComplete() {
    
    $values = $this->form->getElementValues( 0 );
    
    $values['livefeedid'] = $this->feedModel->id;
    $values['timestamp']  = date('Y-m-d H:i:s');
    
    if ( !$this->feedModel->row['isexternal'] ) {
      
      $values['keycode'] = $this->streamModel->generateUniqueKeycode();
      
      if ( $this->feedModel->row['numberofstreams'] == 2 )
        $values['contentkeycode'] =
          $this->streamModel->generateUniqueKeycode()
        ;
      
    }
    
    $this->streamModel->insert( $values );
    
    $this->controller->redirect(
      $this->application->getParameter(
        'forward',
        'live/managefeeds?event=' . $this->channelModel->id
      )
    );
    
  }
  
}
