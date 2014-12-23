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
    'newcomment'           => 'member',
    'moderatecomment'      => 'uploader|moderateduploader|editor|clientadmin',
    'rate'                 => 'member',
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
    'addtochannel'         => 'member',
    'removefromchannel'    => 'member',
    'checkfileresume'      => 'uploader|moderateduploader',
    'uploadchunk'          => 'uploader|moderateduploader',
    'cancelupload'         => 'uploader|moderateduploader',
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
        'impersonatefromparameter' => 'userid',
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
    
    $session = $this->bootstrap->getSession('rating');
    if ( $session[ $recordingid ] ) {
      
      $result['reason'] = 'alreadyvoted';
      $this->jsonOutput( $result );
      
    }
    
    $recordingsModel = $this->bootstrap->getModel('recordings');
    $recordingsModel->id = $recordingid;
    
    if ( !$recordingsModel->addRating( $rating ) )
      $this->jsonOutput( $result );
    
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
    $this->handleUserAccess( $access[ $accesskey ] );
    
    include_once(
      $this->bootstrap->config['templatepath'] .
      'Plugins/modifier.indexphoto.php'
    );
    
    if ( $user['id'] ) {
      $this->toSmarty['channels']    = $recordingsModel->getChannelsForUser( $user );
      $this->toSmarty['commentform'] = $this->getCommentForm()->getHTML();
    }
    
    $this->toSmarty['commentoutput'] = $this->getComments(
      $recordingsModel, $commentspage
    );
    $this->toSmarty['ipaddress']     = $this->getIPAddress();
    $this->toSmarty['member']        = $user;
    $this->toSmarty['sessionid']     = session_id();
    $this->toSmarty['needping']      = true;
    $this->toSmarty['needhistory']   = true;
    $this->toSmarty['height']        = $this->getPlayerHeight( $recordingsModel );
    $this->toSmarty['recording']     = $recordingsModel->addPresenters( true, $this->organization['id'] );
    $this->toSmarty['recordingdownloads'] = $recordingsModel->getDownloadInfo(
      $this->bootstrap->staticuri
    );
    $this->toSmarty['relatedvideos'] = $recordingsModel->getRelatedVideos(
      $this->application->config['relatedrecordingcount'],
      $user,
      $this->organization
    );

    $flashdata = $recordingsModel->getFlashData( $this->toSmarty );
    if ( preg_match( '/^\d{1,2}h\d{1,2}m\d{1,2}s$|^\d+$/', $start ) )
      $flashdata['timeline_startPosition'] = $start;

    $this->toSmarty['flashdata']     = $this->getFlashParameters( $flashdata );
    $this->toSmarty['author']        = $recordingsModel->getAuthor();
    $this->toSmarty['attachments']   = $recordingsModel->getAttachments();
    $this->toSmarty['canrate']       = ( $user['id'] and !$rating[ $recordingsModel->id ] );
    
    $this->toSmarty['opengraph']     = array(
      'type'        => 'video',
      'image'       => smarty_modifier_indexphoto( $recordingsModel->row, 'player' ),
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
    $this->toSmarty['mobilehttpurl'] = $recordingsModel->getMediaUrl(
      'mobilehttp',
      $mobileversion,
      $this->toSmarty
    );
    $this->toSmarty['mobilertspurl'] = $recordingsModel->getMediaUrl(
      'mobilertsp',
      $mobileversion,
      $this->toSmarty
    );

    if ( !empty( $versions['audio'] ) )
      $this->toSmarty['audiofileurl']  = $recordingsModel->getMediaUrl(
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
    
    $this->toSmarty['member']    = $user;
    $this->toSmarty['ipaddress'] = $this->getIPAddress();
    $this->toSmarty['sessionid'] = session_id();
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
    
    $this->jsonOutput( $this->getFlashParameters( $flashdata ) );
    
  }
  
  public function deleteattachmentAction() {
    
    $attachmentModel = $this->modelIDCheck(
      'attached_documents',
      $this->application->getNumericParameter('id')
    );
    
    $recordingsModel = $this->modelOrganizationAndUserIDCheck(
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
    
    $recordingsModel = $this->modelOrganizationAndUserIDCheck(
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
    
    $views          = $this->bootstrap->getSession('views');
    $recordingModel = $this->modelIDCheck(
      'recordings',
      $recordingid,
      false
    );
    
    if ( !$recordingModel )
      return false;
    
    if ( !$views[ $recordingModel->id ] ) {
      
      $recordingModel->incrementViewCounters();
      $views[ $recordingModel->id ] = true;
      
    }
    
    return true;
    
  }

  public function checkstreamaccessAction( $secure = false ) {
    
    $param   = $this->application->getParameter('sessionid');
    $result  = '0';
    $matched =
      preg_match(
        '/(?P<organizationid>\d+)_' .
        '(?P<sessionid>[a-z0-9]{32})_' .
        '(?P<recordingid>\d+)/',
        $param,
        $matches
      )
    ;
    
    $ip  = $this->application->getParameter('IP');
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

            $access[ $accesskey ] = $recordingsModel->userHasAccess(
              $user, $secure, false, $organization
            );

            if ( $access[ $accesskey ] === true )
              $result = '1';

          }

        } else
          $result = '1';

      }

    }
    
    \Springboard\Debug::getInstance()->log(
      false,
      'recordingcheckaccessdebug.txt',
      "SECURE: $secure | RESULT: $result\n" .
      "  REQUEST_URI: " . $_SERVER['REQUEST_URI'] . $_SERVER['QUERY_STRING']
    );
    
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
  
  protected function getPlayerHeight( $recordingsModel, $fullscale = false ) {
    
    if ( $fullscale and $recordingsModel->row['mastermediatype'] == 'audio' and $recordingsModel->hasSubtitle() )
      return '140';
    elseif ( $fullscale and $recordingsModel->row['mastermediatype'] == 'audio' )
      return '60';
    elseif ( $fullscale )
      return '530';
    
    if ( $recordingsModel->row['mastermediatype'] == 'audio' and $recordingsModel->hasSubtitle() )
      $height = '120';
    elseif ( $recordingsModel->row['mastermediatype'] == 'audio' )
      $height = '60';
    else
      $height = '385';
    
    return $height;
    
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
    $versions     = $recordingsModel->getVersions();
    $browserinfo  = $this->bootstrap->getBrowserInfo();
    $user         = $this->bootstrap->getSession('user');
    $access       = $this->bootstrap->getSession('recordingaccess');
    $accesskey    = $recordingsModel->id . '-' . (int)$recordingsModel->row['issecurestreamingforced'];
    $needauth     = false;
    $nopermission = false;
    $l            = $this->bootstrap->getLocalization();
    
    $access[ $accesskey ] = $recordingsModel->userHasAccess( $user, null, false, $this->organization );
    
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

    $this->toSmarty['needauth']      = $needauth;
    $this->toSmarty['ipaddress']     = $this->getIPAddress();
    $this->toSmarty['member']        = $user;
    $this->toSmarty['sessionid']     = session_id();

    if ( $skipcontent )
      $this->toSmarty['skipcontent'] = true;

    $flashdata = $recordingsModel->getFlashData( $this->toSmarty );
    
    $quality        = $this->application->getParameter('quality');
    $mobileversion  = array_pop( $versions['master']['mobile'] );
    $mobileversions = array();

    foreach( $versions['master']['mobile'] as $version ) {

      if ( $quality and $version['qualitytag'] == $quality )
        $mobileversion = $version;

      $mobileversions[] = $version['qualitytag'];

    }

    $this->toSmarty['mobileversions'] = $mobileversions;
    $this->toSmarty['mobilehttpurl'] = $recordingsModel->getMediaUrl(
      'mobilehttp',
      $mobileversion,
      $this->toSmarty
    );
    $this->toSmarty['mobilertspurl'] = $recordingsModel->getMediaUrl(
      'mobilertsp',
      $mobileversion,
      $this->toSmarty
    );

    if ( !empty( $versions['audio'] ) )
      $this->toSmarty['audiofileurl']  = $recordingsModel->getMediaUrl(
        'direct',
        current( $versions['audio'] ),
        $this->toSmarty
      );

    $flashdata['layout_logo'] = $this->toSmarty['STATIC_URI'] . 'images/player_overlay_logo.png';
    $flashdata['layout_logoOrientation'] = 'TR';

    if ( $this->organization['isplayerlogolinkenabled'] )
      $flashdata['layout_logoDestination'] =
        $this->toSmarty['BASE_URI'] . \Springboard\Language::get() .
        '/recordings/details/' . $recordingsModel->id . ',' .
        \Springboard\Filesystem::filenameize( $recordingsModel->row['title'] )
      ;

    if ( preg_match( '/^\d{1,2}h\d{1,2}m\d{1,2}s$|^\d+$/', $start ) )
      $flashdata['timeline_startPosition'] = $start;
    
    if ( $autoplay )
      $flashdata['timeline_autoPlay'] = true;
    
    if ( $needauth ) {
      $flashdata['authorization_need']      = true;
      $flashdata['authorization_loginForm'] = true;
    }
    
    if ( $nopermission ) {
      
      $flashdata['authorization_need']      = true;
      $flashdata['authorization_loginForm'] = false;
      $flashdata['authorization_message']   = $l('recordings', 'nopermission');
      
    }
    
    if ( $fullscale )
      $this->toSmarty['width']     = '950';
    else
      $this->toSmarty['width']     = '480';
    
    $this->toSmarty['height']      = $this->getPlayerHeight( $recordingsModel, $fullscale );
    $this->toSmarty['containerid'] = 'vsq_' . rand();
    $this->toSmarty['recording']   = $recordingsModel->row;
    $this->toSmarty['flashdata']   = $this->getFlashParameters( $flashdata );
    
    $this->smartyoutput('Visitor/Recordings/Embed.tpl');
    
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
  
  protected function checkOrganizationAndUseridWithApi( $api, $table, $id ) {
    
    $model = $this->modelOrganizationAndUserIDCheck( $table, $id, false );
    
    if ( $api and !$model )
      throw new \Exception('No record found, ID#' . $id . ', table: ' . $table );
    elseif ( !$model )
      $this->redirect('');
    else
      return $model;
    
  }
  
  public function checkfileresumeasuserAction( $file, $userid ) {
    return $this->checkfileresumeAction();
  }
  
  public function checkfileresumeAction() {
    
    $filename  = trim( $this->application->getParameter('name') );
    $filesize  = $this->application->getNumericParameter('size', null, true );
    $user      = $this->bootstrap->getSession('user');
    
    if ( !$filename or !$filesize )
      jsonOutput( array('status' => 'error') );
    
    $info        = array(
      'filename'  => $filename,
      'filesize'  => $filesize,
      'iscontent' => $this->application->getNumericParameter('iscontent', 0 ),
      'userid'    => $user['id'],
    );
    $uploadModel = $this->bootstrap->getModel('uploads');
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
    
    if ( $this->bootstrap->config['disable_uploads'] )
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
        $this->jsonOutput( array(
            'status' => 'error',
            'error'  => 'upload_uploaderror',
          )
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
    
    // chunk count is 0 based
    if ( $chunk + 1 == $chunks ) {
      
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
    
    if ( $uploadModel->row['status'] != 'uploading' )
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
    
    $recordingsModel->updateLastPosition(
      $this->organization, $user['id'], $lastposition, session_id()
    );
    return true;
    
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

  public function getcommentsAction() {
    
    $recordingid = $this->application->getNumericParameter('id');
    $page        = $this->application->getNumericParameter('page');
    
    if ( $recordingid <= 0 )
      $this->redirect('index');

    $recordingsModel = $this->modelIDCheck(
      'recordings', $recordingid
    );

    $comments = $this->getComments( $recordingsModel, $page );
    $this->jsonOutput( $comments );

  }
  
  public function logviewAction( $recordingid, $recordingversionid, $viewsessionid, $action, $streamurl, $positionfrom = null, $positionuntil = null, $useragent = '' ) {
    
    $statModel = $this->bootstrap->getModel('view_statistics_ondemand');
    $user      = $this->bootstrap->getSession('user');
    $ipaddress = $this->getIPAddress();
    $sessionid = session_id();
    $useragent .= "\n" . $_SERVER['HTTP_USER_AGENT'];

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

}
