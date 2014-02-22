<?php
namespace Visitor\Live;

class Controller extends \Visitor\Controller {
  public $permissions = array(
    'index'                => 'public',
    'details'              => 'public',
    'view'                 => 'public',
    'getchat'              => 'public',
    'refreshchatinput'     => 'public',
    'createchat'           => 'public',
    'moderatechat'         => 'liveadmin|clientadmin',
    'create'               => 'liveadmin|clientadmin',
    'modify'               => 'liveadmin|clientadmin',
    'createfeed'           => 'liveadmin|clientadmin',
    'modifyfeed'           => 'liveadmin|clientadmin',
    'deletefeed'           => 'liveadmin|clientadmin',
    'createstream'         => 'liveadmin|clientadmin',
    'modifystream'         => 'liveadmin|clientadmin',
    'deletestream'         => 'liveadmin|clientadmin',
    'managefeeds'          => 'liveadmin|clientadmin',
    'togglestream'         => 'liveadmin|clientadmin',
    'getstreamstatus'      => 'liveadmin|clientadmin',
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
    $anonuser  = $this->bootstrap->getSession('anonuser');
    $access    = $this->bootstrap->getSession('liveaccess');
    $accesskey = $feedModel->id . '-' . ( $feedModel->row['issecurestreamingforced']? '1': '0');
    
    $access[ $accesskey ] = $feedModel->isAccessible( $user );
    
    $channelModel = $this->modelIDCheck('channels', $feedModel->row['channelid'] );
    $streamid     = $this->application->getNumericParameter('streamid');
    $browserinfo  = $this->bootstrap->getSession('browser');
    $chromeless   = $this->application->getParameter('chromeless');
    $displaychat  = true;
    $needauth     = false;
    $nopermission = false;
    $fullplayer   = true;
    $urlparams    = array();
    
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
    else
      $this->handleUserAccess( $access[ $accesskey ] );
    
    if ( !count( $browserinfo ) )
      $browserinfo->setArray( \Springboard\Browser::getInfo() );
    
    $streams = $feedModel->getStreamsForBrowser( $browserinfo, $streamid );
    
    if ( !$streams )
      $this->redirectToController('contents', 'http404');
    
    $currentstream = $streams['defaultstream'];
    $streamtype    = $streams['streamtype'];
    $info          = array(
      'organization' => $this->organization,
      'sessionid'    => session_id(),
      'ipaddress'    => $this->getIPAddress(),
      'BASE_URI'     => $this->toSmarty['BASE_URI'],
      'cookiedomain' => $this->organization['cookiedomain'],
      'streams'      => $streams,
      'user'         => $user,
      'checkwatchingtimeinterval' => $this->organization['presencechecktimeinterval'],
      'checkwatchingconfirmationtimeout' => $this->organization['presencecheckconfirmationtime'],
    );
    $flashdata     = $feedModel->getFlashData( $info );
    
    $this->toSmarty['playerwidth']  = 950;
    $this->toSmarty['playerheight'] = 530;
    $this->toSmarty['anonuser']     = $anonuser;

    if ( $feedModel->row['moderationtype'] == 'nochat' )
      $displaychat = false;
    
    if ( $chromeless ) {
      
      $flashdata['layout_logo'] = $this->toSmarty['STATIC_URI'] . 'images/player_overlay_logo.png';
      $flashdata['layout_logoOrientation'] = 'TR';
      
      /*
      $flashdata['layout_logoDestination'] =
        $this->toSmarty['BASE_URI'] . \Springboard\Language::get() .
        '/live/view/' . $feedModel->id . ',' . $currentstream['id'] . ',' .
        \Springboard\Filesystem::filenameize( $feedModel->row['name'] )
      ;
      */
      $fullplayer = $this->application->getParameter('fullplayer');
      $chat       = $this->application->getParameter('chat');
      
      $urlparams['chromeless'] = 'true';
      if ( $chat == 'false' ) {
        
        $displaychat = false;
        $urlparams['chat'] = 'false';
        
      }
      
      if ( $fullplayer == 'false' ) {
        
        $urlparams['fullplayer'] = 'false';
        $fullplayer = false;
        $this->toSmarty['playerwidth']  = 480;
        $this->toSmarty['playerheight'] = 385;
        
      }
      
    }
    
    if ( $flashdata['language'] != 'en' )
      $flashdata['locale'] =
        $this->toSmarty['STATIC_URI'] .
        'js/flash_locale_' . $flashdata['language'] . '.json'
      ;
    
    if ( $needauth )
      $flashdata['authorization_need']    = true;
    
    if ( $nopermission ) {
      
      $flashdata['authorization_need']      = true;
      $flashdata['authorization_loginForm'] = false;
      $flashdata['authorization_message']   = $l('recordings', 'nopermission');
      
    }
    
    if ( $chromeless and $displaychat )
      $flashdata['authorization_callback'] = 'onLiveFlashLogin';
    
    $this->toSmarty['flashdata']   =
      $this->getFlashParameters( $flashdata )
    ;
    $this->toSmarty['livehttpurl'] = $feedModel->getMediaUrl(
      'livehttp',
      $currentstream['keycode'],
      $info,
      session_id()
    );
    $this->toSmarty['livertspurl'] = $feedModel->getMediaUrl(
      'livertsp',
      $currentstream['keycode'],
      $info,
      session_id()
    );
    
    if ( $user['id'] ) {
      $this->toSmarty['livehttpurl'] .= '&uid=' . $user['id'];
      $this->toSmarty['livertspurl'] .= '&uid=' . $user['id'];
    }
    
    if ( $displaychat ) {
      
      if ( !$this->acl ) {
        
        $this->acl = $this->bootstrap->getAcl();
        $this->acl->usersessionkey = $this->usersessionkey;
        
      }
      
      $this->toSmarty['needauth']      = $needauth;
      $this->toSmarty['liveadmin']     = $this->acl->hasPermission('liveadmin|clientadmin');
      // ha liveadmin akkor kiirjuk a moderalasra varo commenteket
      $this->toSmarty['chatitems']     = $feedModel->getChat();
      
      if ( $access[ $accesskey ] !== true ) {
        $l                             = $this->bootstrap->getLocalization();
        $this->toSmarty['chat']        = '&nbsp;';
      } else
        $this->toSmarty['chat']        = $this->fetchSmarty('Visitor/Live/Chat.tpl');
      
      $this->toSmarty['lastmodified']  = md5( $this->toSmarty['chat'] );
      
    }
    
    $this->toSmarty['needauth']      = $needauth;
    $this->toSmarty['needping']      = true;
    $this->toSmarty['chromeless']    = $chromeless;
    $this->toSmarty['streamtype']    = $streamtype;
    $this->toSmarty['displaychat']   = $displaychat;
    $this->toSmarty['channel']       = $channelModel->row;
    $this->toSmarty['streams']       = $streams['streams'];
    $this->toSmarty['feed']          = $feedModel->row;
    $this->toSmarty['currentstream'] = $currentstream;
    $this->toSmarty['liveurl']       = $this->bootstrap->config['wowza']['liveurl'];
    $this->toSmarty['chatpolltime']  = $this->bootstrap->config['chatpolltimems'];
    
    if ( !empty( $urlparams ) )
      $this->toSmarty['urlparams'] = '?' . http_build_query( $urlparams );
    
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
      
      if ( $streamModel->row['status'] != 'ready' ) {
        \Springboard\Debug::getInstance()->log( false, false, 'Stream nem tudott indulni: ' . var_export( $stream, true ), true );
        $this->redirectToController('contents', 'livestream_invalidtransition_start');
      }
      
      $status = 'start';
      
    } elseif ( $this->application->getNumericParameter('start') == '0' ) {
      
      if ( $streamModel->row['status'] != 'recording' ) {
        \Springboard\Debug::getInstance()->log( false, false, 'Stream nem tudott leallni: ' . var_export( $stream, true ), true );
        $this->redirectToController('contents', 'livestream_invalidtransition_stop');
      }
      
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
    
    $access = $this->bootstrap->getSession('liveaccess');
    
    if ( $access[ $livefeedid . '-0' ] === true or $access[ $livefeedid . '-1' ] === true ) {
      
      if ( !$this->acl ) {
        
        $this->acl = $this->bootstrap->getAcl();
        $this->acl->usersessionkey = $this->usersessionkey;
        
      }
      
      $liveadmin = $this->acl->hasPermission('liveadmin|clientadmin');
      $cache     = $this->getChatCache( $livefeedid );
      
      if ( $cache->expired() or !$this->application->production ) {
        
        $feedModel = $this->modelIDCheck( 'livefeeds', $livefeedid );
        $chat      = $feedModel->getChat();
        
        $cache->put( $chat );
        
      } else
        $chat = $cache->get();
      
      $this->toSmarty['liveadmin'] = $liveadmin;
      $this->toSmarty['chatitems'] = $chat;
      $data                        = array('html' => $this->fetchSmarty('Visitor/Live/Chat.tpl') );
      $data['lastmodified']        = md5( $data['html'] );
      
    } else {
      
      $data = array( 'html' => '&nbsp;' );
      $data['lastmodified'] = md5( $data['html'] );
      
    }
    
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
    $this->getChatCache( $livefeedid )->expire();
  }
  
