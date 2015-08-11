<?php
namespace Visitor\Users;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'login'                => 'public',
    'logout'               => 'public',
    'signup'               => 'public',
    'modify'               => 'member',
    'welcome'              => 'member',
    'info'                 => 'member',
    'index'                => 'public',
    'validate'             => 'public',
    'forgotpassword'       => 'public',
    'changepassword'       => 'public',
    'resend'               => 'public',
    'invite'               => 'clientadmin', // invite linkelunk groups/users-bol is
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
    'getinvitationtemplate' => 'clientadmin',
    'togglesubscription'   => 'member',
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
    $userModel   = $this->bootstrap->getModel('users');
    $user        = $this->bootstrap->getSession('user');
    $uploads     = $uploadModel->getUploads( $this->bootstrap->getSession('user') );
    
    $userModel->id  = $user['id'];
    $userModel->row = $user->toArray();
    $this->toSmarty['channels'] = $userModel->getCourses(
      $this->organization
    );

    $recordingids = array();
    foreach ( $this->toSmarty['channels'] as $channel ) {
      foreach( $channel['recordings'] as $recording )
        $recordingids[] = $recording['id'];
    }

    $this->toSmarty['accreditedrecordings'] = $userModel->getAccreditedRecordings(
      $this->organization['id'],
      $recordingids
    );

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
    
    // ha users_invite-bol regisztralt a user akkor validalas utan itt lokjuk at
    // kozvetlenul
    $inviteforwardSession = $this->bootstrap->getSession('inviteforward');
    if ( $inviteforwardSession['forward'] ) {
      $forward = $inviteforwardSession['forward'];
      $inviteforwardSession->clear();
      $this->redirect( $forward );
    }

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
    
    if ( $invitationModel->isExpired() )
      $this->redirectToController('contents', 'invitationvalidationexpired');

    $user                 = $this->bootstrap->getSession('user');
    $forward              = $this->application->getParameter('forward');
    $invitationSession    = $this->bootstrap->getSession('userinvitation');
    $inviteforwardSession = $this->bootstrap->getSession('inviteforward');
    $invitationSession['invitation'] = $invitationModel->row;
    $inviteforwardSession['forward'] = $forward;

    if (
         $forward and
         $invitationModel->row['registereduserid']
       ) {

      // ha van hova redirectelni, es be van lepve es azonos az invitationt elfogadott
      // userrel akkor kozvetlenul iranyitsuk at
      if ( $user['id'] and $invitationModel->row['registereduserid'] == $user['id'] )
        $this->redirect( $forward );
      else // amugy eloszor leptessuk be es utana iranyitsuk at kozvetlenul
        $this->redirect('users/login', array('forward' => $forward ) );

    }

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
      $userModel->unsetAutoLoginCookie( $this->bootstrap->ssl );
    }

    $user->clear();
    $this->regenerateSessionID();
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
    
    if ( $uservalid !== true ) {
      
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
    
    $userModel->registerForSession();
    $userModel->updateSessionInformation();
    $userModel->updateLastlogin( null, $this->getIPAddress(true) );
    $this->logUserLogin('APILOGIN');
    
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
        $recordingsModel->userHasAccess( $user, null, $browserinfo['mobile'], $this->organization )
      ;

      $this->toSmarty['member']    = $user;
      $this->toSmarty['ipaddress'] = $this->getIPAddress();
      $this->toSmarty['sessionid'] = session_id();
      $output = $recordingsModel->getFlashData( $this->toSmarty );
      
    } elseif ( $feedid ) {
      
      $feedModel = $this->modelIDCheck( 'livefeeds', $feedid, false );
      
      if ( !$feedModel )
        throw new \Visitor\Api\ApiException( $l('live', 'nofeed'), true, false );
      
      $user      = $this->bootstrap->getSession('user');
      $access    = $this->bootstrap->getSession('liveaccess');
      $accesskey = $feedModel->id . '-' . ( $feedModel->row['issecurestreamingforced']? '1': '0');
      
      $access[ $accesskey ] = $feedModel->isAccessible( $user, $this->organization );

      $info = array(
        'organization' => $this->organization,
        'sessionid'    => session_id(),
        'ipaddress'    => $this->getIPAddress(),
        'BASE_URI'     => $this->toSmarty['BASE_URI'],
        'cookiedomain' => $this->organization['cookiedomain'],
        'streams'      => $feedModel->getStreamsForBrowser( $this->bootstrap->getBrowserInfo() ),
        'member'       => $user,
        'checkwatchingtimeinterval' => $this->organization['presencechecktimeinterval'],
        'checkwatchingconfirmationtimeout' => $this->organization['presencecheckconfirmationtime'],
      );
      $output = $feedModel->getFlashData( $info );

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
    $foundcontent = false;

    if ( isset( $invitation['recordingid'] ) and $invitation['recordingid'] ) {

      $foundcontent = true;
      if ( !isset( $this->invitationcache['recording-' . $invitation['recordingid'] ] ) )
        $this->invitationcache['recording-' . $invitation['recordingid'] ] =
          $db->getRow("
            SELECT *
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

      $foundcontent = true;
      if ( !isset( $this->invitationcache['livefeed-' . $invitation['livefeedid'] ] ) ) {
        $this->invitationcache['livefeed-' . $invitation['livefeedid'] ] =
          $db->getRow("
            SELECT *
            FROM livefeeds
            WHERE id = '" . $invitation['livefeedid'] . "'
            LIMIT 1
          ");
        ;
        $this->invitationcache['livefeed-' . $invitation['livefeedid'] ]['channel'] =
          $db->getRow("
            SELECT *
            FROM channels
            WHERE id = '" . $this->invitationcache['livefeed-' . $invitation['livefeedid'] ]['channelid'] . "'
            LIMIT 1
          ");
        ;
      }

      $this->toSmarty['livefeed'] =
        $this->invitationcache['livefeed-' . $invitation['livefeedid'] ]
      ;

    }

    if ( isset( $invitation['channelid'] ) and $invitation['channelid'] ) {

      $foundcontent = true;
      if ( !isset( $this->invitationcache['channel-' . $invitation['channelid'] ] ) )
        $this->invitationcache['channel-' . $invitation['channelid'] ] =
          $db->getRow("
            SELECT *
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
            id,
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
      foreach ( explode('|', $invitation['permissions'] ) as $permission ) {
        if ( !$permission )
          continue;

        $permissions[] = $l->getLov('permissions', null, $permission );
      }

      $this->invitationcache['permissions-' . $invitation['permissions'] ] = true;
      $this->toSmarty['permissions'] = $permissions;

    }

    if ( $invitation['templateid'] and !isset( $this->invitationcache['template-' . $invitation['templateid'] ] ) ) {
      $userModel = $this->bootstrap->getModel('users');
      $template  = $userModel->getTemplate(
        $invitation['templateid'],
        $this->organization['id']
      );
      $this->invitationcache['template-' . $invitation['templateid'] ] = $template;
      if ( !strlen( trim( $template['subject'] ) ) )
        $template['subject'] = $l('users', 'templatesubject_default');

      $this->toSmarty['template'] = $template;
    }

    $invitation['id'] = $this->crypto->asciiEncrypt( $invitation['id'] );
    $this->toSmarty['values'] = $invitation;

    // DEBUG AID $this->smartyOutput('Visitor/Users/Email/Invitation.tpl');
    $this->sendOrganizationHTMLEmail(
      $invitation['email'],
      $this->toSmarty['template']['subject'],
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

  public function getinvitationtemplateAction() {

    $userModel = $this->bootstrap->getModel('users');
    $template  = $userModel->getTemplate(
      $this->application->getNumericParameter('templateid'),
      $this->organization['id']
    );

    if ( empty( $template ) )
      $this->jsonOutput( array(
          'status' => 'error',
          'error'  => 'notfound',
        )
      );

    $this->jsonOutput( array(
        'status'  => 'success',
        'subject' => $template['subject'],
        'title'   => $template['title'],
        'prefix'  => $template['prefix'],
        'postfix' => $template['postfix'],
      )
    );

  }

  public function infoAction() {

    $userModel = $this->modelOrganizationAndIDCheck(
      'users',
      $this->application->getNumericParameter('id')
    );

    $this->toSmarty['user']     = $userModel->row;
    $this->toSmarty['channels'] =
      $userModel->getRecordingsProgressWithChannels(
        $this->organization['id']
      )
    ;
    $this->toSmarty['forward']  = $this->application->getParameter(
      'forward', 'users/admin'
    );

    $this->smartyOutput('Visitor/Users/Info.tpl');

  }

  public function togglesubscriptionAction() {
    $channelid = $this->application->getNumericParameter('channelid');
    $state = $this->application->getParameter('state');
    if ( ( $state !== 'add' and $state !== 'del' ) or $channelid <= 0 )
      return $this->redirect('');

    $l         = $this->bootstrap->getLocalization();
    $user      = $this->bootstrap->getSession('user');
    $userModel = $this->bootstrap->getModel('users');
    $userModel->id = $user['id'];

    $userModel->togglesubscription( $channelid, $state );
    $this->redirectWithMessage(
      $this->application->getParameter('forward'),
      $l('users', 'subscription_' . $state )
    );
  }

}
