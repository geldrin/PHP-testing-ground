<?php
namespace Visitor\Users;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'login'          => 'public',
    'logout'         => 'public',
    'signup'         => 'public',
    'modify'         => 'member',
    'welcome'        => 'member',
    'index'          => 'public',
    'validate'       => 'public',
    'forgotpassword' => 'public',
    'changepassword' => 'public',
    'invite'         => 'admin',
    'validateinvite' => 'public',
    'disable'        => 'admin',
    'admin'          => 'admin',
  );
  
  public $forms = array(
    'login'          => 'Visitor\\Users\\Form\\Login',
    'signup'         => 'Visitor\\Users\\Form\\Signup',
    'forgotpassword' => 'Visitor\\Users\\Form\\Forgotpassword',
    'changepassword' => 'Visitor\\Users\\Form\\Changepassword',
    'invite'         => 'Visitor\\Users\\Form\\Invite',
    'modify'         => 'Visitor\\Users\\Form\\Modify',
  );
  
  public $paging = array(
    'admin' => 'Visitor\\Users\\Paging\\Admin',
  );
  
  public $apisignature = array(
    'authenticate' => array(
      'email' => array(
        'type' => 'string'
      ),
      'password' => array(
        'type' => 'string'
      ),
    ),
  );
  
  public function indexAction() {
    echo 'Nothing here yet';
  }
  
  public function welcomeAction() {
    $smarty = $this->bootstrap->getSmarty();
    $this->output( $smarty->fetch('Visitor/Users/Welcome.tpl') );
  }
  
  protected function parseValidationCode() {
    
    $crypto         = $this->bootstrap->getEncryption();
    $validationcode = $this->application->getParameter('b');
    $id             =
      intval( $crypto->asciiDecrypt( $this->application->getParameter('a') ) )
    ;
    
    if ( $id <= 0 or !$validationcode )
      return false;
    
    return array(
      'id'             => $id,
      'validationcode' => $validationcode,
    );
    
  }
  
  public function validateAction() {
    
    if ( !( $data = $this->parseValidationCode() ) )
      $this->redirect('contents/signupvalidationfailed');
    
    $userModel = $this->bootstrap->getModel('users');
    $userModel->select( $data['id'] );
    
    if ( !$userModel->row or $userModel->row['validationcode'] !== $data['validationcode'] )
      $this->redirectToController('contents', 'signupvalidationfailed');
    
    $userModel->updateRow( array(
        'disabled' => 0,
      )
    );
    
    $userModel->registerForSession();
    $this->redirectToController('contents', 'signupvalidated');
    
  }
  
  public function validateinviteAction() {
    
    if ( !( $data = $this->parseValidationCode() ) )
      $this->redirect('contents/invitationvalidationfailed');
    
    $invitationModel = $this->bootstrap->getModel('users_invitations');
    $invitationModel->select( $data['id'] );
    
    if ( !$invitationModel->row or $invitationModel->row['validationcode'] !== $data['validationcode'] )
      $this->redirectToController('contents', 'invitationvalidationfailed');
    
    $invitationSession = $this->bootstrap->getSession('userinvitation');
    $invitationSession['invitation'] = $invitationModel->row;
    
    // elküldeni regisztrálni
    $this->redirectToController('contents', 'invitationvalidated');
    
  }
  
  public function logoutAction() {
    
    $l    = $this->bootstrap->getLocalization();
    $user = $this->bootstrap->getUser();
    $user->destroy();
    
    $this->redirectWithMessage('index', $l('users', 'loggedout') );
    
  }
  
  public function disableAction() {
    
    $userid = $this->application->getNumericParameter('id');
    if ( !$userid )
      $this->redirect('index');
    
    $forward   = $this->application->getParameter('forward', 'users/admin');
    $l         = $this->bootstrap->getLocalization();
    $user      = $this->bootstrap->getUser();
    
    if ( $user->id == $userid )
      $this->redirectWithMessage( $forward, $l('users', 'cantdisableself') );
    
    $userModel = $this->bootstrap->getModel('users');
    $userModel->select( $userid );
    $userModel->updateRow( array(
        'disabled' => $userModel::USER_DISABLED,
      )
    );
    
    $this->redirectWithMessage( $forward, $l('users', 'userdisabled') );
    
  }
  
  // pure api hivas, nem erheto el apin kivulrol (mert nincs a permission tombbe)
  public function authenticateAction() {
    
    $email        = $this->application->getParameter('email');
    $password     = $this->application->getParameter('password');
    $userModel    = $this->bootstrap->getModel('users');
    $organization = $this->bootstrap->getOrganization();
    $uservalid    = $userModel->selectAndCheckUserValid( $organization->id, $email, $password );
    
    if ( $uservalid ) {
      
      $userModel->registerForSession();
      $userModel->updateLastlogin();
      
    }
    
    return $uservalid;
    
  }
  
}
