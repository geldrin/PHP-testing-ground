<?php
namespace Visitor\Users;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'login'                => 'public',
    'logout'               => 'public',
    'signup'               => 'public',
    'modify'               => 'member',
    'welcome'              => 'member',
    'index'                => 'public',
    'validate'             => 'public',
    'forgotpassword'       => 'public',
    'changepassword'       => 'public',
    'resend'               => 'public',
    'invite'               => 'clientadmin',
    'invitations'          => 'clientadmin',
    'validateinvite'       => 'public',
    'disable'              => 'clientadmin',
    'admin'                => 'clientadmin',
    'edit'                 => 'clientadmin',
    'resetsession'         => 'public',
    'validateresetsession' => 'public',
    'resendinvitation'     => 'clientadmin',
    'disableinvitation'    => 'clientadmin',
    'editinvite'           => 'clientadmin',
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
    'resetsession'   => 'Visitor\\Users\\Form\\Resetsession',
    'editinvite'     => 'Visitor\\Users\\Form\\Editinvite',
  );
  
  public $paging = array(
    'admin'       => 'Visitor\\Users\\Paging\\Admin',
    'invitations' => 'Visitor\\Users\\Paging\\Invitations',
  );
  
  public $apisignature = array(
    'authenticate' => array(
      'loginrequired' => false,
      'email'         => array(
        'type' => 'string',
        'shouldemail' => false,
      ),
      'password' => array(
        'type' => 'string',
        'shouldemail' => false,
      ),
      'recordingid' => array(
        'type'     => 'id',
        'required' => false,
        'shouldemail' => false,
      ),
      'feedid' => array(
        'type'     => 'id',
        'required' => false,
        'shouldemail' => false,
      ),
    ),
    'setuserfield' => array(
      'userid' => array(
        'type' => 'id',
      ),
      'field' => array(
        'type' => 'string',
      ),
      'value' => array(
        'type'     => 'string',
        'required' => false,
      ),
      'user' => array(
        'type'       => 'user',
        'permission' => 'admin',
      ),
    ),
    'ping' => array(
      'loginrequired' => false,
      'hashrequired'  => false,
    ),
  );
  
  protected $l;
  protected $crypto;
  protected $invitationcache;

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
  
  public function validateresetsessionAction() {
    
    $userModel = $this->bootstrap->getModel('users');
    $uservalid = $userModel->checkIDAndValidationCode(
      $this->application->getParameter('a'),
      $this->application->getParameter('b')
    );
    
    if ( !$uservalid )
      $this->redirect('contents/signupvalidationfailed');
    
    $userModel->registerForSession();
    $userModel->updateSessionInformation();
    $this->logUserLogin('RESETSESSION LOGIN');
    
    $this->redirectToController('contents', 'sessionreset');
    
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
    $this->logUserLogin('VALIDATED LOGIN');
    
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
   
    if ( $user['id'] ) {
      $userModel = $this->bootstrap->getModel('users');
      $userModel->select( $user['id'] );
      $userModel->row['sessionlastupdated'] = '';
      $userModel->row['sessionid']          = '';
      $userModel->updateRow( $userModel->row );
    }

    $user->clear();
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
      
      throw new \Visitor\Api\ApiException( $message, true, false );
      
    }
    
    if ( !$userModel->checkSingleLoginUsers() ) {
      
      $message = sprintf(
        $l('users','login_apisessionerror'),
        \Springboard\Language::get() . '/users/resetsession?email=' . rawurlencode( $email )
      );
      
      throw new \Visitor\Api\ApiException( $message, true, false );
      
    }
    
    if ( $userModel->row['isadmin'] )
      $userModel->row['organizationid'] = $this->organization['id']; // a registerforsession miatt
    
    $ipaddresses = $this->getIPAddress(true);
    $ipaddress   = '';
    foreach( $ipaddresses as $key => $value )
      $ipaddress .= ' ' . $key . ': ' . $value;
    
    $userModel->registerForSession();
    $userModel->updateSessionInformation();
    $userModel->updateLastlogin( null, $ipaddress );
    $this->logUserLogin('APILOGIN');
    
    $output = array(
      'userid'                           => $userModel->id,
      'needping'                         => (bool)$userModel->row['issingleloginenforced'],
      'pingseconds'                      => $this->bootstrap->config['sessionpingseconds'],
      'checkwatching'                    => (bool)$userModel->row['ispresencecheckforced'],
      'checkwatchingtimeinterval'        => $this->organization['presencechecktimeinterval'],
      'checkwatchingconfirmationtimeout' => $this->organization['presencecheckconfirmationtime'],
    );
    
    if ( $recordingid ) {
      
      $recordingsModel = $this->modelIDCheck( 'recordings', $recordingid, false );
      
      if ( !$recordingsModel )
        throw new \Visitor\Api\ApiException( $l('recordings', 'norecording'), true, false );
      
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
        throw new \Visitor\Api\ApiException( $l('recordings', 'nopermission'), true, false );
      
      $output = array_merge( $output, $recordingsModel->getSeekbarOptions( $userModel->row ) );
      
    } elseif ( $feedid ) {
      
      $feedModel = $this->modelIDCheck( 'livefeeds', $feedid, false );
      
      if ( !$feedModel )
        throw new \Visitor\Api\ApiException( $l('live', 'nofeed'), true, false );
      
      $user      = $this->bootstrap->getSession('user');
      $access    = $this->bootstrap->getSession('liveaccess');
      $accesskey = $feedModel->id . '-' . ( $feedModel->row['issecurestreamingforced']? '1': '0');
      
      $access[ $accesskey ] = $feedModel->isAccessible( $user );
      
      if ( $access[ $accesskey ] !== true )
        throw new \Visitor\Api\ApiException( $l('recordings', 'nopermission'), true, false );
      
    }
    
    return $this->getFlashParameters( $output );
    
  }
  
  public function pingAction() {
    
    $user = $this->bootstrap->getSession('user');
    if ( !$user['id'] )
      return false;
    
    $userModel = $this->bootstrap->getModel('users');
    $userModel->select( $user['id'] );
    
    if ( !$userModel->row )
      return false;
    
    if ( !$userModel->checkSingleLoginUsers() ) {
      
      $user->clear();
      $l = $this->bootstrap->getLocalization();
      $this->addMessage( $l('users', 'loggedout_sessionexpired') );
      return false;
      
    }
    
    $userModel->updateSessionInformation();
    return true;
    
  }
  
  public function setuserfieldAction( $userid, $field, $value ) {
    
    $userModel = $this->bootstrap->getModel('users');
    $userModel->select( $userid );
    
    if ( !$userModel->row )
      throw new \Visitor\Api\ApiException("User with id: $userid not found", true, false );
    
    if ( !isset( $userModel->row[ $field ] ) )
      throw new \Visitor\Api\ApiException("Field: $field not found", true, false );
    
    $userModel->updateRow( array(
        $field => @$_REQUEST['value'],
      )
    );
    
    return $userModel->row;
    
  }
  
  public function sendInvitationEmail( &$invitation ) {
    
    if ( !$this->l )
      $this->l = $this->bootstrap->getLocalization();

    if ( !$this->crypto )
      $this->crypto = $this->bootstrap->getEncryption();

    $db = $this->bootstrap->getAdoDB();
    if ( isset( $invitation['recordingid'] ) and $invitation['recordingid'] ) {

      if ( !isset( $this->invitationcache['recording-' . $invitation['recordingid'] ] ) )
        $this->invitationcache['recording-' . $invitation['recordingid'] ] =
          $db->getRow("
            SELECT
              title,
              subtitle
            FROM recordings
            WHERE id = '" . $invitation['recordingid'] . "'
            LIMIT 1
          ");
        ;

      $this->toSmarty['recording'] =
        $this->invitationcache['recording-' . $invitation['recordingid'] ]
      ;

    }

    if ( isset( $invitation['livefeedid'] ) and $invitation['livefeedid'] ) {

      if ( !isset( $this->invitationcache['livefeed-' . $invitation['livefeedid'] ] ) )
        $this->invitationcache['livefeed-' . $invitation['livefeedid'] ] =
          $db->getOne("
            SELECT name
            FROM livefeeds
            WHERE id = '" . $invitation['livefeedid'] . "'
            LIMIT 1
          ");
        ;

      $this->toSmarty['livefeed'] =
        $this->invitationcache['livefeed-' . $invitation['livefeedid'] ]
      ;

    }

    if ( isset( $invitation['channelid'] ) and $invitation['channelid'] ) {

      if ( !isset( $this->invitationcache['channel-' . $invitation['channelid'] ] ) )
        $this->invitationcache['channel-' . $invitation['channelid'] ] =
          $db->getRow("
            SELECT
              title,
              subtitle
            FROM channels
            WHERE id = '" . $invitation['channelid'] . "'
            LIMIT 1
          ");
        ;

      $this->toSmarty['channel'] =
        $this->invitationcache['channel-' . $invitation['channelid'] ]
      ;

    }

    if ( !isset( $this->invitationcache['user-' . $invitation['userid'] ] ) ) {
      $this->invitationcache['user-' . $invitation['userid'] ] =
        $db->getRow("
          SELECT
            nameprefix,
            namefirst,
            namelast,
            nameformat,
            nickname
          FROM users
          WHERE id = '" . $invitation['userid'] . "'
          LIMIT 1
        ");

      $this->toSmarty['user']   =
        $this->invitationcache['user-' . $invitation['userid'] ]
      ;

    }

    if ( !isset( $this->invitationcache['groups-' . $invitation['groups'] ] ) ) {

      $this->invitationcache['groups-' . $invitation['groups'] ] = true;
      $ids = explode('|', $invitation['groups'] );
      if ( !empty( $ids ) )
        $this->toSmarty['groups'] = $db->getArray("
          SELECT *
          FROM groups
          WHERE id IN('" . implode("', '", $ids ) . "')
        ");

    }

    if ( !isset( $this->invitationcache['departments-' . $invitation['departments'] ] ) ) {

      $this->invitationcache['departments-' . $invitation['departments'] ] = true;
      $ids = explode('|', $invitation['departments'] );
      if ( !empty( $ids ) )
        $this->toSmarty['departments'] = $db->getArray("
          SELECT *
          FROM departments
          WHERE id IN('" . implode("', '", $ids ) . "')
        ");

    }

    $l = $this->l;
    if ( !isset( $this->invitationcache['permissions-' . $invitation['permissions'] ] ) ) {

      $permissions = array();
      foreach ( explode('|', $invitation['permissions'] ) as $permission )
        $permissions[] = $l->getLov('permissions', null, $permission );

      $this->invitationcache['permissions-' . $invitation['permissions'] ] = true;
      $this->toSmarty['permissions'] = $permissions;

    }

    $invitation['id'] = $this->crypto->asciiEncrypt( $invitation['id'] );
    $this->toSmarty['values'] = $invitation;
    $this->sendOrganizationHTMLEmail(
      $invitation['email'],
      $l('users', 'invitationmailsubject'),
      $this->fetchSmarty('Visitor/Users/Email/Invitation.tpl')
    );
    
  }

  public function resendinvitationAction() {

    $invitationModel = $this->modelOrganizationAndUserIDCheck(
      'users_invitations',
      $this->application->getNumericParameter('id')
    );
    $this->sendInvitationEmail( $invitationModel->row );
    $l = $this->l; // a sendInvitationEmail setupolta

    $this->redirectWithMessage(
      $this->application->getParameter('forward', 'users/invitations'),
      $l('users', 'user_invited')
    );

  }

  public function disableinvitationAction() {

    $invitationModel = $this->modelOrganizationAndUserIDCheck(
      'users_invitations',
      $this->application->getNumericParameter('id')
    );

    if ( $invitationModel->row['status'] != 'deleted' )
      $invitationModel->updateRow( array(
          'status' => 'deleted',
        )
      );

    $l = $this->bootstrap->getLocalization();

    $this->redirectWithMessage(
      $this->application->getParameter('forward', 'users/invitations'),
      $l('users', 'invitation_disabled')
    );

  }

}
