<?php
namespace Visitor\Users\Form;
class Login extends \Visitor\Form {
  public $configfile = 'Login.php';
  public $template = 'Visitor/genericform.tpl';
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    $this->toSmarty['title'] = $l('users', 'login_title');
    
  }
  
  public function onComplete() {
    
    $crypto         = $this->bootstrap->getEncryption();
    $values         = $this->form->getElementValues( 0 );
    $smarty         = $this->bootstrap->getSmarty();
    $userModel      = $this->bootstrap->getModel('users');
    $organizationid = $this->controller->organization['id'];
    $access         = $this->bootstrap->getSession('recordingaccess');
    
    $uservalid = $userModel->selectAndCheckUserValid( $organizationid, $values['email'], $values['password'] );
    $orgvalid  = false;
    
    if ( $uservalid and $userModel->row['organizationid'] == $organizationid )
      $orgvalid = true;
    
    if ( !$uservalid or !$orgvalid ) {
      
      $l = $this->bootstrap->getLocalization();
      $this->form->addMessage( sprintf( $l('users','login_error'), \Springboard\Language::get() . '/users/forgotpassword' ) );
      $this->form->invalidate();
      return;
      
    }
    
    $access->clear();
    $userModel->registerForSession();
    $this->toSmarty['member'] = $userModel->row;
    
    $diagnostics = '(diag information was not posted)';
    if ( $this->application->getParameter('diaginfo') )
      $diagnostics = $this->application->getParameter('diaginfo');
    
    $userModel->updateLastlogin( $diagnostics );
    $forward = $this->application->getParameter('forward');
    
    $this->controller->redirect('users/welcome', array( 'forward' => $forward ) );
    
  }
  
}
