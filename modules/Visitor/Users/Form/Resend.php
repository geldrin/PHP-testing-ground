<?php
namespace Visitor\Users\Form;
class Resend extends \Visitor\HelpForm {
  public $configfile = 'Resend.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  
  public function init() {
    $this->controller->toSmarty['helpclass'] = 'rightbox halfbox';
    parent::init();
  }
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title'] = $l('users', 'resend_title');
    
  }
  
  public function onComplete() {
    
    $values    = $this->form->getElementValues( 0 );
    $userModel = $this->bootstrap->getModel('users');
    $crypto    = $this->bootstrap->getEncryption();
    $l         = $this->bootstrap->getLocalization();
    $orgid     = $this->controller->organization['id'];

    $userModel->checkEmailAndDisabledStatus(
      $values['email'], \Model\Users::USER_UNVALIDATED, $orgid
    );
    
    if ( !$userModel->row ) {
      
      $this->form->addMessage( $l('users', 'resendhelp') );
      $this->form->invalidate();
      return;
      
    }
    
    if ( $userModel->row['isusergenerated'] ) {
      
      $this->form->addMessage( $l('users', 'forgotpassword_generror') );
      $this->form->invalidate();
      return;
      
    }
    
    $userModel->row['id'] = $crypto->asciiEncrypt( $userModel->id );
    $this->controller->toSmarty['values'] = $userModel->row;
    
    $this->controller->sendOrganizationHTMLEmail(
      $userModel->row['email'],
      $l('users', 'validationemailsubject'),
      $this->controller->fetchSmarty('Visitor/Users/Email/Validation.tpl')
    );
    
    $this->controller->redirect('contents/needvalidation');
    
  }
  
}
