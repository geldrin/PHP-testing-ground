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
    
    $this->feedModel    = $this->controller->modelIDCheck(
      'livefeeds',
      $this->application->getNumericParameter('id')
    );
    
    $this->channelModel = $this->controller->modelOrganizationAndUserIDCheck(
      'channels',
      $this->feedModel->row['channelid']
    );
    
    $this->streamModel  = $this->bootstrap->getModel('livefeed_streams');
    parent::init();
    
  }
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title']          = $l('live', 'createstream_title');
    $this->controller->toSmarty['sessionmessage'] = $l('live', 'create_notice');
    $this->controller->toSmarty['formclass']      = 'leftdoublebox';
    
  }
  
  public function onComplete() {
    
    $values = $this->form->getElementValues( 0 );
    $l      = $this->bootstrap->getLocalization();
    
    $values['livefeedid']     = $this->feedModel->id;
    $values['timestamp']      = date('Y-m-d H:i:s');
    $values['keycode']        = $this->streamModel->generateUniqueKeycode();
    $values['contentkeycode'] = $this->streamModel->generateUniqueKeycode();
    
    if ( !isset( $values['compatibility'] ) or !is_array( $values['compatibility'] ) )
      $values['compatibility'] = array();
    
    foreach( $l->getLov('live_compatibility') as $key => $value ) {
      
      if ( in_array( $key, $values['compatibility'] ) )
        $values[ $key ] = 1;
      else
        $values[ $key ] = 0;
      
    }
    
    unset( $values['id'] );
    $this->streamModel->insert( $values );
    
    $this->controller->redirect(
      $this->application->getParameter(
        'forward',
        'live/managefeeds/' . $this->channelModel->id
      )
    );
    
  }
  
}
