<?php
namespace Player;

class Recordings extends Player {
  protected $type = 'ondemand';

  public function isHDSEnabled( $info ) {
    return
      $info['organization']['ondemandhdsenabled'] and
      in_array( $this->row['smilstatus'], array('onstorage', 'regenerate') )
    ;
  }

  public function getMediaServers( $info, $hds = null ) {
    $ret = array();

    $prefix = $this->row['issecurestreamingforced']? 'sec': '';
    if ( $hds === null )
      $hds = $this->isHDSEnabled( $info );

    if ( $hds ) {
      $ret[] = $this->getWowzaUrl( $prefix . 'smilurl', false, $info );
    } else {

      if ( $prefix )
        $ret[] = $this->getWowzaUrl( 'secrtmpsurl', true, $info );

      $ret[] = $this->getWowzaUrl( $prefix . 'rtmpurl',  true, $info );
      $ret[] = $this->getWowzaUrl( $prefix . 'rtmpturl', true, $info );
    }

    return $ret;
  }

  public function getWowzaUrl( $type, $needextraparam = false, $info = null ) {
    $url = $this->bootstrap->config['wowza'][ $type ];

    if ( $needextraparam ) {
      $url =
        rtrim( $url, '/' ) .
        $this->getAuthorizeSessionid( $info )
      ;
    }

    return sprintf( $url, $this->streamingserver['server'] );
  }

  protected function getAuthorizeSessionid( $info ) {
    if ( !$info )
      $info = $this->info;

    $ret = sprintf('?sessionid=%s_%s_%s',
      $info['organization']['id'],
      $info['sessionid'],
      $this->row['id']
    );

    if ( isset( $info['tokenauth'] ) and $info['tokenauth'] )
      $ret .= '_' . $info['token'];

    if ( isset( $info['member'] ) and $info['member']['id'] )
      $ret .= '&uid=' . $info['member']['id'];

    return $ret;

  }

  public function generateViewSessionid( $extra ) {
    $ts        = microtime(true);
    $user      = $this->bootstrap->getSession('user');
    $sessionid = session_id();

    return md5( $ts . $sessionid . $this->row['id'] . $extra );
  }

  public function getMediaUrl( $type, $version, $info, $id = null ) {
    if ( !$info )
      $info = $this->info;

    $cookiedomain = $info['organization']['cookiedomain'];
    $sessionid    = $info['sessionid'];
    $host         = '';
    $extension    = 'mp4';
    $authtoken    = $this->getAuthorizeSessionid( $info );
    $extratoken   = '';

    if ( $version ) {
      $extension   = \Springboard\Filesystem::getExtension( $version['filename'] );

      if ( $authtoken )
        $extratoken = '&';
      else
        $extratoken = '?';

      $extratoken .=
        'recordingversionid=' . $version['id'] .
        '&viewsessionid=' . $this->generateViewSessionid( $version['id'] )
      ;

    }

    $user = null;
    if ( isset( $info['member'] ) )
      $user = $info['member'];

    $typeprefix = '';
    if ( $this->row['issecurestreamingforced'] )
      $typeprefix = 'sec';

    switch( $type ) {

      case 'mobilehttp':
        //http://stream.videosquare.hu:1935/vtorium/_definst_/mp4:671/2671/2671_2608_mobile.mp4/playlist.m3u8
        $host        = $this->getWowzaUrl( $typeprefix . 'httpurl');
        $sprintfterm =
          '%3$s:%s/%s/playlist.m3u8' .
          $authtoken .
          $extratoken
        ;

        break;

      case 'mobilertsp':
        //rtsp://stream.videosquare.hu:1935/vtorium/_definst_/mp4:671/2671/2671_2608_mobile.mp4
        $host        = $this->getWowzaUrl( $typeprefix . 'rtspurl');
        $sprintfterm =
          '%3$s:%s/%s' .
          $authtoken .
          $extratoken
        ;

        break;

      case 'direct':
        $host = $this->bootstrap->staticuri;
        $sprintfterm = 'files/recordings/%s/%s';
        break;

      case 'smil':
      case 'contentsmil':
        if ( !$version )
          $version   = array(
            'filename'    => '',
            'recordingid' => $this->row['id'],
          );

        $extension   = 'smil';
        $postfix     = $type == 'contentsmil'? '_content': '';
        $sprintfterm =
          '%3$s:%s/' . $version['recordingid'] . $postfix . '.%3$s/manifest.f4m' .
          $authtoken
        ;

        break;

      case 'content':
      default:
        $sprintfterm = '%3$s:%s/%s';
        break;

    }

    return $host . sprintf( $sprintfterm,
      \Springboard\Filesystem::getTreeDir( $version['recordingid'] ),
      $version['filename'],
      $extension
    );
  }

