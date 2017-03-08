<?php
namespace Visitor\Recordings;

class Controller extends \Visitor\Controller {
  public $commentsperpage = 5;

  public $permissions = array(
    'index'                => 'public',
    'details'              => 'public',
    'getplayerconfig'      => 'public',
    'getcomments'          => 'public',
    'getsubtitle'          => 'public',
    'newcomment'           => 'public',
    'moderatecomment'      => 'uploader|moderateduploader|editor|clientadmin',
    'rate'                 => 'public',
    'upload'               => 'uploader|moderateduploader',
    'uploadcontent'        => 'uploader|moderateduploader',
    'uploadsubtitle'       => 'uploader|moderateduploader',
    'uploadattachment'     => 'uploader|moderateduploader',
    'myrecordings'         => 'uploader|moderateduploader|editor|clientadmin',
    'modifybasics'         => 'uploader|moderateduploader|editor|clientadmin',
    'modifyclassification' => 'uploader|moderateduploader|editor|clientadmin',
    'modifydescription'    => 'uploader|moderateduploader|editor|clientadmin',
    'modifycontributors'   => 'uploader|moderateduploader|editor|clientadmin',
    'modifysharing'        => 'uploader|moderateduploader|editor|clientadmin',
    'modifyattachment'     => 'uploader|moderateduploader|editor|clientadmin',
    'deleteattachment'     => 'uploader|moderateduploader|editor|clientadmin',
    'deletesubtitle'       => 'uploader|moderateduploader|editor|clientadmin',
    'delete'               => 'uploader|moderateduploader|editor|clientadmin',
    'deletecontent'        => 'uploader|moderateduploader|editor|clientadmin',
    'deletecontributor'    => 'uploader|moderateduploader|editor|clientadmin',
    'swapcontributor'      => 'uploader|moderateduploader|editor|clientadmin',
    'checkstreamaccess'    => 'public',
    'securecheckstreamaccess' => 'public',
    'embed'                => 'public',
    'featured'             => 'public',
    'search'               => 'editor|clientadmin',
    'linkcontributor'      => 'uploader|moderateduploader|editor|clientadmin',
    'addtochannel'         => 'editor|clientadmin',
    'removefromchannel'    => 'editor|clientadmin',
    'checkfileresume'      => 'uploader|moderateduploader',
    'uploadchunk'          => 'uploader|moderateduploader',
    'cancelupload'         => 'uploader|moderateduploader',
    // ugyanazok a permissionok mint myrecordingsnal
    'conversioninfo'        => 'uploader|moderateduploader|editor|clientadmin',
    'analytics'            => 'clientadmin',
    'analyticsexport'      => 'clientadmin',
  );

  public $forms = array(
    'upload'               => 'Visitor\\Recordings\\Form\\Upload',
    'uploadcontent'        => 'Visitor\\Recordings\\Form\\Uploadcontent',
    'uploadsubtitle'       => 'Visitor\\Recordings\\Form\\Uploadsubtitle',
    'uploadattachment'     => 'Visitor\\Recordings\\Form\\Uploadattachment',
    'modifybasics'         => 'Visitor\\Recordings\\Form\\Modifybasics',
    'modifyclassification' => 'Visitor\\Recordings\\Form\\Modifyclassification',
    'modifydescription'    => 'Visitor\\Recordings\\Form\\Modifydescription',
    'modifycontributors'   => 'Visitor\\Recordings\\Form\\Modifycontributors',
    'modifysharing'        => 'Visitor\\Recordings\\Form\\Modifysharing',
    'newcomment'           => 'Visitor\\Recordings\\Form\\Newcomment',
    'modifyattachment'     => 'Visitor\\Recordings\\Form\\Modifyattachment',
  );

  public $paging = array(
    'myrecordings' => 'Visitor\\Recordings\\Paging\\Myrecordings',
    'featured'     => 'Visitor\\Recordings\\Paging\\Featured',
  );

  public $apisignature = array(
    'modifyrecording' => array(
      'id' => array(
        'type' => 'id',
      ),
    ),
    'modifyrecordingasuser' => array(
      'id' => array(
        'type' => 'id',
      ),
      'user' => array(
        'type'                     => 'user',
        'permission'               => 'admin',
        'privilege'                => 'recordings_modifyrecordingasuser',
        'impersonatefromparameter' => 'userid',
      ),
    ),
    'addtochannel' => array(
      'recordingid' => array(
        'type' => 'id',
      ),
      'channelid' => array(
        'type' => 'id',
      ),
    ),
    'removefromchannel' => array(
      'recordingid' => array(
        'type' => 'id',
      ),
      'channelid' => array(
        'type' => 'id',
      ),
    ),
    'checkfileresume' => array(
      'name' => array(
        'type' => 'string',
      ),
      'size' => array(
        'type' => 'string',
      ),
    ),
    'checkfileresumeasuser' => array(
      'name' => array(
        'type' => 'string',
      ),
      'size' => array(
        'type' => 'string',
      ),
      'user' => array(
        'type'                     => 'user',
        'permission'               => 'admin',
        'privilege'                => 'recordings_checkfileresumeasuser',
        'impersonatefromparameter' => 'userid',
      ),
    ),
    'uploadchunk' => array(
      'file' => array(
        'type' => 'file',
      ),
      'language' => array(
        'type' => 'string',
      ),
      'encodinggroupid' => array(
        'type' => 'id',
        'required' => false,
      ),
    ),
    'uploadchunkasuser' => array(
      'file' => array(
        'type' => 'file',
      ),
      'language' => array(
        'type' => 'string',
      ),
      'user' => array(
        'type'                     => 'user',
        'permission'               => 'admin',
        'privilege'                => 'recordings_uploadchunkasuser',
        'impersonatefromparameter' => 'userid',
      ),
      'encodinggroupid' => array(
        'type' => 'id',
        'required' => false,
      ),
    ),
    'track' => array(
      'loginrequired' => false,
      'recordingid'   => array(
        'type' => 'id',
      ),
    ),
    'updateposition' => array(
      'loginrequired' => false,
      'recordingid'   => array(
        'type' => 'id',
      ),
      'lastposition' => array(
        'type'     => 'id',
        'required' => false,
      ),
    ),
    'logview' => array(
      'loginrequired' => false,
      'recordingid' => array(
        'type' => 'id',
      ),
      'recordingversionid' => array(
        'type'     => 'id',
        'required' => false,
      ),
      'viewsessionid' => array(
        'type' => 'string',
      ),
      'action' => array(
        'type' => 'string',
      ),
      'streamurl' => array(
        'type' => 'string',
      ),
      'positionfrom' => array(
        'type'     => 'id',
        'required' => false,
      ),
      'positionuntil'=> array(
        'type'     => 'id',
        'required' => false,
      ),
      'useragent' => array(
        'type' => 'string',
        'required' => false,
      ),
    ),
    'checkaccess' => array(
      'loginrequired' => false,
      'recordingid'   => array(
        'type' => 'id',
      ),
      'token'         => array(
        'type' => 'string',
        'required' => false,
      ),
    ),
    'checktimeout' => array(
      'loginrequired' => false,
      'recordingid'   => array(
        'type' => 'id',
      ),
    ),
  );

