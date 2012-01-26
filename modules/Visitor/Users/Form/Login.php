<?php
namespace Visitor\Users\Form;
class Login extends \Visitor\Form {
  public $configfile = 'Login.php';
  public $template = 'Visitor/Users/Login.tpl';
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocale();
    $this->toSmarty['title'] = $l('users', 'login_title');
    
  }
  
  public function onComplete() {
    
    $crypto    = $this->bootstrap->getCrypto();
    $values    = $this->form->getElementValues( 0 );
    $smarty    = $this->bootstrap->getSmarty();
    $userModel = $this->bootstrap->getModel('users');
    
    if ( !$userModel->selectAndCheckUserValid( $values['email'], $values['password'] ) ) {
      
      $l = $this->bootstrap->getLocale();
      $this->form->addMessage( sprintf( $l('users','login_error'), \Springboard\Language::get() . '/users/forgotpassword' ) );
      $this->form->invalidate();
      return;
      
    }
    
    $userModel->registerForSession();
    $this->toSmarty['member'] = $userModel->row;
    
    $diagnostics = '(diag information was not posted)';
    if ( $this->application->getParameter('diaginfo') )
      $diagnostics = $this->application->getParameter('diaginfo');
    
    $userModel->updateLastlogin( $diagnostics );
    $this->controller->redirectToFragment('');
    
  }
  
}
