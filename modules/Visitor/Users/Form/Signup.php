<?php
namespace Visitor\Users\Form;
class Signup extends \Visitor\Form {
  public $configfile = 'Signup.php';
  public $template   = 'Visitor/Users/Login.tpl';
  public $needdb     = true;
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocale();
    $this->toSmarty['title'] = $l('users', 'register_title');
    
  }
  
  public function onComplete() {
    
    $values    = $this->form->getElementValues( 0 );
    $userModel = $this->bootstrap->getModel('users');
    $crypto    = $this->bootstrap->getCrypto();
    $queue     = $this->bootstrap->getMailqueue();
    $smarty    = $this->bootstrap->getSmarty();
    $l         = $this->bootstrap->getLocale();
    
    $values['timestamp']      = date('Y-m-d H:i:s');
    $values['lastloggedin']   = $values['timestamp'];
    $values['browser']        = $_SERVER['HTTP_USER_AGENT'];
    $values['disabled']       = -1; // until validated the user is banned
    $values['validationcode'] = $crypto->randomPassword( 10 );
    $values['password']       = $crypto->getHash( $values['password'] );
    $values['language']       = \Springboard\Language::get();
    $values['organizationid'] = $this->bootstrap->getOrganization()->id;
    
    $userModel->insert( $values );
    
    $userModel->row['id'] = $crypto->asciiEncrypt( $userModel->id );
    $smarty->assign('values', $userModel->row );
    
    $queue->embedImages = false;
    $queue->sendHTMLEmail(
      $userModel->row['email'],
      $l('users', 'validationemailsubject'),
      $smarty->fetch('Visitor/Users/Email/Validation.tpl')
    );
    
    $this->controller->redirectToFragment('contents/needvalidation');
    
  }
  
}