  public function init() {

    $action = str_replace('submit', '', $this->action );

    if ( $action == 'upload' or $action == 'uploadcontent' )
      $this->bootstrap->setupSession( true );

    parent::init();

  }

  public function indexAction() {
    $this->redirect('recordings/myrecordings');
  }

  public function rateAction() {

    $recordingid = $this->application->getNumericParameter('id');
    $rating      = $this->application->getNumericParameter('rating');
    $result      = array('status' => 'error');


    if ( !$recordingid or $rating < 1 or $rating > 5 ) {

      $result['reason'] = 'invalidparameters';
      $this->jsonOutput( $result );

    }

    $cookiekey = "rra_$recordingid";
    $session = $this->bootstrap->getSession('rating');
    $user    = $this->bootstrap->getSession('user');
    if ( !$this->organization['isanonymousratingenabled'] and !$user['id'] ) {
      $result['reason'] = 'upload_membersonly';
      $this->jsonOutput( $result );
    }

    if (
         $session[ $recordingid ] or
         (
           isset( $_COOKIE[ $cookiekey ] ) and
           $_COOKIE[ $cookiekey ]
         )
       ) {

      $result['reason'] = 'alreadyvoted';
      $this->jsonOutput( $result );

    }

    $recordingsModel = $this->bootstrap->getModel('recordings');
    $recordingsModel->id = $recordingid;

    if ( !$recordingsModel->addRating( $rating ) )
      $this->jsonOutput( $result );

    setcookie( $cookiekey, '1', strtotime('+4 months'), '/' );
    $session[ $recordingid ] = true;
    $result = array(
      'status'          => 'success',
      'rating'          => $recordingsModel->row['rating'],
      'numberofratings' => $recordingsModel->row['numberofratings'],
    );

    $this->jsonOutput( $result );

  }

  public function detailsAction() {

    $recordingsModel = $this->modelOrganizationAndIDCheck(
      'recordings',
      $this->application->getNumericParameter('id')
    );

    $commentspage = $this->application->getNumericParameter('commentspage', -1 );
    $start       = $this->application->getParameter('start');
    $versions    = $recordingsModel->getVersions();
    $browserinfo = $this->bootstrap->getBrowserInfo();
    $user        = $this->bootstrap->getSession('user');
    $rating      = $this->bootstrap->getSession('rating');
    $access      = $this->bootstrap->getSession('recordingaccess');
    $accesskey   = $recordingsModel->id . '-' . (int)$recordingsModel->row['issecurestreamingforced'];

    $access[ $accesskey ] = $recordingsModel->userHasAccess(
      $user, null, $browserinfo['mobile'], $this->organization
    );
    // nem ter vissza ha nincs permission
    $this->handleUserAccess( $access[ $accesskey ] );

    $this->bootstrap->getModel('usercontenthistory')->markRecording(
      $recordingsModel,
      $user,
      $this->organization
    );

    $this->bootstrap->includeTemplatePlugin('indexphoto');

    if ( $user['id'] )
      $this->toSmarty['channels']    = $recordingsModel->getChannelsForUser( $user );

    if (
         $recordingsModel->row['commentsenabled'] and
         ( $user['id'] or $recordingsModel->row['isanonymouscommentsenabled'] )
       )
      $this->toSmarty['commentform'] = $this->getCommentForm()->getHTML();

    if (
         $recordingsModel->row['commentsenabled'] and
         $recordingsModel->row['isanonymouscommentsenabled']
       )
      $this->toSmarty['anonuser']    =
        $this->bootstrap->getSession('recordings-anonuser')->toArray()
      ;

    if ( $recordingsModel->row['commentsenabled'] ) {
      $this->toSmarty['commentoutput'] = $this->getComments(
        $recordingsModel, $commentspage
      );
      $this->toSmarty['commentcount'] = $this->getCommentCount(
        $recordingsModel
      );
    }

    $player = $recordingsModel->getPlayer();
    $this->toSmarty['playercontainerid'] = 'playercontainer';
    if ( $recordingsModel->row['mediatype'] == 'audio' )
      $this->toSmarty['playercontainerid'] .= 'audio';

    $this->toSmarty['browser']       = $this->bootstrap->getBrowserInfo();
    $this->toSmarty['versions']      = $versions;
    $this->toSmarty['ipaddress']     = $this->getIPAddress();
    $this->toSmarty['member']        = $user;
    $this->toSmarty['sessionid']     = session_id();
    $this->toSmarty['needhistory']   = true;
    $this->toSmarty['height']        = $player->getHeight( true );
    $this->toSmarty['recording']     = $recordingsModel->addPresenters( true, $this->organization['id'] );
    $this->toSmarty['attachments']   = $recordingsModel->getAttachments();
    $this->toSmarty['recordingdownloads'] = $recordingsModel->getDownloadInfo(
      $this->bootstrap->staticuri,
      $user,
      $this->organization
    );
    $this->toSmarty['relatedvideos'] = $recordingsModel->getRelatedVideos(
      $this->application->config['relatedrecordingcount'],
      $user,
      $this->organization
    );
    if ( preg_match( '/^\d{1,2}h\d{1,2}m\d{1,2}s$|^\d+$/', $start ) )
      $this->toSmarty['startposition'] = $start;

    $this->toSmarty['playerwidth'] = 980;
    $this->toSmarty['flashplayersubtype'] = '';
    $this->toSmarty['flashplayerparams'] = 'flashdefaults.params';
    $this->toSmarty['playerconfig']  =  $player->getGlobalConfig( $this->toSmarty );

    $this->toSmarty['author']        = $recordingsModel->getAuthor();
    $this->toSmarty['canrate']       = (
      ( $user['id'] or $this->organization['isanonymousratingenabled'] ) and
      ( !$rating[ $recordingsModel->id ] and !@$_COOKIE['rra_' . $recordingsModel->id ] )
    );

    $this->toSmarty['opengraph']     = array(
      'type'        => 'video',
      'image'       => smarty_modifier_indexphoto( $recordingsModel->row, 'player' ),
      'imagetype'   => 'player',
      'description' => $recordingsModel->row['description'],
      'title'       => $recordingsModel->row['title'],
      'subtitle'    => $recordingsModel->row['subtitle'],
      'width'       => 398,
      'height'      => 303,
      'video'       =>
        $this->toSmarty['BASE_URI'] . 'flash/VSQEmbedPlayer.swf?media_json=' .
        rawurlencode(
          $this->toSmarty['BASE_URI'] . \Springboard\Language::get() .
          '/recordings/getplayerconfig/' . $recordingsModel->id
        )
      ,
    );
    $this->toSmarty['metadescription'] = true;

    $quality        = $this->application->getParameter('quality');
    $mobileversion  = null;
    $mobileversions = array();

    foreach( $versions['master']['mobile'] as $version ) {

      if ( $mobileversion === null )
        $mobileversion = $version;

      if ( $quality and $version['qualitytag'] == $quality )
        $mobileversion = $version;

      $mobileversions[] = $version['qualitytag'];

    }

    $this->toSmarty['activemobileversion'] = $mobileversion;
    $this->toSmarty['mobileversions'] = $mobileversions;
    $this->toSmarty['mobilehttpurl'] = $player->getMediaUrl(
      'mobilehttp',
      $mobileversion,
      $this->toSmarty
    );
    $this->toSmarty['mobilertspurl'] = $player->getMediaUrl(
      'mobilertsp',
      $mobileversion,
      $this->toSmarty
    );

    if ( !empty( $versions['audio'] ) )
      $this->toSmarty['audiofileurl']  = $player->getMediaUrl(
        'direct',
        current( $versions['audio'] ),
        $this->toSmarty
      );

    $this->smartyoutput('Visitor/Recordings/Details.tpl');

  }

