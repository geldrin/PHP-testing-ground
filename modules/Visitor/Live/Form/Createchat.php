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

      $access = $this->feedModel->isAccessible( $this->user, $this->controller->organization );
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
    $ret    = array();

    if ( $this->feedModel->row['moderationtype'] == 'premoderation' ) {
      $values['moderated'] = -1;

      if ( !$this->controller->acl ) {
        $this->controller->acl = $this->bootstrap->getAcl();
        $this->controller->acl->usersessionkey = $this->controller->usersessionkey;
      }

      // ha nem admin akkor mutatjuk az alertet
      if ( !$this->controller->acl->hasPermission('liveadmin|clientadmin') )
        $ret['moderationalert'] = true;

    } else
      $values['moderated'] = 0;
    
    if ( $this->user['id'] )
      $values['userid']     = $this->user['id'];
    elseif ( !$this->anonuser['id'] ) {

      if ( $this->bootstrap->config['recaptchaenabled'] ) {

        if ( !$values['recaptcharesponse'] )
          $this->jsonOutput( array('status' => 'error', 'error' => 'captcharequired' ) );

        $recaptcha = new \ReCaptcha\ReCaptcha(
          $this->bootstrap->config['recaptchapriv']
        );
        $resp = $recaptcha->verify(
          $values['recaptcharesponse'],
          $this->controller->getIPAddress()
        );

        if ( !$resp->isSuccess() )
          $this->jsonOutput( array(
              'status'     => 'error',
              'error'      => 'captchaerror',
              'errorcodes' => $resp->getErrorCodes(),
            )
          );

      }

      $this->anonuser['id']        = $this->feedModel->getAnonUserID();
      $this->anonuser['timestamp'] = date('Y-m-d H:i:s');

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
    return $this->controller->getchatAction( $values['livefeedid'], $ret );
    
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
