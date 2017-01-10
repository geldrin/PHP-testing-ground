<?php
namespace Player;

abstract class Player {
  protected $type;

  public $bootstrap;
  protected $row = array();
  protected $info = array();
  protected $model;
  protected $streamingserver;

  public function __construct( $bootstrap, $model ) {
    $this->bootstrap = $bootstrap;
    $this->model = $model;
    $this->row = $model->row;
  }

  public function setInfo( $info ) {
    $this->info = $info;
  }

  public function forceMediaServer( $id ) {
    $streamModel = $this->bootstrap->getModel('streamingservers');
    return $this->streamingserver = $streamModel->getByID( $id );
  }

  protected function setupStreamingServer() {
    if ( $this->streamingserver )
      return $this->streamingserver;

    if ( !$this->type )
      throw new \Exception('Unset player type!');

    $streamModel = $this->bootstrap->getModel('streamingservers');
    return $this->streamingserver = $streamModel->getServerByClientIP(
      $this->info['ipaddress'],
      $this->type
    );
  }

  public function getGlobalConfig( $info, $isembed = false ) {
    $this->info = $info;
    $cfg = $this->getConfig( $info, $isembed );
    $ret = array(
      'version'     => $this->bootstrap->config['version'],
      'containerid' => $this->getContainerID(),
      'thumbnail'   => $cfg['thumbnail'],
      'width'       => $this->getWidth( $isembed ),
      'height'      => $this->getHeight( $isembed ),
      'flashplayer' => $this->getFlashConfig( $cfg ),
    );

    if ( $this->needFlowPlayer( $info ) )
      $ret['flowplayer'] = $this->getFlowConfig( $cfg );

    return $ret;
  }

  public function getStructuredFlashData( $info, $isembed = false ) {
    $flashdata = $this->transformFlashData(
      $this->getFlashData(
        $this->getConfig( $info, $isembed )
      )
    );

    $flashdata['recommendatory'] = $flashdata['recommendatory']['string'];
    return $flashdata;
  }

  protected function transformFlashData( $data ) {
    $flashdata = array();
    foreach( $data as $key => $value ) {

      $key = explode('_', $key );
      if ( is_array( $value ) )
        $value = $this->transformFlashData( $value );

      if ( count( $key ) == 1 )
        $flashdata[ $key[0] ] = $value;
      elseif ( count( $key ) == 2 ) {

        if ( !isset( $flashdata[ $key[0] ] ) )
          $flashdata[ $key[0] ] = array();

        $flashdata[ $key[0] ][ $key[1] ] = $value;

      } else
        throw new \Exception('key with more then two underscores!');

    }

    return $flashdata;
  }

  protected function getFlowUrl( $cfg, $type, $version, $extraparams = array() ) {
    /*
    - Minden média URL vége a playlist.m3u8, ami egy m3u8-as playlist, ami felsorolja a media segmenteket. Élő és vod ugyan az.
    - SMIL fájl: a SMIL fájl a Wowza szerveren generálódik, több minőségi változatot fog össze és mondja a Wowzának, hogy a playlistbe több minőséget is tegyen bele.

    Ennek megfelelően:

    1. VoD ABR set lejátszása: https://<server>/<wowza_app>/_definst_/smil:<smil fájl URL>/playlist.m3u8
    2. VoD single minőség lejátszása: https://<server>/<wowza_app>/_definst_/mp4:<mp4 URL>/playlist.m3u8
    3. Live ABR lejátszása: https://<server>/<wowza live app>/_definst_/smil:<live smil URL>/playlist.m3u8

    TODO
    Ja: a mobilok számára fogunk genrálni egy 123_mobile.smil fájlt is, azoknak azt kell kiajánlani.
    */

    $prefix = '';
    // ha ssl-en vagyunk kotelezo hogy ssl-en keresztul menjen
    if ( $this->bootstrap->ssl or $this->row['issecurestreamingforced'] )
      $prefix = 'sec';

    $params = $this->getAuthorizeSessionid( null );
    if ( $extraparams )
      $params .= '&' . http_build_query( $extraparams );

    $extension = 'smil';
    switch( $type ) {
      case 'vodabr':
        $base = $this->bootstrap->config['wowza'][ $prefix . 'httpurl'];
        break;
      case 'vod':
        $base = $this->bootstrap->config['wowza'][ $prefix . 'httpurl'];
        $extension = \Springboard\Filesystem::getExtension( $version['filename'] );
        break;
      case 'liveabr':
        $base = $this->bootstrap->config['wowza'][ $prefix . 'livesmilurl'];
        break;
    }

    // TODO forced streaming server handling
    $base = sprintf( $base, $this->streamingserver['server'] );
    $postfix = '';
    if ( $version['iscontent'] )
      $postfix = '_content';

    if ( isset( $version['recordingid'] ) ) {
      $path = \Springboard\Filesystem::getTreeDir( $version['recordingid'] ) . '/';
      $filename = $version['recordingid'] . $postfix . '.smil';
    } else if ( isset( $version['livefeedid'] ) ) {
      $path = '';
      $filename = $version['livefeedid'] . $postfix . '.smil';
    } else {
      $path = '';
      $filename = $version['streamcode'];
    }

    return strtr(
      '<base><extension>:<path><filename>/playlist.m3u8<params>',
      array(
        '<base>'      => $base,
        '<extension>' => $extension,
        '<path>'      => $path,
        '<filename>'  => $filename,
        '<params>'    => $params,
      )
    );
  }