  public function getplayerconfigAction() {

    $recordingsModel = $this->modelIDCheck(
      'recordings',
      $this->application->getNumericParameter('id')
    );

    $user      = $this->bootstrap->getSession('user');
    $access    = $this->bootstrap->getSession('recordingaccess');
    $accesskey = $recordingsModel->id . '-' . (int)$recordingsModel->row['issecurestreamingforced'];
    $needauth  = false;

    $access[ $accesskey ] = $recordingsModel->userHasAccess( $user, null, false, $this->organization );

    if ( $access[ $accesskey ] === 'registrationrestricted' )
      $needauth = true;

    $this->toSmarty['browser']   = $this->bootstrap->getBrowserInfo();
    $this->toSmarty['member']    = $user;
    $this->toSmarty['ipaddress'] = $this->getIPAddress();
    $this->toSmarty['sessionid'] = session_id();
    $this->toSmarty['attachments'] = $recordingsModel->getAttachments();
    $flashdata = $recordingsModel->getStructuredFlashData( $this->toSmarty );

    if ( $needauth ) {

      $flashdata['authorization']            = array();
      $flashdata['authorization']['need']    = true;

    }

    $flashdata['share']                 = array();
    $flashdata['share']['quickEmbed']   =
      '<iframe width="480" height="303" src="' .
      $this->bootstrap->baseuri . \Springboard\Language::get() . '/recordings/embed/' .
      $recordingsModel->id . '" frameborder="0" allowfullscreen="allowfullscreen"></iframe>'
    ;
    $flashdata['share']['recordingURL'] =
      $this->bootstrap->baseuri . \Springboard\Language::get() . '/recordings/details/' .
      $recordingsModel->id . ',' . \Springboard\Filesystem::filenameize( $recordingsModel->row['title'] )
    ;

    $this->jsonOutput( $this->getSignedPlayerParameters( $flashdata ) );

  }

  public function deleteattachmentAction() {

    $attachmentModel = $this->modelIDCheck(
      'attached_documents',
      $this->application->getNumericParameter('id')
    );

    $this->modelOrganizationAndUserIDCheck(
      'recordings',
      $attachmentModel->row['recordingid']
    );

    $attachmentModel->updateRow( array(
        'status' => 'markedfordeletion',
      )
    );

    $this->redirect(
      $this->application->getParameter('forward', 'recordings/myrecordings')
    );

  }

  public function deletesubtitleAction() {

    $subtitleModel   = $this->modelIDCheck(
      'subtitles',
      $this->application->getNumericParameter('id')
    );

    $this->modelOrganizationAndUserIDCheck(
      'recordings',
      $subtitleModel->row['recordingid']
    );

    $subtitleModel->delete( $subtitleModel->id );
    $this->redirect(
      $this->application->getParameter('forward', 'recordings/myrecordings')
    );

  }

  public function getsubtitleAction() {

    header('Content-Type: text/plain; charset=UTF-8');
    $subtitleid = $this->application->getNumericParameter('id');
    $cache      = $this->bootstrap->getCache('subtitle_' . $subtitleid, null, true );

    if ( $cache->expired() ) {

      $subtitleModel = $this->modelIDCheck('subtitles', $subtitleid );
      $subtitle      = $subtitleModel->row['subtitle'];
      $cache->put( $subtitle );
      unset( $subtitleModel );

    } else
      $subtitle = $cache->get();

    $this->sendheaders = false;
    $this->output( $subtitle, true, true );

  }

  public function deleteAction() {

    $recordingModel = $this->modelOrganizationAndUserIDCheck(
      'recordings',
      $this->application->getNumericParameter('id')
    );

    if ( preg_match( '/^onstorage$|^failed.*$/', $recordingModel->row['status'] ) )
      $recordingModel->markAsDeleted();

    $this->redirect(
      $this->application->getParameter('forward', 'recordings/myrecordings')
    );

  }

  public function deletecontentAction() {

    $recordingModel = $this->modelOrganizationAndUserIDCheck(
      'recordings',
      $this->application->getNumericParameter('id')
    );

    if ( preg_match( '/^onstorage$|^failed.*$/', $recordingModel->row['contentstatus'] ) )
      $recordingModel->markContentAsDeleted();

    $this->redirect(
      $this->application->getParameter('forward', 'recordings/myrecordings')
    );

  }

  public function trackAction( $recordingid ) {

    $recordingModel = $this->modelIDCheck(
      'recordings',
      $recordingid,
      false
    );

    if ( !$recordingModel )
      return false;

    $recordingModel->incrementViewCounters();
    return true;

  }

