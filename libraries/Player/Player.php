<?php
namespace Player;

abstract class Player {
  public $bootstrap;
  protected $row = array();
  protected $info = array();
  protected $model;

  public function __construct( $bootstrap, $model ) {
    $this->bootstrap = $bootstrap;
    $this->model = $model;
    $this->row = $model->row;
  }

  public function setInfo( $info ) {
    $this->info = $info;
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

  protected function getFlowUrl( $info, $type, $version, $extraparams = array() ) {
    /*
    - Minden média URL vége a playlist.m3u8, ami egy m3u8-as playlist, ami felsorolja a media segmenteket. Élő és vod ugyan az.
    - SMIL fájl: a SMIL fájl a Wowza szerveren generálódik, több minőségi változatot fog össze és mondja a Wowzának, hogy a playlistbe több minőséget is tegyen bele.

    Ennek megfelelően:

    1. VoD ABR set lejátszása: https://<server>/<wowza_app>/_definst_/smil:<smil fájl URL>/playlist.m3u8
    2. VoD single minőség lejátszása: https://<server>/<wowza_app>/_definst_/mp4:<mp4 URL>/playlist.m3u8
    3. Live ABR lejátszása: https://<server>/<wowza live app>/_definst_/smil:<live smil URL>/playlist.m3u8

    Ja: a mobilok számára fogunk genrálni egy 123_mobile.smil fájlt is, azoknak azt kell kiajánlani.
    */

    $prefix = '';
    // TODO ha ssl-en vagyunk kotelezo hogy ssl-en keresztul menjen ez?
    if ( $this->bootstrap->ssl or $this->row['issecurestreamingforced'] )
      $prefix = 'sec';

    $params = $this->getAuthorizeSessionid( $info );
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

    if ( isset( $version['recordingid'] ) ) {
      $path = \Springboard\Filesystem::getTreeDir( $version['recordingid'] ) . '/';
      $filename = $version['filename'];
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

  abstract protected function needFlowPlayer( $info );
  abstract protected function getFlashConfig( $cfg );
  abstract protected function getFlowConfig( $cfg );
  abstract protected function getConfig( $info, $isembed );
}