  abstract public function getWidth( $isembed );
  abstract public function getHeight( $isembed );
  abstract protected function getAuthorizeSessionid( $cfg );

  abstract protected function needFlowPlayer( $info );
  abstract protected function getFlashConfig( $cfg );
  abstract protected function getFlowStreams( $cfg );

  protected function getFlowConfig( $cfg ) {
    if ( !$cfg['hds'] )
      throw new \Exception("Flowplayer only supported with HDS streams");

    $ret = array(
      // the video is loaded on demand, i.e. when the user starts playback with a click
      'splash' => false,
      // By default the embed feature loads the embed script and Flowplayer assets from our CDN. This can be customized in the embed configuration object if you prefer to host the files yourself.
      'embed' => false,
      // playlistbe egymas utan tovabb
      'advance' => true,
      // minden video wide-screen
      'ratio' => 9/16,
      'live'  => $this->type === 'live',
      'playlist' => array(),
      'vsq' => array(
        'debug'    => $this->bootstrap->config['vsqplayer_debug'],
        'type'     => $this->type,
        'duration' => -1,
        // a master video hanyadik a sorban amit jatszani kell
        'masterIndex' => 0,
        'autoplay' => false,
        // a minosegi valtozatok labeljei, kulon a master es contentnek
        'labels' => array(),
        'secondarySources' => array(),
        'contentOnRight' => (bool) $this->row['slideonright'],
      ),
    );

    $newclip = array(
      // Set a title for this clip. Displayed in a top bar when hovering over the player.
      'title'   => isset( $cfg['title'] )? $cfg['title']: $this->row['title'],
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
    );

    if ( isset( $cfg['duration'] ) )
      $ret['vsq']['duration'] = $cfg['duration'];

    $streams = $this->getFlowStreams( $cfg );
    if ( $streams['intro'] ) {
      $ret['vsq']['masterIndex']++;
      $clip = $newclip;
      $clip['sources'][] = array(
        'type' => $streams['intro']['type'],
        'src'  => $streams['intro']['url'],
        'vsq-labels' => $streams['intro']['labels'],
      );
      $ret['playlist'][] = $clip;
    }

    // master
    $clip = $newclip;
    $clip['sources'][] = array(
      'type' => $streams['master']['type'],
      'src'  => $streams['master']['url'],
      'vsq-labels' => $streams['master']['labels'],
    );
    // TODO ezt akarjuk lecserelni majd dinamikusan a source labelekkel
    // ugyhogy majd torolni kell
    $ret['vsq']['labels'] = $streams['master']['labels'];
    $ret['playlist'][] = $clip;

    if ( $streams['outro'] ) {
      $clip = $newclip;
      $clip['sources'][] = array(
        'type' => $streams['outro']['type'],
        'src'  => $streams['outro']['url'],
        'vsq-labels' => $streams['outro']['labels'],
      );
      $ret['playlist'][] = $clip;
    }

    if ( empty( $streams['content'] ) )
      return $ret;

    $ret['vsq']['secondarySources'][] = array(
      'type' => $streams['content']['type'],
      'src'  => $streams['content']['url'],
      'vsq-labels' => $streams['content']['labels'],
    );

    return $ret;
  }

  abstract protected function getConfig( $info, $isembed );
}
