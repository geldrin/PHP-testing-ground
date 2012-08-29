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
  
  public function viewAction() {
    
    $feedModel = $this->modelIDCheck(
      'livefeeds',
      $this->application->getNumericParameter('id')
    );
    
    $user       = $this->bootstrap->getSession('users');
    $accessible = $feedModel->isAccessible( $user );
    
    if ( $accessible !== true )
      $this->redirectToController('contents', $accessible );
    
    $channelModel = $this->modelIDCheck('channels', $feedModel->row['channelid'] );
    $streamid     = $this->application->getNumericParameter('streamid');
    $browserinfo  = $this->bootstrap->getSession('browser');
    
    if ( !count( $browserinfo ) )
      $browserinfo->setArray( \Springboard\Browser::getInfo() );
    
    $streams      = $feedModel->getStreams( $browserinfo['mobile'] );
    
    if ( $streamid and isset( $streams[ $streamid ] ) )
      $currentstream = $streams[ $streamid ];
    else
      $currentstream = reset( $streams );
    
    $flashdata = array(
      'language'        => \Springboard\Language::get(),
      'media_servers'   => array( $this->bootstrap->config['wowza']['liveingressurl'] ),
      'media_streams'   => array( $currentstream['keycode'] ),
      'recording_title' => $feedModel->row['name'],
      'recording_type'  => 'live',
    );
    
    if ( $feedModel->row['numberofstreams'] == 2 ) {
      
      $flashdata['media_secondaryServers'] = array( $this->bootstrap->config['wowza']['liveingressurl'] );
      $flashdata['media_secondaryStreams'] = array( $currentstream['contentkeycode'] );
      
    }
    
    if ( !$feedModel->row['slideonright'] )
      $flashdata['layout_videoOrientation'] = 'right';
    
    if ( $flashdata['language'] != 'en' )
      $flashdata['locale'] =
        $this->toSmarty['STATIC_URI'] .
        'js/flash_locale_' . $flashdata['language'] . '.json'
      ;
    
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
    
    $this->toSmarty['channel']       = $channelModel->row;
    $this->toSmarty['streams']       = $streams;
    $this->toSmarty['feed']          = $feedModel->row;
    $this->toSmarty['currentstream'] = $currentstream;
    $this->toSmarty['liveurl']       = $this->bootstrap->config['wowza']['liveurl'];
    
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
  
  public function getchatAction( $livefeedid = null ) {
    
    if ( !$livefeedid )
      $livefeedid = $this->application->getNumericParameter('id');
    
    if ( !$livefeedid or $livefeedid < 0 )
      $this->jsonOutput( array('status' => 'error') );
    
    $cache = $this->getChatCache( $livefeedid );
    
    if ( $cache->expired() ) {
        
      $feedModel = $this->modelIDCheck( 'livefeeds', $livefeedid );
      $start     = $this->application->getNumericParameter('start', 0);
      if ( $start < 0 )
        $start   = 0;
      
      $limit = 200;
      $chat  = $feedModel->getChat( $start, $limit );
      $data  = array(
        'lastmodified' => 0,
      );
      
      if ( count( $chat ) )
        $data['lastmodified'] = $chat[ count( $chat ) - 1]['timestamp'];
      
      $this->toSmarty['lastmodified'] = $data['lastmodified'];
      $this->toSmarty['chat']         = $chat;
      $data['html']                   = $this->fetchSmarty('Visitor/Live/Chat.tpl');
      
      $cache->put( $data );
      
    } else
      $data = $cache->get();
    
    $this->jsonOutput( array(
        'status'       => 'success',
        'lastmodified' => $data['lastmodified'],
        'html'         => $data['html'],
      )
    );
    
  }
  
  public function getChatCache( $livefeedid ) {
    return $this->bootstrap->getCache('livefeed_chat-' . $livefeedid );
  }
  
  public function moderatechatAction() {
    
    $chatModel = $this->modelIDCheck(
      'livefeed_chat',
      $this->application->getNumericParameter('id')
    );
    
    $feedModel = $this->modelOrganizationAndUserIDCheck(
      'livefeeds',
      $chatModel->row['livefeedid']
    );
    
    $chatModel->updateRow( array(
        'moderated' => 1
      )
    );
    
    $this->getChatCache( $feedModel->id )->expire();
    return $this->getchatAction( $feedModel->id );
    
  }
  
}