  public function checkstreamaccessAction( $secure = false ) {

    $ip    = $this->application->getParameter('IP');
    $param = $this->application->getParameter('sessionid');
    $tcurl = $this->application->getParameter('tcurl');

    // nginx rtmp module igy kuldi
    if ( !$param and $tcurl ) {
      $q = parse_url( $tcurl, PHP_URL_QUERY );
      if ( $q ) {
        parse_str( $q, $params );
        if ( isset( $params['sessionid'] ) )
          $param = $params['sessionid'];
      }

      if ( !$ip )
        $ip = $this->application->getParameter('addr');

    }

    $result  = '0';
    $matched =
      preg_match(
        '/^(?P<organizationid>\d+)_' .
        '(?P<sessionid>' . \Springboard\Session::SESSIONID_RE . ')_' .
        '(?P<recordingid>\d+)' .
        '(?:_(?P<token>.+))?$/',
        $param,
        $matches
      )
    ;

    $ips = $this->bootstrap->config['allowedstreamips'];
    if ( $ip and $ips and in_array( $ip, $ips ) ) {

      $result  = '1';
      $matched = false;

    }

    if ( $matched ) {

      $orgModel     = $this->bootstrap->getModel('organizations');
      $organization = $orgModel->getOrganizationByID( $matches['organizationid'] );
      if ( $organization ) {

        $this->impersonateOrganization( $organization );
        $this->bootstrap->setupSession(
          true, $matches['sessionid'], $organization['cookiedomain']
        );

        $this->debugLogUsers();
        $access    = $this->bootstrap->getSession('recordingaccess');
        $accesskey = $matches['recordingid'] . '-' . (int)$secure;

        if ( $access[ $accesskey ] !== true ) {

          $user            = $this->bootstrap->getSession('user');
          $recordingsModel = $this->modelIDCheck(
            'recordings', $matches['recordingid'], false
          );

          if ( $recordingsModel ) {

            $token = null;
            if ( isset( $matches['token'] ) and $matches['token'] )
              $token = $matches['token'];

            $access[ $accesskey ] = $recordingsModel->userHasAccess(
              $user, $secure, false, $organization, $token
            );

            if ( $access[ $accesskey ] === true )
              $result = '1';

          }

        } else
          $result = '1';

      }

    }

    if ( $this->bootstrap->config['checkaccessdebuglog'] )
      \Springboard\Debug::getInstance()->log(
        false,
        'recordingcheckaccessdebug.txt',
        "SECURE: $secure | RESULT: $result\n" .
        \Springboard\Debug::getRequestInformation(2) . "\n"
      );

    if ( !$result )
      header('HTTP/1.1 403 Forbidden');

    echo
      '<?xml version="1.0"?>
      <result>
        <success>' . $result . '</success>
      </result>'
    ;

    die();

  }

  public function securecheckstreamaccessAction() {
    return $this->checkstreamaccessAction( true );
  }

  public function modifyrecordingAction( $id ) {

    $recordingModel = $this->modelUserAndIDCheck('recordings', $id, false );

    if ( !$recordingModel )
      throw new \Exception('No recording found with that ID');

    $values = $this->application->getParameters();
    unset( // TODO inkabb whitelistet mint blacklistet
      $values['id'],
      $values['format'],
      $values['layer'],
      $values['method'],
      $values['module'],
      $values['language'],
      $values['_module']
    );

    $recordingModel->updateRow( $values );
    return $recordingModel->row;

  }

  public function modifyrecordingasuserAction( $id ) {
    return $this->modifyrecordingAction( $id );
  }

  public function embedAction() {

    $recordingsModel = $this->modelIDCheck(
      'recordings',
      $this->application->getNumericParameter('id')
    );

    $start        = $this->application->getParameter('start');
    $autoplay     = $this->application->getParameter('autoplay');
    $fullscale    = $this->application->getParameter('fullscale');
    $skipcontent  = $this->application->getParameter('skipcontent');
    $token        = $this->application->getParameter('token', null );
    $versions     = $recordingsModel->getVersions();
    $browserinfo  = $this->bootstrap->getBrowserInfo();
    $user         = $this->bootstrap->getSession('user');
    $access       = $this->bootstrap->getSession('recordingaccess');
    $accesskey    = $recordingsModel->id . '-' . (int)$recordingsModel->row['issecurestreamingforced'];
    $needauth     = false;
    $nopermission = false;
    $tokenValid   = false;
    $l            = $this->bootstrap->getLocalization();

    $access[ $accesskey ] = $recordingsModel->userHasAccess(
      $user, null, false, $this->organization, $token
    );

    if (
         in_array( $access[ $accesskey ], array(
             'registrationrestricted',
             'departmentorgrouprestricted',
           ), true // strict = true
         )
       )
      $needauth = true;
    elseif ( $access[ $accesskey ] !== true )
      $nopermission = true;

    if ( $access[ $accesskey ] !== 'tokeninvalid' )
      $tokenValid = true;

    // hozzaferunk, log
    if ( !$needauth and !$nopermission and $tokenValid )
      $this->bootstrap->getModel('usercontenthistory')->markRecording(
        $recordingsModel,
        $user,
        $this->organization
      );

    $this->toSmarty['playercontainerid'] = 'vsq_' . rand();
    $this->toSmarty['flashplayersubtype'] = '';
    $this->toSmarty['flashplayerparams'] = 'flashdefaults.params';
    $this->toSmarty['browser']       = $this->bootstrap->getBrowserInfo();
    $this->toSmarty['versions']      = $versions;
    $this->toSmarty['needauth']      = $needauth;
    $this->toSmarty['tokenvalid']    = $tokenValid;
    $this->toSmarty['nopermission']  = $nopermission;
    $this->toSmarty['ipaddress']     = $this->getIPAddress();
    $this->toSmarty['member']        = $user;
    $this->toSmarty['sessionid']     = session_id();
    $this->toSmarty['attachments']   = $recordingsModel->getAttachments();
    $this->toSmarty['autoplay']      = $autoplay;
    if ( preg_match( '/^\d{1,2}h\d{1,2}m\d{1,2}s$|^\d+$/', $start ) )
      $this->toSmarty['startposition'] = $start;

    if ( $skipcontent )
      $this->toSmarty['skipcontent'] = true;

    // kikapcsolni az ajanlot ha token auth van
    $this->toSmarty['tokenauth'] = $token and $tokenValid;
    $this->toSmarty['token'] = $token;
    $this->toSmarty['logo'] = array(
      'url' => $this->toSmarty['STATIC_URI'] . 'images/player_overlay_logo.png',
      'destination' => '',
    );

    if ( $this->organization['isplayerlogolinkenabled'] )
      $this->toSmarty['logo']['destination'] =
        $this->toSmarty['BASE_URI'] . \Springboard\Language::get() .
        '/recordings/details/' . $recordingsModel->id . ',' .
        \Springboard\Filesystem::filenameize( $recordingsModel->row['title'] )
      ;

    $player = $recordingsModel->getPlayer();
    $playerdata = $player->getGlobalConfig( $this->toSmarty, true );
    unset(
      $this->toSmarty['logo']
    );

    $quality        = $this->application->getParameter('quality');
    $mobileversion  = array_pop( $versions['master']['mobile'] );
    $mobileversions = array();

    foreach( $versions['master']['mobile'] as $version ) {

      if ( $quality and $version['qualitytag'] == $quality )
        $mobileversion = $version;

      $mobileversions[] = $version['qualitytag'];

    }

    $this->toSmarty['mobileversions'] = $mobileversions;
    $this->toSmarty['mobilehttpurl'] = $player->getMediaUrl(
      'mobilehttp',
      $mobileversion,
      $this->toSmarty
    );
    $this->toSmarty['mobilertspurl'] = $player->getMediaUrl(
      'mobilertsp',
      $mobileversion,
      $this->toSmarty
    );

    if ( !empty( $versions['audio'] ) )
      $this->toSmarty['audiofileurl']  = $player->getMediaUrl(
        'direct',
        current( $versions['audio'] ),
        $this->toSmarty
      );

    if ( $fullscale )
      $this->toSmarty['playerwidth'] = '950';
    else
      $this->toSmarty['playerwidth'] = '480';

    $this->toSmarty['playerheight'] = $player->getHeight( $fullscale );
    $this->toSmarty['recording']    = $recordingsModel->row;
    $this->toSmarty['playerconfig'] = $playerdata;

    if ($this->organization['ondemandplayertype'] == "flash" )
      $this->smartyoutput('Visitor/Recordings/EmbedFlash.tpl');
    else if ($this->organization['ondemandplayertype'] == "flowplayer" )
      $this->smartyoutput('Visitor/Recordings/EmbedFlow.tpl');
    else
      throw new \Exception("Unknown ondemandplayertype ${$this->organization['ondemandplayertype']}");
  }

