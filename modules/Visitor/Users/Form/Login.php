<?php
namespace Visitor\Users\Form;
class Login extends \Visitor\Form {
  public $configfile = 'Login.php';
  public $template = 'Visitor/genericform.tpl';
  public $xsrfprotect = false; // hogy mukodjon a fooldali gyors belepes
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title'] = $l('users', 'login_title');
    parent::postSetupForm();
    
  }
  
  public function onComplete() {
    
    $crypto         = $this->bootstrap->getEncryption();
    $values         = $this->form->getElementValues( 0 );
    $userModel      = $this->bootstrap->getModel('users');
    $organizationid = $this->controller->organization['id'];
    $access         = $this->bootstrap->getSession('recordingaccess');
    $d              = \Springboard\Debug::getInstance();
    
    $uservalid = $userModel->selectAndCheckUserValid( $organizationid, $values['email'], $values['password'] );
    $orgvalid  = $timestampvalid = false;
    
    if ( $uservalid and ( $userModel->row['organizationid'] == $organizationid or $userModel->row['isadmin'] ) )
      $orgvalid = true;
    
    if (
         ( $uservalid and !$userModel->row['timestampdisabledafter'] ) or
         (
           $uservalid and $userModel->row['timestampdisabledafter'] and
           strtotime( $userModel->row['timestampdisabledafter'] ) > time()
         )
       )
      $timestampvalid = true;
    
    if ( $userModel->row['isadmin'] )
      $userModel->row['organizationid'] = $organizationid; // a registerforsession miatt
      
    // single login location check 
    $sessionvalid = $uservalid && $userModel->checkSingleLoginUsers();
    
    if ( !$uservalid or !$orgvalid or !$sessionvalid or !$timestampvalid ) {

      $l            = $this->bootstrap->getLocalization();
      $lang         = \Springboard\Language::get();
      $encodedemail = rawurlencode( $values['email'] );
      
      switch ( false ) {
        case $uservalid:
        case $orgvalid:
          $message = sprintf(
            $l('users','login_error'),
            $lang . '/users/forgotpassword?email=' . $encodedemail,
            $lang . '/users/resend?email=' . $encodedemail
          );
          break;
        
        case $sessionvalid:
          $message = sprintf(
            $l('users','login_sessionerror'),
            ceil( $this->bootstrap->config['sessiontimeout'] / 60 ),
            $lang . '/users/resetsession?email=' . $encodedemail
          );
          break;
        
        case $timestampvalid:
          $message = $l('users', 'timestampdisabled');
          break;
        
        default: throw new \Exception('unhandled switch case'); break;
          
      }

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
    
    $ipaddress = $this->controller->getIPAddress();
    $userModel->updateLastlogin( $diagnostics, $ipaddress );
    $d->log(false, 'login.txt', 'LOGIN SESSIONID: ' . session_id() . ' IPADDRESS: ' . $ipaddress );
    $forward = $this->application->getParameter('forward');
    
    if ( $values['welcome'] )
      $this->controller->redirect('users/welcome', array( 'forward' => $forward ) );
    else
      $this->controller->redirect( $forward );
    
  }
  
}
