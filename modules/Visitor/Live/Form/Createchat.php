<?php
namespace Visitor\Live\Form;

class Createchat extends \Visitor\HelpForm {
  public $configfile = 'Createchat.php';
  public $template   = 'Visitor/genericform.tpl';
  public $user;
  public $anonuser;
  public $feedModel;
  
  public function init() {
    
    $this->user      = $this->bootstrap->getSession('user');
    $this->feedModel = $this->controller->modelIDCheck(
      'livefeeds',
      $this->application->getNumericParameter('id')
    );
    
    if ( !$this->user['id'] and !$this->feedModel->row['anonymousallowed'] )
      $this->jsonOutput( array('status' => 'error', 'error' => 'registrationrestricted' ) );
    elseif( $this->user['id'] ) {

      $access = $this->feedModel->isAccessible( $this->user );
      if ( $access !== true )
        $this->jsonOutput( array('status' => 'error', 'error' => $access ) );

    } elseif( !$this->user['id'] ) {
      
      $this->anonuser = $this->bootstrap->getSession('anonuser');
      if ( $this->anonuser['id'] )
        $this->feedModel->refreshAnonUserID();

      $this->controller->toSmarty['anonuser'] = $this->anonuser;

    }

    if ( $this->feedModel->row['moderationtype'] == 'nochat' )
      $this->jsonOutput( array('status' => 'error', 'error' => 'nochat' ) );
    
    parent::init();
    
  }
  
  public function onComplete() {
    
    $values = $this->form->getElementValues( 0 );
    
    if ( $this->feedModel->row['moderationtype'] == 'premoderation' )
      $values['moderated'] = -1;
    else
      $values['moderated'] = 0;
    
    if ( $this->user['id'] )
      $values['userid']     = $this->user['id'];
    elseif ( !$this->anonuser['id'] ) {

      $challenge = @$_REQUEST['recaptcha_challenge_field'];
      $response  = @$_REQUEST['recaptcha_response_field'];
      if ( !$challenge or !$response )
        $this->jsonOutput( array('status' => 'error', 'error' => 'captcharequired' ) );

      include_once( $this->bootstrap->config['libpath'] . 'recaptchalib/recaptchalib.php' );
      $answer = \recaptcha_check_answer(
        $this->bootstrap->config['recaptchapriv'],
        $_SERVER['REMOTE_ADDR'],
        $challenge,
        $response
      );

      if ( $answer->is_valid ) {
        $this->anonuser['id']        = $this->feedModel->getAnonUserID();
        $this->anonuser['timestamp'] = date('Y-m-d H:i:s');
      } else
        $this->jsonOutput( array('status' => 'error', 'error' => 'captchaerror', 'errormessage' => $answer->error ) );

    }

    if ( $this->anonuser['id'] )
      $values['anonymoususer'] =
        $this->anonuser['id'] . '_' . $this->anonuser['timestamp']
      ;

    $values['timestamp']  = date('Y-m-d H:i:s');
    $values['livefeedid'] = $this->feedModel->id;
    $values['ipaddress']  = $_SERVER['REMOTE_ADDR'];
    
    $chatModel = $this->bootstrap->getModel('livefeed_chat');
    $chatModel->insert( $values );
    
    $this->controller->expireChatCache( $values['livefeedid'] );
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