  public function addtochannelAction( $recordingid = null, $channelid = null ) {

    if ( $recordingid and $channelid )
      $api = true;
    else {

      $recordingid = $this->application->getNumericParameter('id');
      $channelid   = $this->application->getNumericParameter('channel');
      $api         = false;

    }

    $user            = $this->bootstrap->getSession('user');
    $recordingsModel = $this->checkOrganizationAndUseridWithApi( $api, 'recordings', $recordingid );
    $channelsModel   = $this->checkOrganizationAndUseridWithApi( $api, 'channels', $channelid );

    $recordingsModel->addToChannel( $channelsModel->id, $user );

    $this->toSmarty['level']     = 1;
    $this->toSmarty['recording'] = $recordingsModel->row;
    $this->toSmarty['channels']  = $recordingsModel->getChannelsForUser( $user );

    if ( !$api )
      $this->jsonOutput( array(
          'status' => 'success',
          'html'   => $this->fetchSmarty('Visitor/Recordings/Details_channels.tpl'),
        )
      );
    else
      return true;

  }

  public function removefromchannelAction( $recordingid = null, $channelid = null ) {

    if ( $recordingid and $channelid )
      $api = true;
    else {

      $recordingid = $this->application->getNumericParameter('id');
      $channelid   = $this->application->getNumericParameter('channel');
      $api         = false;

    }

    $user            = $this->bootstrap->getSession('user');
    $recordingsModel = $this->checkOrganizationAndUseridWithApi( $api, 'recordings', $recordingid );
    $channelsModel   = $this->checkOrganizationAndUseridWithApi( $api, 'channels', $channelid );

    $recordingsModel->removeFromChannel( $channelsModel->id, $user );

    $this->toSmarty['level']     = 1;
    $this->toSmarty['recording'] = $recordingsModel->row;
    $this->toSmarty['channels']  = $recordingsModel->getChannelsForUser( $user );

    if ( !$api )
      $this->jsonOutput( array(
          'status' => 'success',
          'html'   => $this->fetchSmarty('Visitor/Recordings/Details_channels.tpl'),
        )
      );
    else
      return true;

  }

  public function checkfileresumeasuserAction( $file, $userid ) {
    return $this->checkfileresumeAction();
  }

  public function checkfileresumeAction() {

    $filename  = trim( $this->application->getParameter('name') );
    $filesize  = $this->application->getNumericParameter('size', null, true );
    $user      = $this->bootstrap->getSession('user');

    if ( !$filename or !$filesize )
      $this->jsonOutput( array('status' => 'error') );

    $info        = array(
      'filename'  => $filename,
      'filesize'  => $filesize,
      'iscontent' => $this->application->getNumericParameter('iscontent', 0 ),
      'userid'    => $user['id'],
    );
    $uploadModel = $this->bootstrap->getModel('uploads');
    if ( !$uploadModel->isUploadingAllowed() )
      $this->jsonOutput( array('status' => 'error', 'reason' => 'notenoughspace') );

    $data        = $uploadModel->getFileResumeInfo( $info );

    if ( empty( $data ) )
      $startfromchunk = 0;
    else
      $startfromchunk = $data['currentchunk'] + 1;

    $this->jsonOutput( array(
        'status'         => 'success',
        'startfromchunk' => $startfromchunk,
      )
    );

  }


  public function uploadchunkasuserAction() {
    return $this->uploadchunkAction();
  }

