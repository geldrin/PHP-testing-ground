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
    'chatadmin'            => 'liveadmin|clientadmin',
    'chatexport'           => 'liveadmin|clientadmin',
    'viewers'              => 'liveadmin|clientadmin',
    'togglestream'         => 'liveadmin|clientadmin',
    'getstreamstatus'      => 'liveadmin|clientadmin',
    'checkstreamaccess'    => 'public',
    'securecheckstreamaccess' => 'public',
    'search'               => 'member',
    'analytics'            => 'liveadmin|clientadmin',
    'delete'               => 'liveadmin|clientadmin',
  );
  
  public $forms = array(
    'create'               => 'Visitor\\Live\\Form\\Create',
    'modify'               => 'Visitor\\Live\\Form\\Modify',
    'createfeed'           => 'Visitor\\Live\\Form\\Createfeed',
    'modifyfeed'           => 'Visitor\\Live\\Form\\Modifyfeed',
    'createstream'         => 'Visitor\\Live\\Form\\Createstream',
    'modifystream'         => 'Visitor\\Live\\Form\\Modifystream',
    'createchat'           => 'Visitor\\Live\\Form\\Createchat',
    'analytics'            => 'Visitor\\Live\\Form\\Analytics',
  );
  
  public $paging = array(
    'index'   => 'Visitor\\Live\\Paging\\Index',
    'details' => 'Visitor\\Live\\Paging\\Details',
  );
  
  public $apisignature = array(
    'logview' => array(
      'loginrequired' => false,
      'livefeedid' => array(
        'type' => 'id',
      ),
      'livefeedstreamid' => array(
        'type' => 'id',
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
      'useragent' => array(
        'type' => 'string',
        'required' => false,
      ),
    ),
    'checkaccess' => array(
      'loginrequired' => false,
      'livefeedid'   => array(
        'type' => 'id',
      ),
    ),
  );
  
  public function init() {
    
    parent::init();
    if ( !$this->organization['islivestreamingenabled'] ) {
      
      header('HTTP/1.0 403 Forbidden');
      $this->redirectToController('contents', 'nopermissionlivestreaming');
      
    }

    $this->toSmarty['defaultimage'] =
      $this->bootstrap->staticuri . 'images/live_player_placeholder.png'
    ;

  }
  
  public function viewAction() {
    
    $feedModel = $this->modelIDCheck(
      'livefeeds',
      $this->application->getNumericParameter('id')
    );

    $l         = $this->bootstrap->getLocalization();
    $user      = $this->bootstrap->getSession('user');
    $anonuser  = $this->bootstrap->getSession('anonuser');
    $views     = $this->bootstrap->getSession('livefeed-views');
    $access    = $this->bootstrap->getSession('liveaccess');
    $accesskey = $feedModel->id . '-' . ( $feedModel->row['issecurestreamingforced']? '1': '0');
    
    $access[ $accesskey ] = $feedModel->isAccessible( $user, $this->organization );
    
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
             'departmentorgrouprestricted',
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

    // counters
    if ( !$views[ $feedModel->id ] ) {
      $feedModel->incrementViewCounters();
      $views[ $feedModel->id ] = true;
    }

    $currentstream = $streams['defaultstream'];
    $streamtype    = $streams['streamtype'];
    $info          = array(
      'organization' => $this->organization,
      'sessionid'    => session_id(),
      'ipaddress'    => $this->getIPAddress(),
      'BASE_URI'     => $this->toSmarty['BASE_URI'],
      'cookiedomain' => $this->organization['cookiedomain'],
      'streams'      => $streams,
      'member'       => $user,
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
      
      if ( $this->organization['isplayerlogolinkenabled'] )
        $flashdata['layout_logoDestination'] =
          $this->toSmarty['BASE_URI'] . \Springboard\Language::get() .
          '/live/view/' . $feedModel->id . ',' . $currentstream['id'] . ',' .
          \Springboard\Filesystem::filenameize( $feedModel->row['name'] )
        ;
      
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
    
    if ( $needauth ) {
      $flashdata['authorization_need']      = true;
      $flashdata['authorization_loginForm'] = true;
    }
    
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
      $info
    );
    $this->toSmarty['livertspurl'] = $feedModel->getMediaUrl(
      'livertsp',
      $currentstream['keycode'],
      $info
    );
    
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
    
    $this->toSmarty['title'] = sprintf(
      $l('live', 'view_title'),
      $channelModel->row['title'],
      $feedModel->row['name']
    );

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
  
  public function chatadminAction() {

    $feedModel = $this->modelIDCheck(
      'livefeeds',
      $this->application->getNumericParameter('id')
    );
    $channelModel = $this->modelIDCheck('channels', $feedModel->row['channelid'] );

    // access init, a ->getChat mindig elvarja hogy a session pre-authorized legyen
    $user      = $this->bootstrap->getSession('user');
    $access    = $this->bootstrap->getSession('liveaccess');
    $accesskey = $feedModel->id . '-' . ( $feedModel->row['issecurestreamingforced']? '1': '0');
    $access[ $accesskey ] = $feedModel->isAccessible( $user, $this->organization );

    $this->toSmarty['liveadmin']    = true;
    $this->toSmarty['chatitems']    = $feedModel->getChat();
    $this->toSmarty['chat']         = $this->fetchSmarty('Visitor/Live/Chat.tpl');
    $this->toSmarty['lastmodified'] = md5( $this->toSmarty['chat'] );
    $this->toSmarty['feed']         = $feedModel->row;
    $this->toSmarty['channel']      = $channelModel->row;
    $this->toSmarty['chatpolltime'] = $this->bootstrap->config['chatpolltimems'];
    $this->smartyOutput('Visitor/Live/ChatAdmin.tpl');

  }

  public function chatexportAction() {
    $feedModel = $this->modelIDCheck(
      'livefeeds',
      $this->application->getNumericParameter('id')
    );
    $channelModel = $this->modelIDCheck('channels', $feedModel->row['channelid'] );
    $l = $this->bootstrap->getLocalization();

    $chatrs   = $feedModel->getAllChat();
    $filename = sprintf(
      "%s-%s-%s-chat.csv",
      \Springboard\Filesystem::filenameize( $channelModel->row['title'] ),
      $feedModel->row['channelid'],
      $feedModel->id
    );

    include_once( $this->bootstrap->config['templatepath'] . 'Plugins/modifier.nickformat.php');

    header("Pragma: ");
    header("Cache-Control: ");
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=' . $filename );

    $delim  = ";";
    $handle = fopen('php://output', 'w');
    $fields = array(
      $l('live', 'chat_csv_timestamp'),
      $l('live', 'chat_csv_nick'),
      $l('live', 'chat_csv_text'),
      $l('live', 'chat_csv_isquestion'),
      $l('live', 'chat_csv_moderation'),
      $l('live', 'chat_csv_ipaddress'),
    );
    fputcsv( $handle, $fields, $delim );

    foreach( $chatrs as $row ) {
      if ( $row['userid'] )
        $nick = smarty_modifier_nickformat( $row );
      else
        $nick = $row['anonymoususer'];

      $data = array(
        $row['timestamp'],
        $nick,
        $row['text'],
        $row['isquestion'],
        $l->getLov('chatmoderation', null, $row['moderated']),
        $row['ipaddress'],
      );
      fputcsv( $handle, $data, $delim );
    }

    fclose( $handle );

  }

  public function deleteAction() {

    $channelModel = $this->modelOrganizationAndUserIDCheck(
      'channels',
      $this->application->getNumericParameter('id')
    );

    $forward = $this->application->getParameter(
      'forward', 'live'
    );

    if ( !$channelModel->row['isliveevent'] )
      $this->redirect( $forward );

    $channelModel->markAsDeleted();

    $feedModel = $this->bootstrap->getModel("livefeeds");
    $feeds = $channelModel->getFeeds();
    foreach( $feeds as $feed ) {
      $feedModel->id = $feed['id'];
      $feedModel->markAsDeleted();
    }

    $this->redirect( $forward );

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
    
    $feedModel->updateRow( array(
        'smilstatus'        => 'regenerate',
        'contentsmilstatus' => 'regenerate',
      )
    );
    $feedModel->markAsDeleted();

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

    $streamModel->markAsDeleted();
    $feedModel->updateRow( array(
        'smilstatus'        => 'regenerate',
        'contentsmilstatus' => 'regenerate',
      )
    );

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
    $feedModel->updateRow( array(
        'smilstatus'        => 'regenerate',
        'contentsmilstatus' => 'regenerate',
      )
    );

    $this->redirect(
      $this->application->getParameter(
        'forward',
        'live/managefeeds/' . $channelModel->id
      )
    );
    
  }
  
  public function getchatAction( $livefeedid = null, $ret = array() ) {
    
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
      
      if ( $cache->expired() ) {
        
        $feedModel = $this->modelIDCheck( 'livefeeds', $livefeedid );
        $chat      = $feedModel->getChat();
        
        $cache->put( $chat );
        
      } else
        $chat = $cache->get();
      
      $this->toSmarty['anonuser']  = $this->bootstrap->getSession('anonuser');
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
    
    $ret['status']       = 'success';
    $ret['lastmodified'] = $data['lastmodified'];
    $ret['html']         = $data['html'];
    $ret['polltime']     = $this->bootstrap->config['chatpolltimems'];
    $this->jsonOutput( $ret );
    
  }
  
  public function expireChatCache( $livefeedid ) {
    $this->getChatCache( $livefeedid )->expire();
  }
  
  public function getChatCache( $livefeedid ) {
    
    return $this->bootstrap->getCache(
      sprintf('livefeed_chat-%d', $livefeedid ),
      null,
      true
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
        '/(?P<organizationid>\d+)_' .
        '(?P<sessionid>[a-z0-9]{32})_' .
        '(?P<feedid>\d+)/',
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
        $access    = $this->bootstrap->getSession('liveaccess');
        $accesskey = $matches['feedid'] . '-' . (int)$secure;
        
        if ( $access[ $accesskey ] !== true ) {

          $user      = $this->bootstrap->getSession('user');
          $feedModel = $this->modelIDCheck('livefeeds', $matches['feedid'], false );

          if ( $feedModel ) {

            $access[ $accesskey ] = $feedModel->isAccessible(
              $user, $organization, $secure
            );

            if ( $access[ $accesskey ] === true )
              $result = '1';

          }
          
        } else
          $result = '1';

      }

    }

    if ( $this->bootstrap->config['livecheckaccessdebuglog'] )
      \Springboard\Debug::getInstance()->log(
        false,
        'livecheckaccessdebug.txt',
        "LIVESECURE: $secure | RESULT: $result\n" .
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
  
  public function searchAction() {
    
    $term   = $this->application->getParameter('term');
    $output = array(
    );
    
    if ( !$term )
      $this->jsonoutput( $output );
    
    $user          = $this->bootstrap->getSession('user');
    $livefeedModel = $this->bootstrap->getModel('livefeeds');
    $results       = $livefeedModel->search( $term, $user['id'], $this->organization['id'] );
    
    if ( empty( $results ) )
      $this->jsonoutput( $output );
    
    $img = $this->bootstrap->staticuri . 'images/videothumb_wide_placeholder.png';
    include_once( $this->bootstrap->config['templatepath'] . 'Plugins/modifier.shortdate.php');

    foreach( $results as $result ) {

      $label = $result['channeltitle'] . '<br/>(' . $result['name'] . ')';
      if ( $result['starttimestamp'] )
        $label .= '<br/>' . smarty_modifier_shortdate(
          '%Y. %B %e',
          $result['starttimestamp'],
          $result['endtimestamp']
        );

      $data = array(
        'value' => $result['id'],
        'label' => $label,
        'img'   => $img,
      );
      
      $output[] = $data;
      
    }
    
    $this->jsonoutput( $output );
    
  }
  
  public function transformStatistics( $data ) {

    $l          = $this->bootstrap->getLocalization();
    $ret        = array(
      'origstartts'  => strtotime( $data['originalstarttimestamp'] ) * 1000,
      'origendts'    => strtotime( $data['originalendtimestamp'] ) * 1000,
      'startts'      => $data['starttimestamp'] * 1000,
      'endts'        => $data['endtimestamp'] * 1000,
      'stepinterval' => $data['step'] * 1000,
      'labels'       => array(),
      'data'         => array(),
    );

    // prepare the chart labels
    foreach( $data['data'] as $value ) {

      foreach( $value as $field => $v )
        $ret['labels'][] = $l('live', 'stats_' . $field );

      break;

    }

    $ret['labels'][] = $l('live', 'stats_sum');

    // prepare the values
    foreach( $data['data'] as $key => $value ) {

      $row = array(
        intval( $value['timestamp'] ) * 1000,
      );

      $sum = 0;
      foreach( $value as $field => $v ) {

        if ( $field == 'timestamp' )
          continue;

        $v = intval( $v );
        $row[] = $v;
        $sum += $v;

      }

      unset( $data['data'][ $key ] );
      $row[] = $sum;
      $ret['data'][] = $row;

    }

    return $ret;

  }

  public function logviewAction( $livefeedid, $livefeedstreamid, $viewsessionid, $action, $streamurl, $useragent = '' ) {
    
    $statModel = $this->bootstrap->getModel('view_statistics_live');
    $user      = $this->bootstrap->getSession('user');
    $ipaddress = $this->getIPAddress();
    $sessionid = session_id();
    $useragent .= "\n" . $_SERVER['HTTP_USER_AGENT'];

    $values = array(
      'userid'             => $user['id'],
      'livefeedid'         => $livefeedid,
      'livefeedstreamid'   => $livefeedstreamid,
      'sessionid'          => $sessionid,
      'viewsessionid'      => $viewsessionid,
      'action'             => $action,
      'url'                => $streamurl,
      'ipaddress'          => $ipaddress,
      'useragent'          => $useragent,
    );

    $statModel->log( $values );
    return true;

  }

  public function checkaccessAction( $livefeedid ) {
    $user        = $this->bootstrap->getSession('user');
    $ret         = array(
      'hasaccess' => false,
    );

    $feedModel = $this->bootstrap->getModel('livefeeds');
    $feedModel->select( $livefeedid );

    if ( !$feedModel->row )
      return $ret;

    $access = $feedModel->isAccessible( $user, $this->organization );

    if ( $access === true )
      $ret['hasaccess'] = true;
    else {
      $ret['hasaccess'] = false;
      $ret['reason']    = $access;
    }

    return $ret;
  }

  public function viewersAction() {
    $livefeedid = $this->application->getNumericParameter('livefeedid');
    $ret = array('success' => false);

    if ( $livefeedid <= 0 )
      $this->jsonOutput( $ret );

    // 60 masodperces cache lejarat, nem nyelv specifikus
    $cache = $this->bootstrap->getCache( "livefeed-viewers-$livefeedid", 60, true );
    if ( $cache->expired() ) {
      $feedModel = $this->bootstrap->getModel('livefeeds');
      $feedModel->select( $livefeedid );

      if ( // feedid invalid
           !$feedModel->row or
           $feedModel->row['organizationid'] != $this->organization['id']
         )
        return $ret;

      include_once( $this->bootstrap->config['templatepath'] . 'Plugins/modifier.numberformat.php' );
      $data = $feedModel->getViewers();
      $cache->put( array(
          'currentviewers'          => $data,
          'formattedcurrentviewers' => smarty_modifier_numberformat( $data ),
        )
      );

    }

    $ret = array(
      'success' => true,
      'data'    => $cache->get(),
    );

    $this->jsonOutput( $ret );

  }

}
