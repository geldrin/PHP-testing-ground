<?php
namespace Visitor\Users\Form;
class Changepassword extends \Visitor\Form {
  public $configfile = 'Changepassword.php';
  public $template   = 'Visitor/genericform.tpl';
  public $needdb     = true;
  
  protected $validationcode;
  protected $userid;
  protected $crypto;
  
  public function init() {
    
    $user = $this->bootstrap->getUser();
    if ( isset( $user->id ) )
      $this->controller->redirectToFragment('index');
    
    $code = $this->application->getParameter('code');
    if ( strlen( $code ) < 11 )
      $this->controller->redirectToFragment('contents/badparameter');
    
    $this->crypto         = $this->bootstrap->getCrypto();
    $this->validationcode = substr( $code, -10 );
    $this->userid         =
      intval( $this->crypto->asciiDecrypt( substr( $code, 0, -10 ) ) )
    ;
    
    if ( $this->userid <= 0 )
      $this->controller->redirectToFragment('contents/badparameter');
    
  }
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocale();
    $this->toSmarty['title'] = $l('users', 'changepassword_title');
    
  }
  
  public function onComplete() {
    
    $values    = $this->form->getElementValues( 0 );
    $userModel = $this->bootstrap->getModel('users');
    $l         = $this->bootstrap->getLocale();
    
    if ( !$userModel->checkIDAndValidationCode( $this->userid, $this->validationcode ) ) {
      
      $this->form->addMessage( $l('users', 'changepass_badparameter') );
      $this->form->invalidate();
      return;
      
    }
    
    $userModel->updateRow( array(
        'password'       => $this->crypto->getHash( $values['password'] ),
        'validationcode' => 'validated',
      )
    );
    
    if ( $userModel->row['disabled'] == 0 ) {
      
      $userModel->registerForSession();
      $userModel->updateLastLogin();
      
    }
    
    $this->controller->redirectToFragmentWithMessage('index', $l('users', 'changepass_changed') );
    
  }
  
}
