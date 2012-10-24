<?php
namespace Visitor\Live;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'index'                => 'public',
    'details'              => 'public',
    'view'                 => 'public',
    'getchat'              => 'public',
    'createchat'           => 'member',
    'moderatechat'         => 'liveadmin',
    'create'               => 'liveadmin',
    'modify'               => 'liveadmin',
    'createfeed'           => 'liveadmin',
    'modifyfeed'           => 'liveadmin',
    'deletefeed'           => 'liveadmin',
    'createstream'         => 'liveadmin',
    'modifystream'         => 'liveadmin',
    'deletestream'         => 'liveadmin',
    'managefeeds'          => 'liveadmin',
    'togglestream'         => 'liveadmin',
    'getstreamstatus'      => 'liveadmin',
    'checkstreamaccess'    => 'public',
    'securecheckstreamaccess' => 'public',
  );
  
  public $forms = array(
    'create'               => 'Visitor\\Live\\Form\\Create',
    'modify'               => 'Visitor\\Live\\Form\\Modify',
    'createfeed'           => 'Visitor\\Live\\Form\\Createfeed',
    'modifyfeed'           => 'Visitor\\Live\\Form\\Modifyfeed',
    'createstream'         => 'Visitor\\Live\\Form\\Createstream',
    'modifystream'         => 'Visitor\\Live\\Form\\Modifystream',
    'createchat'           => 'Visitor\\Live\\Form\\Createchat',
  );
  
  public $paging = array(
    'index'   => 'Visitor\\Live\\Paging\\Index',
    'details' => 'Visitor\\Live\\Paging\\Details',
  );
  
  public function init() {
    
    parent::init();
    if ( !$this->organization['islivestreamingenabled'] ) {
      
      header('HTTP/1.0 403 Forbidden');
      $this->redirectToController('contents', 'nopermissionlivestreaming');
      
    }
    
  }
  
  public function viewAction() {
    
    $feedModel = $this->modelIDCheck(
      'livefeeds',
      $this->application->getNumericParameter('id')
    );
    
    $user      = $this->bootstrap->getSession('user');
    $access    = $this->bootstrap->getSession('liveaccess');
    $accesskey = $feedModel->id . '-0'; // TODO secure
    
    $access[ $accesskey ] = $feedModel->isAccessible( $user );
    
    $channelModel = $this->modelIDCheck('channels', $feedModel->row['channelid'] );
    $streamid     = $this->application->getNumericParameter('streamid');
    $browserinfo  = $this->bootstrap->getSession('browser');
    $fullplayer   = $this->application->getParameter('player', true );
    $chromeless   = $this->application->getParameter('chromeless');
    $displaychat  = true;
    $needauth     = false;
    $nopermission = false;
    
    if (
         $chromeless and in_array( $access[ $accesskey ], array(
             'registrationrestricted',
             'grouprestricted',
             'departmentrestricted',
           ), true // strict = true
         )
       )
      $needauth = true;
    elseif ( $chromeless and $access[ $accesskey ] !== true )
      $nopermission = true;
    elseif ( $access[ $accesskey ] !== true )
      $this->redirectToController('contents', $access[ $accesskey ] );
    
    if ( !count( $browserinfo ) )
      $browserinfo->setArray( \Springboard\Browser::getInfo() );
    
    $streams      = $feedModel->getStreamsForBrowser( $browserinfo, $streamid );
    
    if ( !$streams )
      $this->redirectToController('contents', 'http404');
    
    $currentstream = $streams['defaultstream'];
    $streamtype    = $streams['streamtype'];
    $streams       = $streams['streams'];
    $authorizecode = $feedModel->getAuthorizeSessionidParam(
      $this->organization['domain'],
      session_id()
    );
    
    $flashdata = array(
      'language'        => \Springboard\Language::get(),
      'media_servers'   => array( $this->bootstrap->config['wowza']['liveingressurl'] . $authorizecode ),
      'media_streams'   => array( $currentstream['keycode'] ),
      'recording_title' => $feedModel->row['name'],
      'recording_type'  => 'live',
    );
    
    if ( $feedModel->row['hascontent'] and ( !$chromeless or $fullplayer ) ) {
      
      $flashdata['media_secondaryServers'] = array( $this->bootstrap->config['wowza']['liveingressurl'] . $authorizecode );
      $flashdata['media_secondaryStreams'] = array( $currentstream['contentkeycode'] );
      $this->toSmarty['doublewidth']       = true;
      
    }
    
    if ( !$feedModel->row['slideonright'] )
      $flashdata['layout_videoOrientation'] = 'right';
    
    if ( $flashdata['language'] != 'en' )
      $flashdata['locale'] =
        $this->toSmarty['STATIC_URI'] .
        'js/flash_locale_' . $flashdata['language'] . '.json'
      ;
    
    if ( $needauth ) {
      
      $flashdata['authorization_need']    = true;
      $flashdata['authorization_gateway'] = rawurlencode(
        $this->bootstrap->baseuri . 'hu/api?' .
        http_build_query( array(
            'format' => 'json',
            'layer'  => 'controller',
            'module' => 'users',
            'method' => 'authenticate',
            'feedid' => $feedModel->id,
          )
        )
      );
      
    }
    
    if ( $nopermission ) {
      
      $flashdata['authorization_need']      = true;
      $flashdata['authorization_loginForm'] = false;
      $flashdata['authorization_message']   = $l('recordings', 'nopermission');
      
    }
    
    $this->toSmarty['flashdata'] = $flashdata;
    
    $this->toSmarty['livehttpurl'] = $feedModel->getMediaUrl(
      'livehttp',
      $currentstream['keycode'],
      $this->toSmarty['organization']['domain'],
      session_id()
    );
    $this->toSmarty['livertspurl'] = $feedModel->getMediaUrl(
      'livertsp',
      $currentstream['keycode'],
      $this->toSmarty['organization']['domain'],
      session_id()
    );
    
    if ( $chromeless ) {
      
      $displaychat = $this->application->getParameter('chat', 1 );
      if ( $displaychat == 'false' )
        $displaychat = false;
      
    }
    
    if ( $feedModel->row['moderationtype'] == 'nochat' )
      $displaychat = false;
    
    if ( $displaychat ) {
      
      if ( !$this->acl ) {
        
        $this->acl = $this->bootstrap->getAcl();
        $this->acl->usersessionkey = $this->usersessionkey;
        
      }
      
      $this->toSmarty['liveadmin']     = $this->acl->hasPermission('liveadmin');
      // ha liveadmin akkor kiirjuk a moderalasra varo commenteket
      $this->toSmarty['chatitems']     = $feedModel->getChat( $this->toSmarty['liveadmin']? null: -1 );
      $this->toSmarty['chat']          = $this->fetchSmarty('Visitor/Live/Chat.tpl');
      $this->toSmarty['lastmodified']  = md5( $this->toSmarty['chat'] );
      
    }
    
    $this->toSmarty['streamtype']    = $streamtype;
    $this->toSmarty['displaychat']   = $displaychat;
    $this->toSmarty['channel']       = $channelModel->row;
    $this->toSmarty['streams']       = $streams;
    $this->toSmarty['feed']          = $feedModel->row;
    $this->toSmarty['currentstream'] = $currentstream;
    $this->toSmarty['liveurl']       = $this->bootstrap->config['wowza']['liveurl'];
    $this->toSmarty['chatpolltime']  = $this->bootstrap->config['chatpolltimems'];
    
    $this->smartyOutput('Visitor/Live/View.tpl');
    
  }
  
  public function managefeedsAction() {
    
    $channelModel = $this->modelOrganizationAndUserIDCheck(
      'channels',
      $this->application->getNumericParameter('id')
    );
    $helpModel    = $this->bootstrap->getModel('help_contents');
    $helpModel->addFilter('shortname', 'live_managefeeds', false, false );
    
    $this->toSmarty['help']    = $helpModel->getRow();
    $this->toSmarty['feeds']   = $channelModel->getFeedsWithStreams();
    $this->toSmarty['channel'] = $channelModel->row;
    $this->smartyOutput('Visitor/Live/Managefeeds.tpl');
    
  }
  
  public function deletefeedAction() {
    
    $feedModel    = $this->modelIDCheck(
      'livefeeds',
      $this->application->getNumericParameter('id')
    );
    
    $channelModel = $this->modelOrganizationAndUserIDCheck(
      'channels',
      $feedModel->row['channelid']
    );
    
    if ( $feedModel->row['feedtype'] == 'vcr' and !$feedModel->canDeleteFeed() )
      throw new \Exception("VCR helszín törles nem lehetséges!");
    
    $feedModel->delete( $feedModel->id );
    $this->redirect(
      $this->application->getParameter(
        'forward',
        'live/managefeeds/' . $channelModel->id
      )
    );
    
  }
  
  public function deletestreamAction() {
    
    $streamModel   = $this->modelIDCheck(
      'livefeed_streams',
      $this->application->getNumericParameter('id')
    );
    
    $feedModel    = $this->modelIDCheck(
      'livefeeds',
      $streamModel->row['livefeedid']
    );
    
    $channelModel = $this->modelOrganizationAndUserIDCheck(
      'channels',
      $feedModel->row['channelid']
    );
    
    $streamModel->delete( $streamModel->id );
    $this->redirect(
      $this->application->getParameter(
        'forward',
        'live/managefeeds/' . $channelModel->id
      )
    );
    
  }
  
  public function togglestreamAction() {
    
    $streamModel   = $this->modelIDCheck(
      'livefeed_streams',
      $this->application->getNumericParameter('id')
    );
    
    $feedModel    = $this->modelIDCheck(
      'livefeeds',
      $streamModel->row['livefeedid']
    );
    
    $channelModel = $this->modelOrganizationAndUserIDCheck(
      'channels',
      $feedModel->row['channelid']
    );
    
    if ( $this->application->getNumericParameter('start') == '1' ) {
      
      if ( $streamModel->row['status'] != 'ready' )
        $this->redirectToController('contents', 'livestream_invalidtransition_start');
      
      $status = 'start';
      
    } elseif ( $this->application->getNumericParameter('start') == '0' ) {
      
      if ( $streamModel->row['status'] != 'recording' )
        $this->redirectToController('contents', 'livestream_invalidtransition_stop');
      
      $status = 'disconnect';
      
    } else
      throw new \Exception('Invalid start argument');
    
    $streamModel->updateRow( array(
        'status' => $status,
      )
    );
    
    $this->redirect(
      $this->application->getParameter(
        'forward',
        'live/managefeeds/' . $channelModel->id
      )
    );
    
  }
  
  public function getchatAction( $livefeedid = null ) {
    
    if ( !$livefeedid )
      $livefeedid = $this->application->getNumericParameter('id');
    
    if ( !$livefeedid or $livefeedid < 0 )
      $this->jsonOutput( array('status' => 'error') );
    
    if ( !$this->acl ) {
      
      $this->acl = $this->bootstrap->getAcl();
      $this->acl->usersessionkey = $this->usersessionkey;
      
    }
    
    $liveadmin = $this->acl->hasPermission('liveadmin');
    $cache     = $this->getChatCache( $livefeedid, $liveadmin );
    
    if ( $cache->expired() or !$this->application->production ) {
        
      $feedModel        = $this->modelIDCheck( 'livefeeds', $livefeedid );
      $excludemoderated = $liveadmin? null: -1; // ha liveadmin akkor kiirjuk a moderalasra varo commenteket
      $chat             = $feedModel->getChat( $excludemoderated );
      
      $this->toSmarty['liveadmin'] = $liveadmin;
      $this->toSmarty['chatitems'] = $chat;
      $data                        = array('html' => $this->fetchSmarty('Visitor/Live/Chat.tpl') );
      $data['lastmodified']        = md5( $data['html'] );
      $cache->put( $data );
      
    } else
      $data = $cache->get();
    
    if ( $this->application->getParameter('lastmodified') == $data['lastmodified'] ) {
      
      header('HTTP/1.1 204 No Content');
      die();
      
    }
    
    $this->jsonOutput( array(
        'status'       => 'success',
        'lastmodified' => $data['lastmodified'],
        'html'         => $data['html'],
        'polltime'     => $this->bootstrap->config['chatpolltimems'],
      )
    );
    
  }
  
  public function expireChatCache( $livefeedid ) {
    $this->getChatCache( $livefeedid, 0 )->expire();
    $this->getChatCache( $livefeedid, 1 )->expire();
  }
  
  public function getChatCache( $livefeedid, $liveadmin ) {
    
    return $this->bootstrap->getCache(
      sprintf('livefeed_chat-%d-%d', $livefeedid, (int)$liveadmin )
    );
    
  }
  
  public function moderatechatAction() {
    
    $moderated = $this->application->getNumericParameter('moderate');
    
    if ( $moderated != 0 and $moderated != 1 )
      $this->redirect('');
    
    $chatModel = $this->modelIDCheck(
      'livefeed_chat',
      $this->application->getNumericParameter('id')
    );
    
    $feedModel = $this->modelOrganizationAndUserIDCheck(
      'livefeeds',
      $chatModel->row['livefeedid']
    );
    
    $chatModel->updateRow( array(
        'moderated' => $moderated,
      )
    );
    
    $this->expireChatCache( $feedModel->id );
    return $this->getchatAction( $feedModel->id );
    
  }
  
  public function checkstreamaccessAction( $secure = false ) {
    
    \Springboard\Debug::getInstance()->log( false, false, "SECURE: $secure\n" . var_export( $_SERVER, true ) );
    $param   = $this->application->getParameter('sessionid');
    $result  = '0';
    $matched =
      preg_match(
        '/(?P<domain>[a-z\.]+)_' .
        '(?P<sessionid>[a-z0-9]{32})_' .
        '(?P<feedid>\d+)/',
        $param,
        $matches
      )
    ;
    
    if ( $matched ) {
      
      $this->bootstrap->setupSession( true, $matches['sessionid'], $matches['domain'] );
      $access    = $this->bootstrap->getSession('liveaccess');
      $accesskey = $matches['feedid'] . '-' . (int)$secure;
      
      if ( $access[ $accesskey ] !== true ) {
        
        $user      = $this->bootstrap->getSession('user');
        $feedModel = $this->modelIDCheck('livefeeds', $matches['feedid'], false );
        
        if ( $feedModel ) {
          
          $access[ $accesskey ] = $feedModel->isAccessible( $user, $secure );
          
          if ( $access[ $accesskey ] === true )
            $result = '1';
          
        }
        
      } else
        $result = '1';
      
    } else
      $result = '1'; // TODO ha nincs sessionid akkor haljunk el
    
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
  
  public function getstreamstatusAction() {
    
    $streamModel = $this->bootstrap->getModel('livefeed_streams');
    $statuses    = $streamModel->getStatusForIDs(
      $this->application->getParameter('id')
    );
    
    $data = array();
    foreach( $statuses as $key => $value ) {
      
      $this->toSmarty['stream'] = $data[ $key ] = $value;
      $data[ $key ]['html']     =
        $this->fetchSmarty('Visitor/Live/Managefeeds_streamaction.tpl')
      ;
      
    }
    
    $this->jsonOutput( array(
        'status'     => 'success',
        'data'       => $data,
        'polltimems' => 5000,
      )
    );
    
  }
  
}
