<?php
namespace Visitor\Live\Form;

class Createchat extends \Visitor\HelpForm {
  public $configfile = 'Createchat.php';
  public $template   = 'Visitor/genericform.tpl';
  public $user;
  public $feedModel;
  
  public function init() {
    
    $this->user      = $this->bootstrap->getSession('user');
    $this->feedModel = $this->controller->modelIDCheck(
      'livefeeds',
      $this->application->getNumericParameter('id')
    );
    
    $access = $this->feedModel->isAccessible( $this->user );
    if ( $access !== true )
      $this->jsonOutput( array('status' => 'error', 'error' => $access ) );
    
    parent::init();
    
  }
  
  public function onComplete() {
    
    $values = $this->form->getElementValues( 0 );
    
    $values['userid']     = $this->user['id'];
    $values['timestamp']  = date('Y-m-d H:i:s');
    $values['moderated']  = 0;
    $values['livefeedid'] = $this->feedModel->id;
    
    $chatModel = $this->bootstrap->getModel('livefeed_chat');
    $chatModel->insert( $values );
    
    $this->controller->getChatCache( $values['livefeedid'] )->expire();
    return $this->controller->getchatAction( $values['livefeedid'] );
    
  }
  
  public function displayForm( $submitted ) {
    
    if ( $submitted and !$this->form->validate() ) {
      
      $formvars = $this->form->getVars();
      
      $this->controller->jsonOutput( array(
          'status' => 'error',
          'error'  => reset( $formvars['messages'] ),
        )
      );
      
    }
    
    parent::displayForm( $submitted );
    
  }
  
}