  public function uploadchunkAction() {

    if ( $this->bootstrap->inMaintenance('upload') )
      $this->jsonOutput( array('status' => 'error', 'error'  => 'upload_unknownerror') );

    if (
         !isset( $_REQUEST['name'] ) or
         (
           isset( $_REQUEST['chunks'] ) and
           !intval( $_REQUEST['chunks'] )
         )
       ) {

      $this->chunkResponseAndLog( array(
          'status' => 'error',
          'error'  => 'upload_uploaderror',
        ), 'Parameter validation failed: ' . var_export( $_REQUEST, true )
      );

    }

    if ( !isset( $_FILES['file'] ) ) {

      $data = file_get_contents("php://input");
      if ( strlen( $data ) != $_SERVER['CONTENT_LENGTH'] )
        $this->chunkResponseAndLog( array(
            'status' => 'error',
            'error'  => 'upload_uploaderror',
          ), 'Data length was not the same as the content_length!'
        );

      $file = array(
        'tmp_name' => tempnam( null, 'uploadchunk_'),
      );

      if ( !file_put_contents( $file['tmp_name'], $data ) )
        $this->chunkResponseAndLog( array(
            'status' => 'error',
            'error'  => 'upload_uploaderror',
          ), 'Could not move temporary file!'
        );

      unset( $data );

    } elseif ( $_FILES['file']['error'] != 0 )
      $this->chunkResponseAndLog( array(
          'status' => 'error',
          'error'  => 'upload_uploaderror',
        ), 'Upload error: $_FILES: ' . var_export( $_FILES, true )
      );
    else
      $file = $_FILES['file'];

    $encodinggroupid = $this->application->getNumericParameter('encodinggroupid');
    $filename    = trim( $this->application->getParameter('name') );
    $chunk       = $this->application->getNumericParameter('chunk');
    $chunks      = $this->application->getNumericParameter('chunks');
    $filesize    = $this->application->getNumericParameter('size', null, true ); // float hogy beleferjen nagy szam
    if ( $filesize < 0 )
      $this->chunkResponseAndLog( array(
          'status' => 'error',
          'error'  => 'upload_uploaderror',
        ), 'Upload error, filesize was negative: $_FILES: ' . var_export( $_FILES, true )
      );

    $uploadModel = $this->bootstrap->getModel('uploads');
    $user        = $this->bootstrap->getSession('user');
    $iscontent   = (bool)$this->application->getNumericParameter('iscontent');
    $isintrooutro= (bool)$this->application->getNumericParameter('isintrooutro');
    $chunkinfo   = array(
      'filename'  => $filename,
      'filesize'  => $filesize,
      'iscontent' => $iscontent,
      'userid'    => $user['id'],
    );
    $info        = $uploadModel->getFileResumeInfo( $chunkinfo );

    if ( !$chunks ) // nem kotelezo, nem lesz megadva ha a file merete kisebb mint a chunk merete
      $chunks = 1;

    if (
         !empty( $info ) and
         $info['chunkcount'] == $chunks and // sanity checks
         $chunk == ( $info['currentchunk'] + 1 )
       ) {

      $sleptfor = 0;
      while ( $info['status'] == 'handlechunk' and $sleptfor < 30 ) {

        sleep(1);
        $info = $uploadModel->getFileResumeInfo( $chunkinfo );
        $sleptfor++;

      }

      if ( $info['status'] == 'handlechunk' ) {

        header('HTTP/1.1 500 Internal Server Error');
        $this->chunkResponseAndLog( array(
            'status' => 'error',
            'error'  => 'upload_unknownerror',
          ), 'After 30 seconds, upload is in status=handlechunk! info: ' . var_export( $info , true ),
          true
        );

      }

      $uploadModel->id  = $info['id'];
      $uploadModel->row = $info;
      $uploadModel->handleChunk( $file['tmp_name'] );

    } elseif ( $chunk == 0 ) {
      if ( !$uploadModel->isUploadingAllowed() )
        $this->chunkResponseAndLog( array(
            'status' => 'error',
            'error'  => 'upload_unknownerror',
          ), 'info: uploadModel->isUploadingAllowed() -> false',
          false
        );

      $uploadModel->insert( array(
          'recordingid'  => $this->application->getNumericParameter('id', 0 ),
          'iscontent'    => (int)$iscontent,
          'filename'     => $filename,
          'currentchunk' => $chunk,
          'chunkcount'   => $chunks,
          'size'         => $filesize,
          'userid'       => $user['id'],
          'status'       => 'handlechunk',
          'timestamp'    => date('Y-m-d H:i:s'),
        )
      );

      $uploadModel->handleChunk( $file['tmp_name'] );
      $uploadModel->updateRow( array(
          'status' => 'uploading',
        )
      );

      @unlink( $file['tmp_name'] );
      $info = $uploadModel->row;

    } else {

      // a chunk nem vart sorrendben erkezett, nem tudunk vele kezdeni semmit
      $this->jsonOutput( array(
          'status' => 'error',
          'error'  => 'upload_unknownerror',
        )
      );

    }

    // chunk count is 0 based, vegso chunk, handleChunk mar lefutott
    if ( $chunk + 1 == $chunks ) {

      // ha ez elfailel akkor nagyobb hibak vannak
      // muszaj megegyeznie az adatbazisban tarolt file meretnek
      // a konkret file meretevel
      if ( !$uploadModel->filesizeMatches() ) {
        $uploadModel->updateRow( array(
            'status'       => 'error_filesizemismatch',
          )
        );

        $this->chunkResponseAndLog( array(
            'status' => 'error',
            'error'  => 'upload_unknownerror',
          ), 'info: uploadModel->filesizeMatches() -> false'
        );
      }

      $filepath = $uploadModel->getChunkPath();
      $uploadModel->updateRow( array(
          'currentchunk' => $chunk,
          'status'       => 'completed',
        )
      );
      @unlink( $file['tmp_name'] );
      $info = array(
        'iscontent'  => $iscontent,
        'handlefile' => 'rename',
        'filepath'   => $filepath,
        'filename'   => $info['filename'],
        'user'       => $user,
        'isintrooutro' => $isintrooutro,
        'encodinggroupid' => $encodinggroupid,
      );

      try {

        if ( $info['iscontent'] ) {

          $recordingModel = $this->modelOrganizationAndUserIDCheck(
            'recordings',
            $this->application->getNumericParameter('id'), // recordingid
            false
          );

          if ( !$recordingModel )
            $this->chunkResponseAndLog( array(
                'error' => 'upload_membersonly',
              )
            );

        } else {

          $recordingModel = $this->bootstrap->getModel('recordings');
          $languageModel  = $this->bootstrap->getModel('languages');
          $languages      = $languageModel->getAssoc('id', 'shortname', false, false, false, 'weight');
          $language       = $this->application->getNumericParameter('videolanguage');
          $textlanguage   = $this->application->getParameter('textlanguage');

          if ( !$language and $textlanguage ) {

            foreach( $languages as $id => $lang ) {

              if ( $lang == $textlanguage ) {

                $language = $id;
                break;

              }

            }

          }

          if ( !isset( $languages[ $language ] ) )
            $this->jsonOutput( array('status' => 'error', 'error' => 'upload_securityerror') );

          $info['language'] = $language;

        }

        $recordingModel->upload( $info );
        $uploadModel->updateRow( array(
            'recordingid' => $recordingModel->id,
          )
        );
        $channelid = $this->application->getNumericParameter('channelid');
        if ( !$info['iscontent'] and $channelid ) {

          $channelModel = $this->modelOrganizationAndUserIDCheck(
            'channels',
            $channelid,
            false
          );

          if ( !$channelModel ) {

            $error = 'upload_invalidchannel';
            $message = $l('recordings', 'invalidchannel');

          } else
            $channelModel->insertIntoChannel( $recordingModel->id, $user );

        }

      } catch( \Model\InvalidFileTypeException $e ) {
        $error   = 'upload_invalidfiletype';
        $message = $e->getMessage();
      } catch( \Model\InvalidLengthException $e ) {
        $error   = 'upload_invalidlength';
        $message = $e->getMessage();
      } catch( \Model\InvalidVideoResolutionException $e ) {
        $error   = 'upload_recordingtoobig';
        $message = $e->getMessage();
      } catch( \Model\InvalidException $e ) {
        $error   = 'upload_failedvalidation';
        $message = $e->getMessage();
      } catch( \Exception $e ) {
        $error   = 'upload_unkownerror';
        $message = $e->getMessage();
      }

      if ( isset( $error ) )
        $this->chunkResponseAndLog( array(
            'status' => 'error',
            'error'  => $error
          ),
          "Recording upload (iscontent: $iscontent) failed with exception message: $message \n\n" .
          'Metadata: ' . var_export( $recordingModel->metadata, true )
        );

      if ( $iscontent )
        $url = $this->getUrlFromFragment('contents/uploadcontentsuccessfull');
      else
        $url = $this->getUrlFromFragment('contents/uploadsuccessfull');

      $this->jsonOutput( array(
          'status' => 'success',
          'url'    => $url,
          'id'     => $recordingModel->id,
        )
      );

    } else {

      $uploadModel->updateRow( array(
          'status'       => 'uploading',
          'currentchunk' => $chunk,
        )
      );
      @unlink( $file['tmp_name'] );

      $this->jsonOutput( array(
          'status' => 'continue',
        )
      );

    }

    $this->jsonOutput( array(
        'status' => 'continue',
      )
    );

  }

  protected function chunkResponseAndLog( $response, $log = false, $shouldemail = false ) {

    if ( $log ) {

      $log  .= "\n" . var_export( $_SERVER, true );
      $debug = \Springboard\Debug::getInstance();
      $debug->log( false, 'chunkupload.txt', $log, $shouldemail );

    }

    $this->jsonOutput( $response, true );

  }

