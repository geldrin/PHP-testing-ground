<?php
namespace Visitor\Users\Form;
class Login extends \Visitor\Form {
  public $configfile = 'Login.php';
  public $template = 'Visitor/Users/Login.tpl';
  public $xsrfprotect = false; // hogy mukodjon a fooldali gyors belepes
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title'] = $l('users', 'login_title');
    if ( $this->application->getParameter('nolayout') )
      $this->controller->toSmarty['nolayout'] = true;
    
    parent::postSetupForm();
    
  }
  
  public function onComplete() {
    
    $crypto         = $this->bootstrap->getEncryption();
    $values         = $this->form->getElementValues( 0 );
    $userModel      = $this->bootstrap->getModel('users');
    $organizationid = $this->controller->organization['id'];
    $access         = $this->bootstrap->getSession('recordingaccess');
    
    $uservalid = $userModel->selectAndCheckUserValid( $organizationid, $values['email'], $values['password'] );
    
    // single login location check 
    $sessionvalid = $uservalid === true && $userModel->checkSingleLoginUsers();
    
    if ( $uservalid !== true or !$sessionvalid ) {

      $l            = $this->bootstrap->getLocalization();
      $lang         = \Springboard\Language::get();
      $encodedemail = rawurlencode( $values['email'] );
      
      if ( !$uservalid or $uservalid == 'organizationinvalid' )
        $message = sprintf(
          $l('users','login_error'),
          $lang . '/users/forgotpassword?email=' . $encodedemail,
          $lang . '/users/resend?email=' . $encodedemail
        );
      elseif ( $uservalid == 'expired' )
        $message = $l('users', 'timestampdisabled');
      elseif ( !$sessionvalid )
        $message = sprintf(
          $l('users','login_sessionerror'),
          ceil( $this->bootstrap->config['sessiontimeout'] / 60 ),
          $lang . '/users/resetsession?email=' . $encodedemail
        );

      $this->form->addMessage( $message );
      $this->form->invalidate();
      return;
      
    }
    
    $access->clear();
    $userModel->registerForSession();
    $userModel->updateSessionInformation();
    $this->controller->toSmarty['member'] = $userModel->row;
    
    $diagnostics = '(diag information was not posted)';
    if ( $this->application->getParameter('diaginfo') )
      $diagnostics = $this->application->getParameter('diaginfo');
    
    $userModel->updateLastlogin( $diagnostics, $this->controller->getIPAddress(true) );
    $this->controller->logUserLogin('LOGIN');
    $forward = $this->application->getParameter('forward');

    if ( $values['rememberme'] )
      $userModel->setRemembermeCookie( $this->bootstrap->ssl );

    if ( strpos( $forward, 'users/login' ) !== false ) {
      $forward = '';
      $values['welcome'] = true;
    }
    
    if ( $values['welcome'] )
      $this->controller->redirect('users/welcome', array( 'forward' => $forward ) );
    else
      $this->controller->redirect( $forward );
    
  }
  
}
