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
      $this->controller->redirectToFragment('');
    
    $code = $this->application->getParameter('code');
    if ( !strlen( $code ) < 11 )
      $this->controller->redirectToFragment('contents/badparameter');
    
    $this->crypto         = $this->bootstrap->getCrypto();
    $this->validationcode = substr( $code, -10 );
    $this->userid         = substr( $code, 0, -10 );
    $this->userid         = intval( $this->crypto->asciiDecrypt( $this->userid ) );
    
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
      
      $this->form->addMessage( l('users', 'changepass_badparameter') );
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
    
    $this->redirectToFragmentWithMessage('index', $l('users', 'changepass_changed') );
    
  }
  
}









class formHandler_userschangepassword extends formHandler_help {
  var $getDatabase = true;
  var $config      = 'users_changepassword.php';
  var $template    = 'genericform.tpl';
  
  function check() {
    
    if ( getuser('id') )
      tools::go(); // the user is already logged in
    
    if ( !isset( $_REQUEST['a'] ) or strlen( @$_REQUEST['b'] ) !== 10 )
      tools::go(); // invalid parameters
    
    $userid = tools::decrypt( $_REQUEST['a'] );
    if ( !preg_match('/^[0-9]+$/', $userid ) or $userid <= 0 )
      tools::go(); // invalid 'a' parameter
    
    parent::check();
    
  }
  
  // ----------------------------------------------------------------------------
  function onComplete() {
    
  }
  
}

?>