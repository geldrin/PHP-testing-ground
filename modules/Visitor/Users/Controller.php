<?php
namespace Visitor\Users;

class Controller extends \Springboard\Controller\Visitor {
  public $permissions = array(
    'login'          => 'public',
    'logout'         => 'public',
    'signup'         => 'public',
    'modify'         => 'member',
    'index'          => 'public',
    'validate'       => 'public',
    'forgotpassword' => 'public',
    'changepassword' => 'public',
  );
  
  public $forms = array(
    'login'          => 'Visitor\\Users\\Form\\Login',
    'signup'         => 'Visitor\\Users\\Form\\Signup',
    'forgotpassword' => 'Visitor\\Users\\Form\\Forgotpassword',
    'changepassword' => 'Visitor\\Users\\Form\\Changepassword',
  );
  
  public function indexAction() {
    echo 'Nothing here yet';
  }
  
  public function validateAction() {
    
    $code = $this->application->getParameter('code');
    
    if ( strlen( $code ) < 10 )
      $this->redirectToFragment('contents/signupvalidationfailed');
    
    $crypto         = $this->bootstrap->getCrypto();
    $validationcode = substr( $code, -10 );
    $userid         =
      intval( $crypto->asciiDecrypt( substr( $code, 0, -10 ) ) )
    ;
    
    if ( $userid <= 0 )
      $this->redirectToFragment('contents/signupvalidationfailed');
    
    $userModel = $this->bootstrap->getModel('users');
    $userModel->select( $userid );
    
    if ( @$userModel->row['validationcode'] !== $validationcode )
      $this->redirectToFragment('contents/signupvalidationfailed');
    
    $userModel->updateRow( array(
        'disabled' => 0,
      )
    );
    
    $userModel->registerForSession();
    $this->redirectToFragment('contents/signupvalidated');
    
  }
  
  public function logoutAction() {
    
    $user = $this->bootstrap->getUser();
    $user->destroy();
    
    $this->redirectToFragmentWithMessage('index', 'loggedout');
    
  }
  
}
