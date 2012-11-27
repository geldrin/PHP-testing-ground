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
    'resend'         => 'public',
    'invite'         => 'clientadmin',
    'validateinvite' => 'public',
    'disable'        => 'clientadmin',
    'admin'          => 'clientadmin',
    'edit'           => 'clientadmin',
  );
  
  public $forms = array(
    'login'          => 'Visitor\\Users\\Form\\Login',
    'signup'         => 'Visitor\\Users\\Form\\Signup',
    'forgotpassword' => 'Visitor\\Users\\Form\\Forgotpassword',
    'changepassword' => 'Visitor\\Users\\Form\\Changepassword',
    'invite'         => 'Visitor\\Users\\Form\\Invite',
    'modify'         => 'Visitor\\Users\\Form\\Modify',
    'resend'         => 'Visitor\\Users\\Form\\Resend',
    'edit'           => 'Visitor\\Users\\Form\\Edit',
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
      'recordingid' => array(
        'type'     => 'id',
        'required' => false,
      ),
      'feedid' => array(
        'type'     => 'id',
        'required' => false,
      ),
    ),
  );
  
  public function indexAction() {
    echo 'Nothing here yet';
  }
  
  public function welcomeAction() {
    
    $l           = $this->bootstrap->getLocalization();
    $uploadModel = $this->bootstrap->getModel('uploads');
    $uploads     = $uploadModel->getUploads( $this->bootstrap->getSession('user') );
    
    if ( !empty( $uploads ) )
      $this->toSmarty['sessionmessage'] = sprintf(
        $l('recordings', 'continueupload'),
        \Springboard\Language::get() . '/recordings/upload'
      );
    
    $this->smartyoutput('Visitor/Users/Welcome.tpl');
    
  }
  
  public function validateAction() {
    
    $access    = $this->bootstrap->getSession('recordingaccess');
    $userModel = $this->bootstrap->getModel('users');
    $uservalid = $userModel->checkIDAndValidationCode(
      $this->application->getParameter('a'),
      $this->application->getParameter('b')
    );
    
    if ( !$uservalid )
      $this->redirect('contents/signupvalidationfailed');
    
    $userModel->updateRow( array(
        'disabled' => 0,
      )
    );
    
    $userModel->registerForSession();
    $access->clear();
    
    $this->redirectToController('contents', 'signupvalidated');
    
  }
  
  public function validateinviteAction() {
    
    $crypt = $this->bootstrap->getEncryption();
    $id    = intval( $crypt->asciiDecrypt( $this->application->getParameter('a') ) );
    $validationcode = $this->application->getParameter('b');
    
    if ( $id <= 0 or !$validationcode )
      $this->redirect('contents/invitationvalidationfailed');
    
    $invitationModel = $this->bootstrap->getModel('users_invitations');
    $invitationModel->select( $id );
    
    if ( !$invitationModel->row or $invitationModel->row['validationcode'] !== $validationcode )
      $this->redirectToController('contents', 'invitationvalidationfailed');
    
    $invitationSession = $this->bootstrap->getSession('userinvitation');
    $invitationSession['invitation'] = $invitationModel->row;
    
    // elküldeni regisztrálni
    $this->redirectToController('contents', 'invitationvalidated');
    
  }
  
  public function logoutAction() {
    
    $l    = $this->bootstrap->getLocalization();
    $user = $this->bootstrap->getSession('user');
    $user->clear();
    session_destroy();
    $this->redirectWithMessage('index', $l('users', 'loggedout') );
    
  }
  
  public function disableAction() {
    
    $userid = $this->application->getNumericParameter('id');
    if ( !$userid )
      $this->redirect('index');
    
    $forward   = $this->application->getParameter('forward', 'users/admin');
    $l         = $this->bootstrap->getLocalization();
    $user      = $this->bootstrap->getSession('user');
    
    if ( $user['id'] == $userid )
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
  public function authenticateAction( $email, $password, $recordingid = null, $feedid = null ) {
    
    if ( !$email or !$password )
      return false;
    
    $l         = $this->bootstrap->getLocalization();
    $userModel = $this->bootstrap->getModel('users');
    $uservalid = $userModel->selectAndCheckUserValid(
      $this->organization['id'],
      $email,
      $password
    );
    
    if ( !$uservalid ) {
      
      $message = sprintf(
        $l('users', 'accessdenied'),
        $this->bootstrap->baseuri . \Springboard\Language::get() .
        '/users/forgotpassword?email=' . rawurlencode( $email )
      );
      
      throw new \Exception( $message );
      
    }
    
    if ( $userModel->row['isadmin'] )
      $userModel->row['organizationid'] = $this->organization['id']; // a registerforsession miatt
    
    $userModel->registerForSession();
    $userModel->updateLastlogin();
    
    if ( $recordingid ) {
      
      $recordingsModel = $this->modelIDCheck( 'recordings', $recordingid, false );
      
      if ( !$recordingsModel )
        throw new \Exception( $l('recordings', 'norecording') );
      
      $browserinfo     = $this->bootstrap->getBrowserInfo();
      $user            = $this->bootstrap->getSession('user');
      $access          = $this->bootstrap->getSession('recordingaccess');
      $accesskey       =
        $recordingsModel->id . '-' .
        (int)$recordingsModel->row['issecurestreamingforced']
      ;
      $access[ $accesskey ] =
        $recordingsModel->userHasAccess( $user, null, $browserinfo['mobile'] )
      ;
    
      if ( $access[ $accesskey ] !== true )
        throw new \Exception( $l('recordings', 'nopermission') );
      
    } elseif ( $feedid ) {
      
      $feedModel = $this->modelIDCheck( 'livefeeds', $feedid );
      
      if ( !$feedModel )
        throw new \Exception( $l('live', 'nofeed') );
      
      $user      = $this->bootstrap->getSession('user');
      $access    = $this->bootstrap->getSession('liveaccess');
      $accesskey = $feedModel->id . '-0'; // TODO secure
      
      $access[ $accesskey ] = $feedModel->isAccessible( $user );
      
      if ( $access[ $accesskey ] !== true )
        throw new \Exception( $l('recordings', 'nopermission') );
      
    }
    
    return true;
    
  }
  
}
