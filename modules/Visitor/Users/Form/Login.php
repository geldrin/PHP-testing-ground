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
    
    $crypto       = $this->bootstrap->getEncryption();
    $values       = $this->form->getElementValues( 0 );
    $smarty       = $this->bootstrap->getSmarty();
    $userModel    = $this->bootstrap->getModel('users');
    $organization = $this->bootstrap->getOrganization();
    
    $uservalid = $userModel->selectAndCheckUserValid( $values['email'], $values['password'] );
    $orgvalid  = false;
    
    if (
         $uservalid and
         (
           $userModel->row['organizationid'] == $organization->id or
           in_array( $userModel->row['organizationid'], $organization->children )
         )
       )
      $orgvalid = true;
    
    if ( !$uservalid or !$orgvalid ) {
      
      $l = $this->bootstrap->getLocalization();
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
    $forward = $this->application->getParameter('forward');
    
    if ( $forward )
      $this->controller->redirect( $forward );
    
    $this->controller->redirect('index');
    
  }
  
}
