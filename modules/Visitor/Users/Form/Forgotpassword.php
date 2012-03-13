<?php
namespace Visitor\Users\Form;
class Forgotpassword extends \Visitor\Form {
  public $configfile = 'Forgotpassword.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  
  public function init() {
    
    $user = $this->bootstrap->getUser();
    
    if ( isset( $user->id ) )
      $this->controller->redirect('index');
    
  }
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    $this->toSmarty['title'] = $l('users', 'forgotpassword_title');
    
  }
  
  public function onComplete() {
    
    $values    = $this->form->getElementValues( 0 );
    $userModel = $this->bootstrap->getModel('users');
    $smarty    = $this->bootstrap->getSmarty();
    $crypto    = $this->bootstrap->getEncryption();
    $code      = $crypto->randomPassword( 10 );
    $l         = $this->bootstrap->getLocalization();
    $queue     = $this->bootstrap->getMailqueue();
    
    if ( !$userModel->checkEmailAndUpdateValidationCode( $values['email'], $code ) ) {
      
      $this->form->addMessage( $l('users', 'forgotpassword_error') );
      $this->form->invalidate();
      return;
      
    }
    
    $userModel->row['id'] = $crypto->asciiEncrypt( $userModel->row['id'] );
    $smarty->assign('values', $userModel->row );
    
    $queue->sendHTMLEmail(
      $userModel->row['email'],
      $l('users', 'forgotpass_emailsubject'),
      $smarty->fetch('Visitor/Users/Email/Forgotpassword.tpl')
    );
    
    $this->controller->redirect('contents/passwordreminder');
    
  }
  
}