  public function getChatCache( $livefeedid ) {
    
    return $this->bootstrap->getCache(
      sprintf('livefeed_chat-%d', $livefeedid )
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
    
    $ip  = $this->application->getParameter('IP');
    $ips = $this->bootstrap->config['allowedstreamips'];
    
    if ( $ip and $ips and in_array( $ip, $ips ) ) {
      
      $result  = '1';
      $matched = false;
      
    }
    
    if ( $matched ) {
      
      $this->bootstrap->setupSession( true, $matches['sessionid'], $matches['domain'] );
      $this->debugLogUsers();
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
      
    }
    
    \Springboard\Debug::getInstance()->log(
      false,
      'livecheckaccessdebug.txt',
      "LIVESECURE: $secure | RESULT: $result\n" .
      "  REQUEST_URI: " . $_SERVER['REQUEST_URI']
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
  
  public function refreshchatinputAction() {
    
    $feedModel = $this->modelIDCheck(
      'livefeeds',
      $this->application->getNumericParameter('id')
    );
    
    if ( $feedModel->row['accesstype'] != 'registrations' )
      $this->toSmarty['needauth'] = true;
    
    $this->toSmarty['feed']       = $feedModel->row;
    $this->toSmarty['chromeless'] = true;
    $this->jsonOutput( array(
        'status' => 'success',
        'html'   => $this->fetchSmarty('Visitor/Live/Chatinput.tpl'),
      )
    );
    
  }
  
}