  public function canceluploadAction() {
    $uploadModel = $this->modelUserAndIDCheck(
      'uploads',
      $this->application->getNumericParameter('id')
    );

    if (
         $uploadModel->row['status'] != 'uploading' and
         $uploadModel->row['status'] != 'handlechunk'
       )
      $this->jsonOutput( array('status' => 'error', 'message' => 'Wrong status') );

    $uploadModel->updateRow( array(
        'status' => 'markedfordeletion',
      )
    );

    $this->redirect(
      $this->application->getParameter('forward')
    );
  }

  public function linkcontributorAction() {

    $recordingsModel   = $this->modelOrganizationAndUserIDCheck(
      'recordings',
      $this->application->getNumericParameter('id')
    );
    $contributorModel = $this->modelOrganizationAndIDCheck(
      'contributors',
      $this->application->getNumericParameter('contributorid')
    );
    $roleModel = $this->modelOrganizationAndIDCheck(
      'roles',
      $this->application->getNumericParameter('contributorrole')
    );

    $recordingsModel->linkContributor( array(
        'contributorid'  => $contributorModel->id,
        'organizationid' => $this->organization['id'],
        'roleid'         => $roleModel->id,
      )
    );

    $this->toSmarty['contributors'] = $recordingsModel->getContributorsWithRoles();
    $this->toSmarty['recordingid']  = $recordingsModel->id;
    $this->jsonOutput( array(
        'status' => 'OK',
        'html'   => $this->fetchSmarty('Visitor/Recordings/Contributors.tpl'),
      )
    );

  }

  public function deletecontributorAction() {

    $contribroleModel = $this->modelIDCheck(
      'contributors_roles',
      $this->application->getNumericParameter('id')
    );
    $recordingsModel  = $this->modelOrganizationAndUserIDCheck(
      'recordings',
      $contribroleModel->row['recordingid']
    );

    $contribroleModel->delete( $contribroleModel->id );

    $this->toSmarty['contributors'] = $recordingsModel->getContributorsWithRoles();
    $this->toSmarty['recordingid']  = $recordingsModel->id;
    $this->jsonOutput( array(
        'status' => 'OK',
        'html'   => $this->fetchSmarty('Visitor/Recordings/Contributors.tpl'),
      )
    );

  }

  public function swapcontributorAction() {

    $whatcontribroleModel = $this->modelIDCheck(
      'contributors_roles',
      $this->application->getNumericParameter('what')
    );
    $wherecontribroleModel = $this->modelIDCheck(
      'contributors_roles',
      $this->application->getNumericParameter('where')
    );

    if (
         $whatcontribroleModel->row['recordingid'] != $wherecontribroleModel->row['recordingid'] or
         $whatcontribroleModel->row['weight'] == $wherecontribroleModel->row['weight']
       )
      $this->redirect();

    $recordingsModel  = $this->modelOrganizationAndUserIDCheck(
      'recordings',
      $whatcontribroleModel->row['recordingid']
    );

    $whatweight = $whatcontribroleModel->row['weight'];
    $whatcontribroleModel->updateRow( array(
        'weight' => $wherecontribroleModel->row['weight'],
      )
    );
    $wherecontribroleModel->updateRow( array(
        'weight' => $whatweight,
      )
    );

    $this->toSmarty['contributors'] = $recordingsModel->getContributorsWithRoles();
    $this->toSmarty['recordingid']  = $recordingsModel->id;
    $this->jsonOutput( array(
        'status' => 'OK',
        'html'   => $this->fetchSmarty('Visitor/Recordings/Contributors.tpl'),
      )
    );

  }

  public function checktimeoutAction( $recordingid ) {
    $user            = $this->bootstrap->getSession('user');
    $recordingsModel = $this->modelIDCheck(
      'recordings',
      $recordingid,
      false
    );

    if ( !$user or !$user['id'] )
      throw new \Visitor\Api\ApiException('User not logged in', false, false );

    if ( !$recordingsModel )
      throw new \Visitor\Api\ApiException('Recording not found', false, false );

    return !$recordingsModel->checkViewProgressTimeout( $this->organization, $user['id'] );
  }

  public function updatepositionAction( $recordingid, $lastposition ) {

    $d = \Springboard\Debug::getInstance();
    $user            = $this->bootstrap->getSession('user');
    $recordingsModel = $this->modelIDCheck(
      'recordings',
      $recordingid,
      false
    );

    $d->log(
      false,
      'updateposition.txt',
      'recordingid: ' . $recordingid .
      ' foundrecording: ' . ( !!$recordingsModel ? 'true' : 'false' ) .
      ' position: ' . $lastposition
    );

    if ( !$user or !$user['id'] )
      throw new \Visitor\Api\ApiException('User not logged in', false, false );

    if ( !$recordingsModel )
      throw new \Visitor\Api\ApiException('Recording not found', false, false );

    $ret = $recordingsModel->updateLastPosition(
      $this->organization, $user['id'], $lastposition, session_id()
    );

    return $ret;

  }

  public function searchAction() {

    $term   = $this->application->getParameter('term');
    $output = array(
    );

    if ( !$term )
      $this->jsonoutput( $output );

    $user           = $this->bootstrap->getSession('user');
    $recordingModel = $this->bootstrap->getModel('recordings');
    $results        = $recordingModel->search( $term, $user['id'], $this->organization['id'] );

    if ( empty( $results ) )
      $this->jsonoutput( $output );

    foreach( $results as $result ) {

      $title = $result['title'];
      if ( strlen( trim( $result['subtitle'] ) ) )
        $title .= '<br/>' . $result['subtitle'];

      $data = array(
        'value' => $result['id'],
        'label' => $title,
        'img'   => $this->bootstrap->staticuri,
      );

      if ( $result['indexphotofilename'] )
        $data['img'] .= 'files/' . $result['indexphotofilename'];
      else
        $data['img'] .= 'images/videothumb_audio_placeholder.png';

      $output[] = $data;

    }

    $this->jsonoutput( $output );

  }

  public function getCommentForm() {

    $l    = $this->bootstrap->getLocalization();
    $form = $this->bootstrap->getForm('recordings_newcomment');

    $form->submit = $l('recordings', 'submit_comment');
    $form->formopenlayout =
      '<form enctype="multipart/form-data" target="%target%" ' .
      'id="%id%" name="%name%" action="%action%" method="%method%">'
    ;

    $form->jspath =
      $this->toSmarty['STATIC_URI'] . 'js/clonefish.js'
    ;
    $form->layouts['tabular']['container']  =
      "<table cellpadding=\"0\" cellspacing=\"0\">\n%s\n</table>\n"
    ;

    $form->layouts['tabular']['element'] =
      '<tr %errorstyle%>' .
        '<td class="labelcolumn">' .
          '<label for="%id%">%displayname%</label>' .
        '</td>' .
        '<td class="elementcolumn">%prefix%%element%%postfix%%errordiv%</td>' .
      '</tr>'
    ;

    $form->layouts['tabular']['buttonrow'] =
      '<tr class="buttonrow"><td>%s</td></tr>'
    ;

    $form->layouts['tabular']['button'] =
      '<input type="submit" value="%s" class="submitbutton" />'
    ;

    $form->layouts['rowbyrow']['errordiv'] =
      '<div id="%divid%" style="display: none; visibility: hidden; ' .
      'padding: 2px 5px 2px 5px; background-color: #d03030; color: white;' .
      'clear: both;"></div>'
    ;

    include(
      $this->bootstrap->config['modulepath'] .
      'Visitor/Recordings/Form/Configs/Newcomment.php'
    );

    $form->addElements( $config, false, false );
    return $form;
  }

