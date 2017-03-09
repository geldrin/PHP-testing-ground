<?php
namespace Player;

class Live extends Player {
  protected $type = 'live';

  private function isAdaptive( $organization ) {
    if ( $organization['isadaptivestreamingdisabled'] )
      return false;

    if ( !$this->row['livestreamgroupid'] )
      return false;

    $groupid = $this->model->db->qstr( $this->row['livestreamgroupid'] );
    return (bool)$this->model->db->getOne("
      SELECT lg.isadaptive
      FROM livestream_groups AS lg
      WHERE lg.id = $groupid
      LIMIT 1
    ");
  }

  public function getStreamsForBrowser( $browser, $defaultKeycode = null ) {
    $streams         = $this->model->getStreams();
    $narrowedstreams = array();
    $defaultstream   = null;

    if ( empty( $streams ) )
      return false;

    if (
         $browser['mobile'] and
         $browser['mobiledevice'] != 'iphone' and
         $browser['mobiledevice'] != 'android'
       )
      $unknown = true;
    else
      $unknown = false;

    foreach( $streams as $stream ) {

      if (
           ( !$browser['mobile'] and $stream['isdesktopcompatible'] ) or
           ( $browser['mobiledevice'] == 'iphone' and $stream['isioscompatible'] ) or
           ( $browser['mobiledevice'] == 'android' and $stream['isandroidcompatible'] ) or
           $unknown
         )
        $narrowedstreams[ $stream['id'] ] = $stream;

    }

    // nem talaltunk streamet ami raillik a browserre igy minden stream lehetseges, a default az elso lesz
    if ( empty( $narrowedstreams ) ) {

      foreach( $streams as $stream )
        $narrowedstreams[ $stream['id'] ] = $stream;

    }

    if ( !$defaultKeycode ) {

      $defaultstream = reset( $narrowedstreams );
      if ( $browser['mobile'] and $browser['tablet'] ) {

        foreach( $narrowedstreams as $stream ) {

          if (
               (
                 ( $browser['mobiledevice'] == 'iphone' and $stream['isioscompatible'] ) or
                 ( $browser['mobiledevice'] == 'android' and $stream['isandroidcompatible'] )
               )
             ) {

            $defaultstream = $stream;
            break;

          }

        }

      }

    } elseif ( $defaultKeycode and !isset( $narrowedstreams[ $defaultKeycode ] ) )
      return false;
    else
      $defaultstream = $narrowedstreams[ $defaultKeycode ];

    if ( // ha nem mobil vagy nem ismert mobil device, de a stream desktop kompat
         ( !$browser['mobile'] and $defaultstream['isdesktopcompatible'] ) or
         ( $defaultstream['isdesktopcompatible'] and $unknown )
       )
      $streamtype = 'desktop';
    elseif ( // ha mobil es android, vagy mobil es ismeretlen de a stream android kompat
            (
              $browser['mobile'] and
              $browser['mobiledevice'] == 'android' and
              $defaultstream['isandroidcompatible']
            ) or
            ( $defaultstream['isandroidcompatible'] and $unknown )
           )
      $streamtype = 'android';
    elseif ( // ha mobil es ios, vagy mobil es ismeretlen de a stream ios kompat
            (
              $browser['mobile'] and
              $browser['mobiledevice'] == 'iphone' and
              $defaultstream['isioscompatible']
            ) or
            ( $defaultstream['isioscompatible'] and $unknown )
           )
      $streamtype = 'ios';
    elseif ( $defaultstream['isdesktopcompatible'] ) // peldaul ha ismert mobile device de nem kompatibilis a stream akkor ez a fallback sorrend
      $streamtype = 'desktop';
    elseif ( $defaultstream['isandroidcompatible'] )
      $streamtype = 'android';
    elseif ( $defaultstream['isioscompatible'] )
      $streamtype = 'ios';
    else
      throw new \Exception(
        "Unhandled stream type: mobile device: " . $browser['mobiledevice'] .
        " defaultstream: " . var_export( $defaultstream, true )
      );

    return array(
      'streams'       => $narrowedstreams,
      'defaultstream' => $defaultstream,
      'streamtype'    => $streamtype,
    );
  }

  private function getStreamURL( $prefix, $stream, $info ) {
    if ( $this->isHDSEnabled( $prefix, $info ) ) {
      $authorizecode = $this->getAuthorizeSessionid( $info );
      $smilurl       = 'smil:%s.smil/manifest.f4m%s';
      $filename      = $this->model->id;

      if ( $prefix )
        $filename .= '_' . $prefix;

      return sprintf( $smilurl, $filename, $authorizecode );
    } else
      return $stream[ $prefix . 'keycode'];
  }

  private function getStreamInfo( $info ) {
    $ret = array(
      'master'  => array(),
      'content' => array(),
    );

    foreach( $info['streams']['streams'] as $stream ) {
      $val = array(
        'url'        => $this->getStreamURL('', $stream, $info ),
        'label'      => $stream['qualitytag'],
        'parameters' => array(
          'livefeedstreamid' => $stream['id'],
          'viewsessionid'    => $this->generateViewSessionid( $stream['id'] ),
        ),
      );
      $ret['master'][] = $val;

      unset( $val['parameters'] );
      $val['url'] = $this->getStreamURL('content', $stream, $info );
      $ret['content'][] = $val;
    }

    return $ret;
  }

  private function isHDSEnabled( $prefix = '', $info ) {
    return
      $info['organization']['livehdsenabled'] and
      in_array( $this->row[ $prefix . 'smilstatus'], array('onstorage', 'regenerate') )
    ;
  }

  private function getMediaServers( $info ) {
    $ret = array(
      'master'  => array(),
      'content' => array(),
    );

    $authorizecode = $this->getAuthorizeSessionid( $info );
    $prefix        = $this->row['issecurestreamingforced']? 'sec': '';
    $hds           = $this->isHDSEnabled( '', $info );

    $prefix = $this->row['issecurestreamingforced']? 'sec': '';
    if ( $hds ) {
      $ret['master'][] =
        $this->bootstrap->config['wowza'][ $prefix . 'livesmilurl' ]
      ;
    } else {

      if ( $this->row['issecurestreamingforced'] )
        $ret['master'] = array(
          rtrim( $this->bootstrap->config['wowza']['seclivertmpsurl'], '/' ) . $authorizecode,
          rtrim( $this->bootstrap->config['wowza']['seclivertmpeurl'], '/' ) . $authorizecode,
          rtrim( $this->bootstrap->config['wowza']['secliveurl'], '/' ) . $authorizecode,
        );
      else
        $ret['master'] = array(
          rtrim( $this->bootstrap->config['wowza']['livertmpurl'], '/' ) . $authorizecode,
          rtrim( $this->bootstrap->config['wowza']['liveurl'], '/' ) . $authorizecode,
        );

    }

    $streamingserver = $this->streamingserver;
    if ( empty( $streamingserver ) )
      throw new \Exception("No streaming server found, not even the default");

    foreach( $ret['master'] as $key => $url )
      $ret['master'][ $key ] = sprintf( $url, $streamingserver['server'] );

    $contenthds = $this->isHDSEnabled('content', $info );
    if ( $hds == $contenthds ) {

      $ret['content'] = $ret['master'];
      return $ret;

    } elseif ( $contenthds )
      $ret['content'][] =
        rtrim( $this->bootstrap->config['wowza'][ $prefix . 'livesmilurl' ], '/' ) . $authorizecode
      ;
    else {

      if ( $this->row['issecurestreamingforced'] )
        $ret['content'] = array(
          rtrim( $this->bootstrap->config['wowza']['seclivertmpsurl'], '/' ) . $authorizecode,
          rtrim( $this->bootstrap->config['wowza']['seclivertmpeurl'], '/' ) . $authorizecode,
          rtrim( $this->bootstrap->config['wowza']['secliveurl'], '/' ) . $authorizecode,
        );
      else
        $ret['content'] = array(
          rtrim( $this->bootstrap->config['wowza']['livertmpurl'], '/' ) . $authorizecode,
          rtrim( $this->bootstrap->config['wowza']['liveurl'], '/' ) . $authorizecode,
        );

    }

    foreach( $ret['content'] as $key => $url )
      $ret['content'][ $key ] = sprintf( $url, $streamingserver['server'] );

    return $ret;
  }

  private function generateViewSessionid( $extra ) {
    $ts        = microtime(true);
    $user      = $this->bootstrap->getSession('user');
    $sessionid = session_id();

    return md5( $ts . $sessionid . $this->model->id . $extra );
  }

  private function getIntroData( $info ) {
    $ret = array(
      'servers' => array(),
      'streams' => array(),
    );

    if ( !$this->row['introrecordingid'] )
      return $ret;

    $recordingsModel = $this->bootstrap->getModel('recordings');
    $recordingsModel->select( $this->row['introrecordingid'] );
    $versions = $recordingsModel->getVersions();

    if ( empty( $versions['master']['desktop'] ) )
      throw new \Exception("The placeholder does not have desktopcompatible recordings!");

    $recordingsModel->row['issecurestreamingforced'] = $this->row['issecurestreamingforced'];
    $ret['servers'] = $recordingsModel->getMediaServers(
      $info, $this->isHDSEnabled( '', $info )
    );

    foreach( $versions['master']['desktop'] as $version ) {
      $ret['streams'][] = array(
        'label' => $version['qualitytag'],
        'url'   => $recordingsModel->getMediaUrl(
          'default', $version, $info
        ),
      );
    }

    return $ret;
  }

  protected function getAuthorizeSessionid( $info ) {
    if ( !$info )
      $info = $this->info;

    if (
         !isset( $info['organization'] ) or
         !isset( $info['sessionid'] ) or
         !$info['sessionid']
       )
      return '';

    $ret = sprintf('?sessionid=%s_%s_%s',
      $info['organization']['id'],
      $info['sessionid'],
      $this->model->id
    );

    if ( isset( $info['tokenauth'] ) and $info['tokenauth'] )
      $ret .= '_' . $info['token'];

    if ( isset( $info['member'] ) and $info['member']['id'] )
      $ret .= '&uid=' . $info['member']['id'];

    return $ret;
  }

  public function getMediaUrl( $type, $streamcode, $info ) {
    $url = $this->bootstrap->config['wowza'][ $type . 'url' ] . $streamcode;
    $sessionid = $info['sessionid'];
    if ( isset( $info['member'] ) )
      $user = $info['member'];
    else
      $user = null;

    switch( $type ) {

      case 'livehttp':
        //http://stream.videosquare.eu/devvsqlive/123456/playlist.m3u8
        $url .=
          '/playlist.m3u8' .
          $this->getAuthorizeSessionid( $info )
        ;

        break;

      case 'livertsp':
        //rtsp://stream.videosquare.eu/devvsqlive/123456
        $url .= $this->getAuthorizeSessionid( $info );

        break;

    }

    $this->setupStreamingServer();
    return sprintf( $url, $this->streamingserver['server'] );
  }

  public function getLength() {
    return null;
  }

  public function getPlayerHeight( $fullscale = false ) {
    if ( !$fullscale )
      return 385;

    return 550;
  }

  protected function getConfig( $info, $isembed ) {
    $this->bootstrap->includeTemplatePlugin('indexphoto');

    $user = $this->bootstrap->getSession('user');
    if ( empty( $info['streams'] ) )
      $info['streams'] = $this->getStreamsForBrowser( $info['browser'] );

    // minden ido intervallum masodpercbe
    $data = array(
      'member'        => $user,
      'organization'  => $info['organization'],
      'browser'       => $info['browser'],
      'tokenauth'     => false,
      'needauth'      => false,
      'nopermission'  => false,
      'tokenvalid'    => true,
      'logo'          => array(),
      'hds'           => $this->isHDSEnabled( '', $info ),
      'adaptive'      => $this->isAdaptive( $info['organization'] ),
      'streams'       => $info['streams'],
      'streaminfo'    => $this->getStreamInfo( $info ),
      'intro'         => $this->getIntroData( $info ),
      'title'         => $info['title'],
      'presenceCheck' => array(
        'enabled'  => (bool)$user['ispresencecheckforced'],
        'interval' => $info['organization']['presencechecktimeinterval'],
        'timeout'  => $info['organization']['presencecheckconfirmationtime'],
      ),
      'viewSession' => array(
        'timeout' => $info['organization']['viewsessiontimeoutminutes'] * 60,
      ),
      'extraParameters' => array(
        'livefeedid' => $this->model->id,
      ),
      'thumbnail' => \smarty_modifier_indexphoto(
        $this->row, 'player', $this->bootstrap->staticuri
      ),
      'flashplayer' => array(
        'subtype'      => $info['flashplayersubtype'],
        'params'       => $info['flashplayerparams'],
        'authcallback' => '',
      ),
    );

    if ( $this->bootstrap->config['forcesecureapiurl'] )
      $apiurl = 'https://' . $info['organization']['domain'] . '/';
    else
      $apiurl = $this->bootstrap->baseuri;

    $this->setupStreamingServer();

    $data['apiurl'] = $apiurl . 'jsonapi';

    if ( isset( $info['logo'] ) )
      $data['logo'] = $info['logo'];

    if ( isset( $info['needauth'] ) )
      $data['needauth'] = $info['needauth'];

    if ( isset( $info['nopermission'] ) )
      $data['nopermission'] = $info['nopermission'];

    if ( isset( $info['tokenvalid'] ) )
      $data['tokenvalid'] = $info['tokenvalid'];

    if ( isset( $info['tokenauth'] ) and $info['tokenauth'] ) {
      $data['tokenauth'] = true;
      $data['token'] = $info['token'];
    }

    $data['servers'] = $this->getMediaServers( $info );

    return $data;
  }

  protected function getFlashConfig( $cfg ) {
    $ret = $cfg['flashplayer'];
    $ret['config'] = $this->bootstrap->getSignedPlayerParameters(
      $this->getFlashData( $cfg )
    );
    return $ret;
  }

  protected function getFlashData( $cfg ) {
    $l = $this->bootstrap->getLocalization();
    $ret = array(
      'language'               => \Springboard\Language::get(),
      'api_url'                => $cfg['apiurl'],
      'user_needPing'          => false,
      'feed_id'                => $this->model->id,
      'recording_title'        => $this->row['name'],
      'recording_type'         => 'live',
      'recording_autoQuality'  => false, // nincs stream resolution adat; off
      'timeline_autoPlay'      => true,
      'user_checkWatching'     => (bool)$cfg['presenceCheck']['enabled'],
      'user_checkWatchingTimeInterval' => $cfg['presenceCheck']['interval'],
      'user_checkWatchingConfirmationTimeout' => $cfg['presenceCheck']['timeout'],
      'media_streams'          => array(),
      'media_servers'          => $cfg['servers']['master'],
      'media_streamLabels'     => array(),
      'media_streamParameters' => array(),
      'media_secondyStreams'   => array(),
      'media_secondaryServers' => $cfg['servers']['content'],
      'content_streamLabels'   => array(),
    );

    if ( $ret['language'] != 'en' )
      $ret['locale'] =
        $this->bootstrap->staticuri . 'js/flash_locale_' . $ret['language'] . '.json'
      ;

    switch( $this->streamingserver['type'] ) {
      case 'wowza':
        $ret['media_serverType'] = 0;
        break;
      case 'nginx':
        $ret['media_serverType'] = 1;
        break;
      default:
        throw new \Exception(
          "Unhandled streaming server type: " . $cfg['streamingserver']['type']
        );
    }

    if ( $cfg['adaptive'] )
      $ret['recording_autoQuality'] = true;

    if ( $cfg['needauth'] ) {
      $ret['authorization_need']      = true;
      $ret['authorization_loginForm'] = true;
    }

    if ( $cfg['nopermission'] ) {
      $ret['authorization_need']      = true;
      $ret['authorization_loginForm'] = false;
      $ret['authorization_message']   = $l('recordings', 'nopermission');
    }
    if ( !$cfg['tokenvalid'] ) {
      $ret['authorization_need']      = true;
      $ret['authorization_loginForm'] = false;
      $ret['authorization_message']   = $l('recordings', 'token_invalid');
    }

    if ( $cfg['flashplayer']['authcallback'] )
      $ret['authorization_callback'] = $cfg['flashplayer']['authcallback'];

    foreach( $cfg['streaminfo']['master'] as $stream ) {
      $ret['media_streams'][]          = $stream['url'];
      $ret['media_streamLabels'][]     = $stream['label'];
      $ret['media_streamParameters'][] = $stream['parameters'];
    }
    foreach( $cfg['streaminfo']['content'] as $stream ) {
      $ret['media_secondaryStreams'][] = $stream['url'];
      $ret['content_streamLabels'][]   = $stream['label'];
    }

    $ret['user_pingParameters'] = $cfg['extraParameters'];

    if ( isset( $cfg['tokenauth'] ) and $cfg['tokenauth'] ) {
      $ret['user_needPing'] = true;
      $ret['user_pingParameters']['token'] = $cfg['token'];
      $ret['user_token'] = $cfg['token'];
    }

    if ( $cfg['member'] and $cfg['member']['id'] ) {
      $ret['user_id']          = $cfg['member']['id'];
      $ret['user_needPing']    = true;
      $ret['user_pingSeconds'] = $this->bootstrap->config['sessionpingseconds'];
    }

    $ret = $ret + $this->bootstrap->config['flashplayer_extraconfig'];

    if ( !$this->row['slideonright'] )
      $ret['layout_videoOrientation'] = 'right';

    if ( $this->row['introrecordingid'] ) {
      $ret['livePlaceholder_servers']      = $cfg['intro']['servers'];
      $ret['livePlaceholder_streams']      = array();
      $ret['livePlaceholder_streamLabels'] = array();

      foreach( $cfg['intro']['streams'] as $stream ) {
        $ret['livePlaceholder_streamLabels'][] = $stream['label'];
        $ret['livePlaceholder_streams'][] = $stream['url'];
      }

      $ret['intro_servers']      = $ret['livePlaceholder_servers'];
      $ret['intro_streams']      = $ret['livePlaceholder_streams'];
      $ret['intro_streamLabels'] = $ret['livePlaceholder_streamLabels'];
    }

    return $ret;
  }

  public function getWidth( $isembed ) {
    if ( !$isembed )
      return 980;

    return 480;
  }

  public function getHeight( $isembed ) {
    return $this->getPlayerHeight( !$isembed );
  }

  protected function needFlowPlayer( $info ) {
    if ( $info['organization']['liveplayertype'] === 'flash' )
      return false;

    return $this->isHDSEnabled( '', $info );
  }

  protected function getFlowStreams( $cfg ) {
    $ret = array(
      'master'  => array(),
      'content' => array(),
      'intro'   => array(),
      'outro'   => array(),
    );

    $ret['master'] = array(
      'type' => 'application/x-mpegurl',
      'url'  => '',
      'labels' => array(),
    );

    //$hascontent = $this->model->row['hascontent'];
    $stream = reset( $cfg['streams']['streams'] );
    $hascontent = (bool) $stream['contentkeycode'];
    if ( $hascontent )
      $ret['content'] = array(
        'type' => 'application/x-mpegurl',
        'url'  => '',
        'labels' => array(),
      );

    foreach( $cfg['streams']['streams'] as $stream ) {
      $extraparams = array(
        'livefeedstreamid' => $stream['id'],
        'viewsessionid'    => $this->generateViewSessionid( $stream['id'] ),
      );

      // "mock" version
      $ver = array(
        'livefeedid' => $this->row['id'],
        'iscontent'  => false,
      );

      $ret['master']['url'] = $this->getFlowUrl( $cfg, 'liveabr', $ver, $extraparams );
      $ret['master']['labels'][] = $stream['qualitytag'];

      if ( !$hascontent )
        continue;

      $ver['iscontent'] = true;
      $ret['content']['url'] = $this->getFlowUrl( $cfg, 'liveabr', $ver, $extraparams );
      $ret['content']['labels'][] = $stream['qualitytag'];
    }

    // TODO intro outro
    return $ret;
  }

  protected function getFlowConfig( $cfg ) {
    $ret = parent::getFlowConfig( $cfg );
    $ret['vsq']['parameters']['feedid'] = $this->row['id'];
    return $ret;
  }
}
