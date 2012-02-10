<?php
namespace Visitor\Users;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'login'          => 'public',
    'logout'         => 'public',
    'signup'         => 'public',
    'modify'         => 'member',
    'index'          => 'public',
    'validate'       => 'public',
    'forgotpassword' => 'public',
    'changepassword' => 'public',
    'invite'         => 'admin',
    'validateinvite' => 'public',
    'disable'        => 'admin',
    'listing'        => 'admin',
  );
  
  public $forms = array(
    'login'          => 'Visitor\\Users\\Form\\Login',
    'signup'         => 'Visitor\\Users\\Form\\Signup',
    'forgotpassword' => 'Visitor\\Users\\Form\\Forgotpassword',
    'changepassword' => 'Visitor\\Users\\Form\\Changepassword',
    'invite'         => 'Visitor\\Users\\Form\\Invite',
  );
  
  public $paging = array(
    'listing' => 'Visitor\\Users\\Paging\\Listing',
  );
  
  public function indexAction() {
    echo 'Nothing here yet';
  }
  
  protected function parseValidationCode( $code ) {
    
    if ( strlen( $code ) < 10 )
      return false;
    
    $crypto         = $this->bootstrap->getEncryption();
    $validationcode = substr( $code, -10 );
    $id             =
      intval( $crypto->asciiDecrypt( substr( $code, 0, -10 ) ) )
    ;
    
    if ( $id <= 0 )
      return false;
    
    return array(
      'id'             => $id,
      'validationcode' => $validationcode,
    );
    
  }
  
  public function validateAction() {
    
    $code = $this->application->getParameter('code');
    if ( !( $data = $this->parseValidationCode( $code ) ) )
      $this->redirect('contents/signupvalidationfailed');
    
    $userModel = $this->bootstrap->getModel('users');
    $userModel->select( $data['id'] );
    
    if ( !$userModel->row or $userModel->row['validationcode'] !== $data['validationcode'] )
      $this->redirect('contents/signupvalidationfailed');
    
    $userModel->updateRow( array(
        'disabled' => 0,
      )
    );
    
    $userModel->registerForSession();
    $this->redirectToController('contents', 'signupvalidated');
    
  }
  
  public function validateinviteAction() {
    
    $code = $this->application->getParameter('code');
    if ( !( $data = $this->parseValidationCode( $code ) ) )
      $this->redirect('contents/invitationvalidationfailed');
    
    $invitationModel = $this->bootstrap->getModel('users_invitations');
    $invitationModel->select( $data['id'] );
    
    if ( !$invitationModel->row or $invitationModel->row['validationcode'] !== $data['validationcode'] )
      $this->redirect('contents/invitationvalidationfailed');
    
    $invitationSession = $this->bootstrap->getSession('userinvitation');
    $invitationSession['invitation'] = $invitationModel->row;
    
    // elküldeni regisztrálni
    $this->redirectToController('contents', 'invitationvalidated');
    
  }
  
  public function logoutAction() {
    
    $user = $this->bootstrap->getUser();
    $user->destroy();
    
    $this->redirectWithMessage('index', 'loggedout');
    
  }
  
  public function disableAction() {
    
    $userid = $this->application->getNumericParameter('id');
    if ( !$userid )
      $this->redirect('index');
    
    $forward   = $this->application->getParameter('forward', 'users/list');
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
  
}
