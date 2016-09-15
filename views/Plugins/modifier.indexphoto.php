<?php

function smarty_modifier_indexphoto( $data, $type = '', $staticuri = null ) {

  $bootstrap = \Bootstrap::getInstance();
  if ( !$staticuri )
    $staticuri = $bootstrap->getSmarty()->get_template_vars('STATIC_URI');

  switch( $type ) {

    default:

      $replace = $bootstrap->config['videothumbnailresolutions']['4:3'];
      if ( isset( $data['mediatype'] ) and $data['mediatype'] == 'audio' )
        $default = $staticuri . 'images/videothumb_audio_placeholder.png';
      else
        $default = $staticuri . 'images/videothumb_placeholder.png';
      break;

    case 'wide':

      $replace = $bootstrap->config['videothumbnailresolutions']['wide'];
      if ( isset( $data['mediatype'] ) and $data['mediatype'] == 'audio' )
        $default = $staticuri . 'images/videothumb_wide_audio_placeholder.png';
      else
        $default = $staticuri . 'images/videothumb_wide_placeholder.png';
      break;

    case 'player':

      $replace = $bootstrap->config['videothumbnailresolutions']['player'];
      if ( isset( $data['mediatype'] ) and $data['mediatype'] == 'audio' )
        $default = $staticuri . 'images/videothumb_player_audio_placeholder.png';
      else
        $default = $staticuri . 'images/videothumb_player_placeholder.png';
      break;

    case 'live':
      $replace = $bootstrap->config['videothumbnailresolutions']['live'];
      $default = $staticuri . 'images/live_player_placeholder.png';
      break;

    case 'livethumb':
      $replace = $bootstrap->config['videothumbnailresolutions']['player'];
      $default = $staticuri . 'images/live_player_placeholder.png';
      break;
  }

  if ( isset( $data['indexphotofilename'] ) and $data['indexphotofilename'] )
    return
      $staticuri . 'files/' .
      str_replace(
        $bootstrap->config['videothumbnailresolutions']['4:3'],
        $replace,
        $data['indexphotofilename']
      )
    ;
  else
    return $default;

}
