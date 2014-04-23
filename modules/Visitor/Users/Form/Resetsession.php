<?php
namespace Visitor\Users\Form;
class Resetsession extends \Visitor\HelpForm {
  public $configfile = 'Resetsession.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  
  public function init() {
    
    $user = $this->bootstrap->getSession('user');
    
    if ( isset( $user['id'] ) )
      $this->controller->redirect('index');
    
  }
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title'] = $l('users', 'resetsession_title');
    
  }
  
  public function onComplete() {
    
    $values    = $this->form->getElementValues( 0 );
    $userModel = $this->bootstrap->getModel('users');
    $crypto    = $this->bootstrap->getEncryption();
    $code      = $crypto->randomPassword( 10 );
    $l         = $this->bootstrap->getLocalization();
    
    if ( !$userModel->checkEmailAndUpdateValidationCode( $values['email'], $code ) ) {
      
      $this->form->addMessage( $l('users', 'forgotpassword_error') ); // nincs ilyen reg
      $this->form->invalidate();
      return;
      
    }

    if ( $userModel->row['isusergenerated'] ) {
      
      $this->form->addMessage( $l('users', 'forgotpassword_generror') );
      $this->form->invalidate();
      return;
      
    }
    
    $userModel->row['id'] = $crypto->asciiEncrypt( $userModel->row['id'] );
    $this->controller->toSmarty['values'] = $userModel->row;
    
    $this->controller->sendOrganizationHTMLEmail(
      $userModel->row['email'],
      $l('users', 'resetsession_emailsubject'),
      $this->controller->fetchSmarty('Visitor/Users/Email/Resetsession.tpl')
    );
    
    $this->controller->redirect('contents/validateresetsession');
    
  }
  
}