  public function getLength() {
    return max( $this->row['masterlength'], $this->row['contentmasterlength'] );
  }

  public function getPlayerHeight( $fullscale = false ) {
    if ( $fullscale and $this->row['mastermediatype'] == 'audio' and $this->hasSubtitle() )
      return '140';
    elseif ( $fullscale and $this->row['mastermediatype'] == 'audio' )
      return '60';
    elseif ( $fullscale )
      return '550';

    if ( $this->row['mastermediatype'] == 'audio' and $this->model->hasSubtitle() )
      $height = '120';
    elseif ( $this->row['mastermediatype'] == 'audio' )
      $height = '60';
    else
      $height = '385';

    return $height;
  }

  private function getFlashStreams( $data ) {
    $ret = array(
      'hds'     => array(),
      'desktop' => array(),
      'content' => array(),
      'intro'   => array(),
      'outro'   => array(),
    );

    if ( isset( $data['versions'] ) )
      $versions = $data['versions'];
    else
      $versions = $this->model->getVersions();

    if ( $data['hds'] ) {
      $ret['hds']['master'] = $this->getMediaUrl(
        'smil', null, null
      );
      $ret['hds']['content'] = $this->getMediaUrl(
        'contentsmil', null, null
      );
    }

    foreach( $versions['master']['desktop'] as $version ) {
      $ret['desktop'][] = array(
        'parameters' => array(
          'recordingversionid' => $version['id'],
          'viewsessionid'      => $this->generateViewSessionid( $version['id'] ),
        ),
        'isadaptive'    => $version['isadaptive'],
        'label'         => $version['qualitytag'],
        'dimensions'    => $version['dimensions'],
        'url'           => $this->getMediaUrl('default', $version, null ),
      );
    }
    foreach( $versions['content']['desktop'] as $version ) {
      $ret['content'][] = array(
        'isadaptive'    => $version['isadaptive'],
        'label'         => $version['qualitytag'],
        'dimensions'    => $version['dimensions'],
        'url'           => $this->getMediaUrl('content', $version, null ),
      );
    }

    if ( !$this->row['introrecordingid'] and !$this->row['outrorecordingid'] )
      return $ret;

    $ids     = array();
    $ret     = array();
    $introid = 0;
    $outroid = 0;

    if ( $this->row['introrecordingid'] ) {

      $ids[]   = $this->row['introrecordingid'];
      $introid = $this->row['introrecordingid'];

    }

    if ( $this->row['outrorecordingid'] ) {

      $ids[]   = $this->row['outrorecordingid'];
      $outroid = $this->row['outrorecordingid'];

    }

    $versions = $this->model->getVersions( $ids );
    if ( empty( $versions['master']['desktop'] ) )
      throw new \Exception("The intro/outro does not have desktopcompatible non-content recordings!");

    $type = $data['hds']? 'smil': 'default';
    foreach( $versions['master']['desktop'] as $version ) {

      if ( $version['recordingid'] == $introid )
        $key = 'intro';
      else if ( $version['recordingid'] == $outroid )
        $key = 'outro';
      else // not possible
        throw new \Exception("Invalid version in getIntroOutroFlashdata, neither intro nor outro!");

      $ret[ $key ][] = array(
        'url' => $this->getMediaUrl( $type, $version, null )
      );
    }

    return $ret;
  }

