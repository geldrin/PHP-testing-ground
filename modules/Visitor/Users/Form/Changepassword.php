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
    
    $user = $this->bootstrap->getSession('user');
    if ( $user['id'] )
      $this->controller->redirect('index');
    
    $userModel = $this->bootstrap->getModel('users');
    $data      = $userModel->parseValidationCode(
      $this->application->getParameter('a'),
      $this->application->getParameter('b')
    );
    
    if ( !$data )
      $this->controller->redirect('contents/badparameter');
    
    $this->userid         = $data['id'];
    $this->validationcode = $data['validationcode'];
    
    if ( $this->userid <= 0 )
      $this->controller->redirect('contents/badparameter');
    
    parent::init();
    
  }
  
  public function postSetupForm() {
    
    $l = $this->bootstrap->getLocalization();
    $this->controller->toSmarty['title'] = $l('users', 'changepassword_title');
    
  }
  
  public function onComplete() {
    
    $values    = $this->form->getElementValues( 0 );
    $userModel = $this->bootstrap->getModel('users');
    $l         = $this->bootstrap->getLocalization();
    $access    = $this->bootstrap->getSession('recordingaccess');
    $crypto    = $this->bootstrap->getEncryption();
    
    if ( !$userModel->checkIDAndValidationCode( $this->userid, $this->validationcode ) ) {
      
      $this->form->addMessage( $l('users', 'changepass_badparameter') );
      $this->form->invalidate();
      return;
      
    }
    
    $userModel->updateRow( array(
        'password'       => $crypto->getHash( $values['password'] ),
        'validationcode' => 'validated',
      )
    );
    
    if ( $userModel->row['disabled'] == 0 ) {
      
      $userModel->registerForSession();
      $userModel->updateLastLogin();
      $access->clear();
      
    }
    
    $this->controller->redirectWithMessage('index', $l('users', 'changepass_changed') );
    
  }
  
}