  public function moderatecommentAction() {

    $recordingsModel = $this->modelOrganizationAndUserIDCheck(
      'recordings',
      $this->application->getNumericParameter('id')
    );

  }

  public function getComments( $recordingsModel, $page ) {

    $l         = $this->bootstrap->getLocalization();
    $pagecount = $recordingsModel->getCommentsPageCount(
      $this->commentsperpage
    );

    if ( $page < 1 or $page > $pagecount )
      $page = $pagecount;

    $this->toSmarty['maxpages']     = 4;
    $this->toSmarty['activepage']   = $page?: 1; // egy oldalnak mindig lennie kell
    $this->toSmarty['pagecount']    = $pagecount?: 1;
    $this->toSmarty['recording']    = $recordingsModel->row;
    $this->toSmarty['comments']     = $recordingsModel->getCommentsPage(
      $this->commentsperpage, $page
    );

    return array(
      'html'       => $this->fetchSmarty('Visitor/Recordings/Comments.tpl'),
    );

  }

  private function getCommentCount( $recordingsModel ) {
    return $recordingsModel->getCommentsCount();
  }

  public function getcommentsAction() {

    $recordingid = $this->application->getNumericParameter('id');
    $page        = $this->application->getNumericParameter('page');

    if ( $recordingid <= 0 )
      $this->redirect('index');

    $recordingsModel = $this->modelIDCheck(
      'recordings', $recordingid
    );

    if ( !$recordingsModel->row['commentsenabled'] )
      $this->jsonOutput( array('success' => false) );

    $comments = $this->getComments( $recordingsModel, $page );
    $this->jsonOutput( $comments );

  }

  public function logviewAction( $recordingid, $recordingversionid, $viewsessionid, $action, $streamurl, $positionfrom = null, $positionuntil = null, $useragent = '' ) {

    $statModel = $this->bootstrap->getModel('view_statistics_ondemand');
    $user      = $this->bootstrap->getSession('user');
    $ipaddress = $this->getIPAddress();
    $sessionid = session_id();
    $useragent .= " " . $_SERVER['HTTP_USER_AGENT'];
    $useragent = str_replace( array("\r", "\n"), " ", $useragent );

    $values = array(
      'userid'             => $user['id'],
      'recordingid'        => $recordingid,
      'recordingversionid' => $recordingversionid,
      'sessionid'          => $sessionid,
      'viewsessionid'      => $viewsessionid,
      'action'             => $action,
      'url'                => $streamurl,
      'ipaddress'          => $ipaddress,
      'useragent'          => $useragent,
      'positionfrom'       => $positionfrom,
      'positionuntil'      => $positionuntil,
    );

    $statModel->log( $values );
    return true;

  }

  public function checkaccessAction( $recordingid, $token ) {
    $browserinfo = $this->bootstrap->getBrowserInfo();
    $user        = $this->bootstrap->getSession('user');
    $ret         = array(
      'hasaccess' => false,
    );

    $recordingsModel = $this->bootstrap->getModel('recordings');
    $recordingsModel->select( $recordingid );

    if ( !$recordingsModel->row )
      return $ret;

    if ( !$token )
      $token = null;

    $access = $recordingsModel->userHasAccess(
      $user, null, $browserinfo['mobile'], $this->organization, $token
    );

    if ( $access === true )
      $ret['hasaccess'] = true;
    else {
      $ret['hasaccess'] = false;
      $ret['reason']    = $access;
    }

    return $ret;
  }

  public function conversioninfoAction() {
    if ( !isset( $_REQUEST['ids'] ) or !is_array( $_REQUEST['ids'] ) )
      $this->jsonOutput( array('success' => false, 'reason' => 'wrong ids passed'));

    // sanitize
    $ids = array();
    foreach( $_REQUEST['ids'] as $value ) {
      $id = intval( $value );
      if ( $id > 0 )
        $ids[] = $id;
    }
    $ids = array_unique( $ids );

    $recModel = $this->bootstrap->getModel('recordings');
    $info = $recModel->getConversionInformation(
      $ids,
      $this->bootstrap->getSession('user'),
      $this->organization['id']
    );

    $this->jsonOutput( array(
        'success' => true,
        'data'    => $info,
      )
    );
  }

  public function analyticsAction() {
    $recordingsModel = $this->modelOrganizationAndUserIDCheck(
      'recordings',
      $this->application->getNumericParameter('id')
    );
    $l = $this->bootstrap->getLocalization();

    $data = array(
      'data'   => $recordingsModel->getSegmentAnalytics(),
      'labels' => array(
        $l('recordings', 'analytics_timestamp'),
        $l('recordings', 'analytics_views'),
      ),
    );

    $defaultBack = \Springboard\Language::get() . '/recordings/myrecordings';

    $back = $this->application->getParameter('forward', $defaultBack );
    $info = parse_url( $back );
    if (
         isset( $info['host'] ) and
         $info['host'] !== $this->organization['domain']
       )
      $back = $defaultBack;

    $this->toSmarty['back']          = $back;
    $this->toSmarty['recording']     = $recordingsModel->row;
    $this->toSmarty['analyticsdata'] = $data;
    $this->toSmarty['needanalytics'] = true;
    $this->smartyOutput('Visitor/Recordings/Analytics.tpl');
  }

  public function analyticsexportAction() {
    $recordingsModel = $this->modelOrganizationAndUserIDCheck(
      'recordings',
      $this->application->getNumericParameter('id')
    );

    $segments = $recordingsModel->getSegmentAnalytics();

    $delim = ';';
    $filename =
      'videosquare-recordingstatistics-' .
      $recordingsModel->id . '_' . \Springboard\Filesystem::filenameize(
        mb_substr( $recordingsModel->row['title'], 0, 20 )
      ) . '-' .
      date('YmdHis') . '.csv'
    ;

    $header = array(
      'timestamp' => 'timestampSeconds',
      'views'     => 'numberOfViews',
    );

    $f = \Springboard\Browser::initCSVHeaders(
      $filename,
      array_values( $header ),
      $delim
    );

    foreach( $segments as $segment ) {
      $data = array(
        'timestamp' => $segment['timestamp'],
        'views'     => $segment['views'],
      );
      fputcsv( $f, array_values( $data ), $delim );
    }

    fclose( $f );
    die();
  }
}
