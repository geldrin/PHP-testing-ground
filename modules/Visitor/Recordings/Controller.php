<?php
namespace Visitor\Recordings;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'index'                => 'public',
    'details'              => 'public',
    'getplayerconfig'      => 'public',
    'getcomments'          => 'public',
    'getsubtitle'          => 'public',
    'newcomment'           => 'member',
    'rate'                 => 'member',
    'upload'               => 'uploader',
    'uploadcontent'        => 'uploader',
    'uploadsubtitle'       => 'uploader',
    'uploadattachment'     => 'uploader',
    'myrecordings'         => 'uploader|clientadmin',
    'modifybasics'         => 'uploader|clientadmin',
    'modifyclassification' => 'uploader|clientadmin',
    'modifydescription'    => 'uploader|clientadmin',
    'modifycontributors'   => 'uploader|clientadmin',
    'modifysharing'        => 'uploader|clientadmin',
    'modifyattachment'     => 'uploader|clientadmin',
    'deleteattachment'     => 'uploader|clientadmin',
    'deletesubtitle'       => 'uploader|clientadmin',
    'delete'               => 'uploader|clientadmin',
    'deletecontent'        => 'uploader|clientadmin',
    'deletecontributor'    => 'uploader|clientadmin',
    'swapcontributor'      => 'uploader|clientadmin',
    'checkstreamaccess'    => 'public',
    'securecheckstreamaccess' => 'public',
    'embed'                => 'public',
    'featured'             => 'public',
    'linkcontributor'      => 'uploader|clientadmin',
    'addtochannel'         => 'member',
    'removefromchannel'    => 'member',
    'checkfileresume'      => 'uploader',
    'uploadchunk'          => 'uploader',
    'cancelupload'         => 'uploader',
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
    
    $recordingsModel = $this->modelIDCheck(
      'recordings',
      $this->application->getNumericParameter('id')
    );
    
    $browserinfo = $this->bootstrap->getBrowserInfo();
    $user        = $this->bootstrap->getSession('user');
    $rating      = $this->bootstrap->getSession('rating');
    $access      = $this->bootstrap->getSession('recordingaccess');
    $accesskey   = $recordingsModel->id . '-' . (int)$recordingsModel->row['issecurestreamingforced'];
    
    $access[ $accesskey ] = $recordingsModel->userHasAccess( $user, null, $browserinfo['mobile'] );
    
    if ( $access[ $accesskey ] !== true )
      $this->redirectToController('contents', $access[ $accesskey ] );
    
    include_once(
      $this->bootstrap->config['templatepath'] .
      'Plugins/modifier.indexphoto.php'
    );
    
    if ( $user['id'] )
      $this->toSmarty['channels'] = $recordingsModel->getChannelsForUser( $user );
    
    $this->toSmarty['member']        = $user;
    $this->toSmarty['needping']      = true;
    $this->toSmarty['height']        = $this->getPlayerHeight( $recordingsModel );
    $this->toSmarty['recording']     = $recordingsModel->addPresenters( true, $this->organization['id'] );
    $this->toSmarty['flashdata']     = $this->getFlashParameters(
      $recordingsModel->getFlashData( $this->toSmarty, session_id() )
    );
    $this->toSmarty['comments']      = $recordingsModel->getComments();
    $this->toSmarty['commentcount']  = $recordingsModel->getCommentsCount();
    $this->toSmarty['author']        = $recordingsModel->getAuthor();
    $this->toSmarty['attachments']   = $recordingsModel->getAttachments();
    $this->toSmarty['canrate']       = ( $user['id'] and !$rating[ $recordingsModel->id ] );
    $this->toSmarty['relatedvideos'] = $recordingsModel->getRelatedVideos(
      $this->application->config['relatedrecordingcount'],
      $user,
      $this->organization['id']
    );
    $this->toSmarty['opengraph']     = array(
      'type'        => 'video',
      'image'       => smarty_modifier_indexphoto( $recordingsModel->row, 'player' ),
      'description' => $recordingsModel->row['description'],
      'title'       => $recordingsModel->row['title'],
      'subtitle'    => $recordingsModel->row['subtitle'],
      'width'       => 398,
      'height'      => 303,
      'video'       =>
        $this->toSmarty['BASE_URI'] . 'flash/TCSharedPlayer.swf?media_json=' .
        rawurlencode(
          $this->toSmarty['BASE_URI'] . \Springboard\Language::get() .
          '/recordings/getplayerconfig/' . $recordingsModel->id
        )
      ,
    );
    $mobilehq = false;
    if ( $recordingsModel->row['mobilevideoreshq'] and $browserinfo['tablet'] )
      $mobilehq = true;
    
    $quality = $this->application->getParameter('quality');
    if ( $quality and in_array( $quality, array('lq', 'hq') ) ) {
      
      if ( $quality == 'hq' and $recordingsModel->row['mobilevideoreshq'] )
        $mobilehq = true;
      elseif ( $quality == 'lq' )
        $mobilehq = false;
      
    }
    
    $this->toSmarty['mobilehq']      = $mobilehq;
    $this->toSmarty['mobilehttpurl'] = $recordingsModel->getMediaUrl(
      'mobilehttp',
      $mobilehq,
      $this->toSmarty['organization']['domain'],
      session_id()
    );
    $this->toSmarty['mobilertspurl'] = $recordingsModel->getMediaUrl(
      'mobilertsp',
      $mobilehq,
      $this->toSmarty['organization']['domain'],
      session_id()
    );
    $this->toSmarty['audiofileurl']  = $recordingsModel->getMediaUrl(
      'direct',
      false, // non-hq
      $this->toSmarty['organization']['domain'],
      session_id(),
      $this->toSmarty['STATIC_URI']
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
    
    $access[ $accesskey ] = $recordingsModel->userHasAccess( $user );
    
    if ( $access[ $accesskey ] === 'registrationrestricted' )
      $needauth = true;
    
    $this->toSmarty['member'] = $user;
    $flashdata = $recordingsModel->getStructuredFlashData( $this->toSmarty, session_id() );
    
    if ( $needauth ) {
      
      $flashdata['authorization']            = array();
      $flashdata['authorization']['need']    = true;
      $flashdata['authorization']['gateway'] = $this->bootstrap->baseuri . 'hu/api';
      
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
  
  public function getcommentsAction() {
    
    $recordingid = $this->application->getNumericParameter('id');
    $start       = $this->application->getNumericParameter('start');
    
    if ( $recordingid <= 0 )
      $this->redirect('index');
    
    if ( $start < 0 )
      $start = 0;
    
    $l               = $this->bootstrap->getLocalization();
    $recordingsModel = $this->bootstrap->getModel('recordings');
    $recordingsModel->id = $recordingid;
    
    $comments     = $recordingsModel->getComments( $start );
    $commentcount = $recordingsModel->getCommentsCount();
    
    $this->jsonOutput( array(
        'comments'     => $comments,
        'nocomments'   => $l('recordings', 'nocomments'),
        'commentcount' => $commentcount,
      )
    );
    
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
    
    \Springboard\Debug::getInstance()->log( false, false, "SECURE: $secure\n" . var_export( $_SERVER, true ) );
    $param   = $this->application->getParameter('sessionid');
    $result  = '0';
    $matched =
      preg_match(
        '/(?P<domain>[a-z\.]+)_' .
        '(?P<sessionid>[a-z0-9]{32})_' .
        '(?P<recordingid>\d+)/',
        $param,
        $matches
      )
    ;
    
    if ( $matched ) {
      
      $this->bootstrap->setupSession( true, $matches['sessionid'], $matches['domain'] );
      $access    = $this->bootstrap->getSession('recordingaccess');
      $accesskey = $matches['recordingid'] . '-' . (int)$secure;
      
      if ( $access[ $accesskey ] !== true ) {
        
        $user            = $this->bootstrap->getSession('user');
        $recordingsModel = $this->modelIDCheck('recordings', $matches['recordingid'], false );
        
        if ( $recordingsModel ) {
          
          $access[ $accesskey ] = $recordingsModel->userHasAccess( $user, $secure );
          
          if ( $access[ $accesskey ] === true )
            $result = '1';
          
        }
        
      } else
        $result = '1';
      
    }
    
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
    
    $user         = $this->bootstrap->getSession('user');
    $access       = $this->bootstrap->getSession('recordingaccess');
    $accesskey    = $recordingsModel->id . '-' . (int)$recordingsModel->row['issecurestreamingforced'];
    $needauth     = false;
    $nopermission = false;
    $l            = $this->bootstrap->getLocalization();
    
    $access[ $accesskey ] = $recordingsModel->userHasAccess( $user );
    
    if (
         in_array( $access[ $accesskey ], array(
             'registrationrestricted',
             'grouprestricted',
             'departmentrestricted',
           ), true // strict = true
         )
       )
      $needauth = true;
    elseif ( $access[ $accesskey ] !== true )
      $nopermission = true;
    
    $mobilehq = false;
    if ( $recordingsModel->row['mobilevideoreshq'] ) {
      
      $browserinfo = $this->bootstrap->getBrowserInfo();
      if ( $browserinfo['tablet'] )
        $mobilehq = true;
      
    }
    
    $quality = $this->application->getParameter('quality');
    if ( $quality and in_array( $quality, array('lq', 'hq') ) ) {
      
      if ( $quality == 'hq' and $recordingsModel->row['mobilevideoreshq'] )
        $mobilehq = true;
      elseif ( $quality == 'lq' )
        $mobilehq = false;
      
    }
    
    $this->toSmarty['member']        = $user;
    $this->toSmarty['mobilehq']      = $mobilehq;
    $this->toSmarty['mobilehttpurl'] = $recordingsModel->getMediaUrl(
      'mobilehttp',
      $mobilehq,
      $this->toSmarty['organization']['domain'],
      session_id()
    );
    $this->toSmarty['mobilertspurl'] = $recordingsModel->getMediaUrl(
      'mobilertsp',
      $mobilehq,
      $this->toSmarty['organization']['domain'],
      session_id()
    );
    $this->toSmarty['audiofileurl']  = $recordingsModel->getMediaUrl(
      'direct',
      false, // non-hq
      $this->toSmarty['organization']['domain'],
      session_id(),
      $this->toSmarty['STATIC_URI']
    );
    
    $autoplay  = $this->application->getParameter('autoplay');
    $start     = $this->application->getParameter('start');
    $fullscale = $this->application->getParameter('fullscale');
    $skipcontent = $this->application->getParameter('skipcontent');
    
    if ( $skipcontent )
      $this->toSmarty['skipcontent'] = true;
    
    $flashdata = $recordingsModel->getFlashData( $this->toSmarty, session_id() );
    
    $flashdata['layout_logo'] = $this->toSmarty['STATIC_URI'] . 'images/player_overlay_logo.png';
    $flashdata['layout_logoOrientation'] = 'TR';
    /*
    $flashdata['layout_logoDestination'] =
      $this->toSmarty['BASE_URI'] . \Springboard\Language::get() .
      '/recordings/details/' . $recordingsModel->id . ',' .
      \Springboard\Filesystem::filenameize( $recordingsModel->row['title'] )
    ;
    */
    
    if ( preg_match( '/^\d{1,2}h\d{1,2}m\d{1,2}s$/', $start ) )
      $flashdata['timeline_startPosition'] = $start;
    
    if ( $autoplay )
      $flashdata['timeline_autoPlay'] = true;
    
    if ( $needauth ) {
      
      $flashdata['authorization_need']    = true;
      $flashdata['authorization_gateway'] = rawurlencode(
        $this->bootstrap->baseuri . 'hu/api?' .
        http_build_query( array(
            'format'      => 'json',
            'layer'       => 'controller',
            'module'      => 'users',
            'method'      => 'authenticate',
            'recordingid' => $recordingsModel->id,
          )
        )
      );
      
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
        
        $channelid = $this->application->getNumericParameter('channelid');
        if ( !$info['iscontent'] and $channelid ) {
          
          $channelModel = $this->modelOrganizationAndUserIDCheck(
            'channels',
            $channelid,
            false
          );
          
          if ( !$channelModel ) {
            
            $error = 'upload_invalidchannel';
            $message( $l('recordings', 'invalidchannel') );
            
          } else {
            
            $channelModel->insertIntoChannel( $recordingModel->id, $user );
            
          }
          
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
          'Metadata: ' . var_export( @$recordingModel->metadata, true )
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
    
    $user            = $this->bootstrap->getSession('user');
    $recordingsModel = $this->modelIDCheck(
      'recordings',
      $recordingid,
      false
    );
    
    if ( !$user or !$user['id'] )
      return 'nouser';
    
    if ( !$recordingsModel )
      return 'norecording';
    
    $recordingsModel->updateLastPosition( $user['id'], $lastposition );
    return true;
    
  }
  
}
