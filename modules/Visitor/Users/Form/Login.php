<?php
namespace Visitor\Users\Form;
class Login extends \Visitor\Form {
  public $configfile = 'Login.php';
  public $template = 'Visitor/genericform.tpl';
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title'] = $l('users', 'login_title');
    
  }
  
  public function onComplete() {
    
    $crypto         = $this->bootstrap->getEncryption();
    $values         = $this->form->getElementValues( 0 );
    $userModel      = $this->bootstrap->getModel('users');
    $organizationid = $this->controller->organization['id'];
    $access         = $this->bootstrap->getSession('recordingaccess');
    
    $uservalid = $userModel->selectAndCheckUserValid( $organizationid, $values['email'], $values['password'] );
    $orgvalid  = false;
    
    if ( $uservalid and ( $userModel->row['organizationid'] == $organizationid or $userModel->row['isadmin'] ) )
      $orgvalid = true;
    
    if ( $userModel->row['isadmin'] )
      $userModel->row['organizationid'] = $organizationid; // a registerforsession miatt
    
    if ( !$uservalid or !$orgvalid ) {
      
      $l            = $this->bootstrap->getLocalization();
      $lang         = \Springboard\Language::get();
      $encodedemail = rawurlencode( $values['email'] );
      $message      = sprintf(
        $l('users','login_error'),
        $lang . '/users/forgotpassword?email=' . $encodedemail,
        $lang . '/users/resend?email=' . $encodedemail
      );
      $this->form->addMessage( $message );
      $this->form->invalidate();
      return;
      
    }
    
    $access->clear();
    $userModel->registerForSession();
    $this->controller->toSmarty['member'] = $userModel->row;
    
    $diagnostics = '(diag information was not posted)';
    if ( $this->application->getParameter('diaginfo') )
      $diagnostics = $this->application->getParameter('diaginfo');
    
    $userModel->updateLastlogin( $diagnostics );
    $forward = $this->application->getParameter('forward');
    
    if ( $this->application->getNumericParameter('force') and $forward )
      $this->controller->redirect( $forward );
    else
      $this->controller->redirect('users/welcome', array( 'forward' => $forward ) );
    
  }
  
}