  // TODO refaktor, adatbazis muveleteket a model-be
  private function getSeekbarOptions( $info ) {
    $user = $info['member'];

    if ( !$this->row['isseekbardisabled'] or !$user or !$user['id'] )
      return array();

    // lekerjuk a globalis progresst, mert ha mar egyszer megnezett egy felvetelt
    // akkor onnantol nem erdekel minket semmi, barmit megnezhet ujra
    $timeout     = $info['organization']['viewsessiontimeoutminutes'];
    $needreset   = false;
    $watched     = false;
    $row         = $this->model->db->getRow("
      SELECT
        id,
        position AS lastposition,
        IF(
          timestamp < DATE_SUB(NOW(), INTERVAL $timeout MINUTE),
          1,
          0
        ) AS expired
      FROM recording_view_progress
      WHERE
        userid      = '" . $user['id'] . "' AND
        recordingid = '" . $this->row['id'] . "'
      ORDER BY id DESC
      LIMIT 1
    ");

    if ( $row ) {
      $watched   = $this->model->isRecordingWatched( $info['organization'], $row['lastposition'] );
      $needreset = (bool)$row['expired'];

      // ha lejart de nem nezte meg akkor reset
      if ($needreset and !$watched) {
        $row['lastposition'] = 0;
        $seekbardisabled = true;
        $this->model->db->execute("
          UPDATE recording_view_progress
          SET position = 0
          WHERE id = '" . $row['id'] . "'
          LIMIT 1
        ");
      }
    }

    if ( !$watched and $info['organization']['iselearningcoursesessionbound'] ) {

      // ha session-bound akkor csak az adott sessionben allitjuk vissza
      // a felvetel poziciojat, csak akkor ha nem nezte vegig
      $row = $this->model->db->getRow("
        SELECT positionuntil AS lastposition
        FROM recording_view_sessions
        WHERE
          userid      = '" . $user['id'] . "' AND
          recordingid = '" . $this->row['id'] . "' AND
          sessionid   = " . $this->model->db->qstr( $info['sessionid'] ) . "
        ORDER BY id DESC
        LIMIT 1
      ");

    }

    if ( !$row )
      $row = array('lastposition' => 0);

    $seekbardisabled = !$watched; // ha megnezte akkor nem kell seekbar
    $ret = array(
      'enabled'      => (bool) $watched,
      'lastposition' => (int) $row['lastposition'],
      'interval'     =>
        $this->bootstrap->config['recordingpositionupdateseconds']
      ,
    );

    $ret['visible'] = (
      $seekbardisabled and
      \Model\Userroles::userHasPrivilege(
        $user,
        'general_ignoreAccessRestrictions',
        'or',
        'isclientadmin', 'iseditor', 'isadmin'
      )
    );

    return $ret;
  }

  protected function getConfig( $info, $isembed ) {
    $this->bootstrap->includeTemplatePlugin('indexphoto');

    $this->info = $info;
    $this->isembed = $isembed;
    $user = $this->bootstrap->getSession('user');
    $recordingbaseuri =
      $this->bootstrap->baseuri . \Springboard\Language::get() . '/recordings/'
    ;

    $member = array();
    if ( isset( $info['member'] ) )
      $member = $info['member'];

    // minden ido intervallum masodpercbe
    $data = array(
      'member'          => $member,
      'version'         => $this->bootstrap->config['version'],
      'startposition'   => 0,
      'autoplay'        => false,
      'skipcontent'     => false,
      'tokenauth'       => false,
      'needauth'        => false,
      'nopermission'    => false,
      'tokenvalid'      => true,
      'logo'            => array(),
      'recommendations' => array(),
      'subtitles'       => array(),
      'organization'    => $info['organization'],
      'hds'             => $this->isHDSEnabled( $info ),
      'duration'        => $this->getLength(),
      'seekbar'         => $this->getSeekbarOptions( $info ),
      'presenceCheck'   => array(
        'enabled'  => (bool)$user['ispresencecheckforced'],
        'interval' => $info['organization']['presencechecktimeinterval'],
        'timeout'  => $info['organization']['presencecheckconfirmationtime'],
      ),
      'viewSession' => array(
        'timeout' => $info['organization']['viewsessiontimeoutminutes'] * 60,
      ),
      'extraParameters' => array(
        'recordingid' => $this->row['id'],
      ),
      'thumbnail' => \smarty_modifier_indexphoto(
        $this->row, 'player', $this->bootstrap->staticuri
      ),
      'flashplayer' => array(
        'subtype' => $info['flashplayersubtype'],
        'params'  => $info['flashplayerparams'],
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

    if ( isset( $info['startposition'] ) )
      $data['startposition'] = $info['startposition'];

    if ( isset( $info['autoplay'] ) )
      $data['autoplay'] = $info['autoplay'];

    if ( isset( $info['skipcontent'] ) )
      $data['skipcontent'] = $info['skipcontent'];

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

    $needrecommendation = !$info['organization']['isrecommendationdisabled'];
    // ha tokenauth akkor nincs ajanlo
    if ( $needrecommendation and $data['tokenauth'] )
      $needrecommendation = false;

    if ( $needrecommendation ) {
      if ( isset( $info['relatedvideos'] ) )
        $recommendations = $info['relatedvideos'];
      else
        $recommendations = $this->model->getRelatedVideos(
          $this->bootstrap->config['relatedrecordingcount'],
          $info['member'],
          $info['organization']
        );

      foreach( $recommendations as $video )
        $data['recommendations'][] = array(
          'title'       => $video['title'],
          'subtitle'    => $video['subtitle'],
          'thumbnail'   => \smarty_modifier_indexphoto(
            $video, 'wide', $this->bootstrap->staticuri
          ),
          'url'         =>
            $recordingbaseuri . 'details/' . $video['id'] . ',' .
            \Springboard\Filesystem::filenameize( $video['title'] )
          ,
        );
    }

    $subtitles = $this->model->getSubtitleLanguages();
    if ( !empty( $subtitles ) ) {
      $defaultsubtitle = $this->model->getDefaultSubtitleLanguage();
      $data['subtitles']['show'] = (bool)$defaultsubtitle;

      if ( $defaultsubtitle )
        $data['subtitles']['default'] = $defaultsubtitle;

      $data['subtitles']['files'] = array();
      foreach( $subtitles as $subtitle ) {

        $data['subtitles']['files'][ $subtitle['languagecode'] ] =
          $recordingbaseuri . 'getsubtitle/' . $subtitle['id']
        ;

      }
    }

    $data['attachments'] = array();
    if ( isset( $info['attachments'] ) and $info['attachments'] ) {
      $this->bootstrap->includeTemplatePlugin('attachmenturl');

      foreach( $info['attachments'] as $attachment )
        $data['attachments'][] = array(
          'title'    => $attachment['title'],
          'filename' => $attachment['masterfilename'],
          'url'      => smarty_modifier_attachmenturl(
            $attachment, $this->row, $this->bootstrap->staticuri
          ),
        );
    }

    $data['servers'] = $this->getMediaServers( $info, $data['hds'] );

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
    $l   = $this->bootstrap->getLocalization();
    $ret = array(
      'language'              => \Springboard\Language::get(),
      'api_url'               => $cfg['apiurl'],
      'user_needPing'         => false,
      'track_firstPlay'       => true,
      'recording_id'          => $this->row['id'],
      'recording_title'       => $this->row['title'],
      'recording_subtitle'    => (string)$this->row['subtitle'],
      'recording_description' => (string)$this->row['description'],
      'recording_duration'    => $cfg['duration'],
      'recording_image'       => $cfg['thumbnail'],
      'user_checkWatching'    => $cfg['presenceCheck']['enabled'],
      'user_checkWatchingTimeInterval' => $cfg['presenceCheck']['interval'],
      'user_checkWatchingConfirmationTimeout' => $cfg['presenceCheck']['timeout'],
      'recording_timeout' => $cfg['viewSession']['timeout'],
      // 'timeline_autoPlay' beallitas nem lehet false sose mert a flash player nem indul el
    );

    $streams = $this->getFlashStreams( $cfg );

    if ( $ret['language'] != 'en' )
      $ret['locale'] =
        $this->bootstrap->staticuri . 'js/flash_locale_' . $ret['language'] . '.json'
      ;

    if ( $this->row['mastermediatype'] == 'audio' )
      $ret['recording_isAudio'] = true;

    $ret['user_pingParameters'] = array(
      'recordingid' => $this->row['id'],
    );

    if ( $cfg['logo'] ) {
      $ret['layout_logo'] = $cfg['logo']['url'];
      $ret['layout_logoOrientation'] = 'TR';
      if ( $cfg['logo']['destination'] )
        $ret['layout_logoDestination'] = $cfg['logo']['destination'];
    }

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
      $flashdata['authorization_need']      = true;
      $flashdata['authorization_loginForm'] = false;
      $flashdata['authorization_message']   = $l('recordings', 'token_invalid');
    }

    if ( $cfg['tokenauth'] ) {
      $ret['user_needPing'] = true;
      $ret['user_pingParameters']['token'] = $cfg['token'];
      $ret['user_token'] = $cfg['token'];
    }

    if ( isset( $cfg['member']['id'] ) ) {
      $ret['user_id']          = $cfg['member']['id'];
      $ret['user_needPing']    = true;
      $ret['user_pingSeconds'] = $this->bootstrap->config['sessionpingseconds'];
      $ret['recording_checkTimeout'] = true; // nezzuk hogy timeoutolt e a felvetel
    }

    if ( $cfg['startposition'] )
      $ret['timeline_startPosition'] = $cfg['startposition'];

    $ret += $this->bootstrap->config['flashplayer_extraconfig'];

    $ret['media_servers'] = $cfg['servers'];
    switch( $this->streamingserver['type'] ) {
      case 'wowza':
        $ret['media_serverType'] = 0;
        break;
      case 'nginx':
        $ret['media_serverType'] = 1;
        break;
      default:
        throw new \Exception(
          "Unhandled streaming server type: " .
          $cfg['streamingserver']['type']
        );
        break;
    }

    // default bal oldalon van a video, csak akkor allitsuk be ha kell
    if ( !$this->row['slideonright'] )
      $ret['layout_videoOrientation'] = 'right';

    if ( !empty( $streams['desktop'] ) ) {
      $ret['media_streams']          = array();
      $ret['media_streamLabels']     = array();
      $ret['media_streamParameters'] = array();
      $ret['media_streamDimensions'] = array();

      if ( $cfg['hds'] )
        $ret['media_streams'][] = $streams['hds']['master'];

      foreach( $streams['desktop'] as $version ) {
        $ret['media_streamLabels'][]     = $version['label'];
        $ret['media_streamParameters'][] = $version['parameters'];
        if ( $version['dimensions'] )
          $ret['media_streamDimensions'][] = $version['dimensions'];
        else
          $ret['recording_autoQuality'] = false;

        if (
             !$cfg['organization']['isadaptivestreamingdisabled'] and
             $version['isadaptive']
           )
          $ret['recording_autoQuality'] = true;

        if ( !$cfg['hds'] )
          $ret['media_streams'][] = $version['url'];

      }
    }

    if (
         !$cfg['skipcontent'] and
         !empty( $streams['content'] )
       ) {

      if ( $this->row['contentoffsetstart'] )
        $ret['timeline_contentVirtualStart'] = $this->row['contentoffsetstart'];

      if ( $this->row['contentoffsetend'] )
        $ret['timeline_contentVirtualEnd'] = $this->row['contentoffsetend'];

      $ret['content_streams']      = array();
      $ret['content_streamLabels'] = array();
      $ret['content_streamDimensions'] = array();

      if ( $cfg['hds'] )
        $ret['content_streams'][] = $streams['hds']['content'];

      foreach( $streams['content'] as $version ) {
        $ret['content_streamLabels'][] = $version['label'];
        if ( $version['dimensions'] )
          $ret['content_streamDimensions'][] = $version['dimensions'];
        else
          $ret['recording_autoQuality'] = false;

        if ( !$cfg['hds'] )
          $ret['content_streams'][] = $version['url'];
      }
    }

    if ( $streams['intro'] )
      $ret['intro_streams'] = array( reset( $streams['intro'][0] ) );
    if ( $streams['outro'] )
      $ret['outro_streams'] = array( reset( $streams['outro'][0] ) );

    if ( $this->row['offsetstart'] )
      $ret['timeline_virtualStart'] = $this->row['offsetstart'];

    if ( $this->row['offsetend'] )
      $ret['timeline_virtualEnd'] = $this->row['offsetend'];

    if ( $cfg['subtitles'] ) {
      if ( $cfg['subtitles']['show'] )
        $ret['subtitle_autoShow'] = true;

      if ( $cfg['subtitles']['default'] )
        $ret['subtitle_default'] = $cfg['subtitles']['default'];

      $ret['subtitle_files'] = $cfg['subtitles']['files'];
    }

    if ( $cfg['recommendations'] ) {

      $ret['recommendatory_string'] = array();
      foreach( $cfg['recommendations'] as $video ) {

        $ret['recommendatory_string'][] = array(
          'title'    => $video['title'],
          'subtitle' => $video['subtitle'],
          'image'    => $video['thumbnail'],
          'url'      => $video['url'],
        );

      }
    }

    if ( $cfg['attachments'] )
      $ret['attachments_string'] = $cfg['attachments'];

    if ( $cfg['seekbar'] ) {
      $ret['timeline_seekbarDisabled']          = !$cfg['seekbar']['enabled'];
      $ret['timeline_lastPlaybackPosition']     = $cfg['seekbar']['lastposition'];
      $ret['timeline_lastPositionTimeInterval'] = $cfg['seekbar']['interval'];
      if ( $cfg['seekbar']['visible'] )
        $ret['timeline_seekbarVisible'] = true;
    }

    return $ret;
  }

  private function getFlowStreams( $cfg ) {
    if ( isset( $cfg['versions'] ) )
      $versions = $cfg['versions'];
    else
      $versions = $this->model->getVersions();

    $ret = array(
      'master'  => array(),
      'content' => array(),
      'intro'   => array(),
      'outro'   => array(),
    );

    $master = reset( $versions['master']['desktop'] );
    $ret['master'] = array(
      'type' => 'application/x-mpegurl',
      'url'  => $this->getFlowUrl( $cfg, 'vodabr', $master ),
      'labels' => array(),
    );
    foreach( $versions['master']['desktop'] as $version )
      $ret['master']['labels'][] = $version['qualitytag'];


    $content = reset( $versions['content']['desktop'] );
    if ( !$content )
      return $ret;

    $ret['content'] = array(
      'type' => 'application/x-mpegurl',
      'url'  => $this->getFlowUrl( $cfg, 'vodabr', $content ),
      'labels' => array(),
    );
    foreach( $versions['content']['desktop'] as $version )
      $ret['content']['labels'][] = $version['qualitytag'];

    // TODO intro outro
    return $ret;
  }

  protected function getFlowConfig( $cfg ) {
    if ( !$cfg['hds'] )
      return array();

    $ret = array(
      // the video is loaded on demand, i.e. when the user starts playback with a click
      'splash' => false,
      // By default the embed feature loads the embed script and Flowplayer assets from our CDN. This can be customized in the embed configuration object if you prefer to host the files yourself.
      'embed' => false,
      // minden video wide-screen
      'ratio' => 9/16,
      'clip'  => array(
        // Set a title for this clip. Displayed in a top bar when hovering over the player.
        'title'   => $this->row['title'],
        'sources' => array(),
        'hlsjs'   => array(
          // Whether manual HLS quality switching should be smooth - level change with begin of next segment - or instant. Setting this to false can cause a playback pause on switch.
          'smoothSwitching'     => false,
          // Set to true if you want non fatal hls.js playback errors to trigger Flowplayer errors. Useful for debugging streams and live stream maintenance.
          'strict'              => false,
          // do not die on fatal errors
          'recoverMediaError'   => true,
          'recoverNetworkError' => true,
        ),
      ),
      'vsq' => array(
        'autoplay' => false,
        'duration' => $cfg['duration'],
        // a minosegi valtozatok labeljei, kulon a master es contentnek
        'labels' => array(
          'master'  => array(),
          'content' => array(),
        ),
        'secondarySources' => array(),
      ),
    );

    $streams = $this->getFlowStreams( $cfg );
    $ret['clip']['sources'][] = array(
      'type' => $streams['master']['type'],
      'src'  => $streams['master']['url'],
    );
    $ret['vsq']['labels']['master'] = $streams['master']['labels'];

    if ( empty( $streams['content'] ) )
      return $ret;

    $ret['vsq']['secondarySources'][] = array(
      'type' => $streams['content']['type'],
      'src'  => $streams['content']['url'],
    );
    $ret['vsq']['labels']['content'] = $streams['content']['labels'];

    return $ret;
  }

  public function getContainerID() {
    return $this->info['playercontainerid'];
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
    if ( $info['organization']['ondemandplayertype'] === 'flash' )
      return false;

    return $this->isHDSEnabled( $info );
  }
}
