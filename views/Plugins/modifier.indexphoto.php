<?php

function smarty_modifier_indexphoto( $data, $type = '' ) {

  if ( substr( @$data['indexphotofilename'], 0, 39 ) == 'images/videothumb_audio_placeholder.png' ) {
    
    unset( $data['indexphotofilename'] );
    $data['mediatype'] = 'audio';
    
  }
  
  $bootstrap = \Bootstrap::getInstance();
  $staticuri = $bootstrap->getSmarty()->get_template_vars('STATIC_URI');
  
  switch( $type ) {
    
    default:
      
      $replace = $bootstrap->config['videothumbnailresolutions']['4:3'];
      if ( @$data['mediatype'] == 'audio' )
        $default = $staticuri . 'images/videothumb_audio_placeholder.png';
      else
        $default = $staticuri . 'images/videothumb_placeholder.png';
      break;
    
    case 'wide':
      
      $replace = $bootstrap->config['videothumbnailresolutions']['wide'];
      if ( @$data['mediatype'] == 'audio' )
        $default = $staticuri . 'images/videothumb_wide_audio_placeholder.png';
      else
        $default = $staticuri . 'images/videothumb_wide_placeholder.png';
      break;
    
    case 'player':
      
      $replace = $bootstrap->config['videothumbnailresolutions']['player'];
      if ( @$data['mediatype'] == 'audio' )
        $default = $staticuri . 'images/videothumb_player_audio_placeholder.png';
      else
        $default = $staticuri . 'images/videothumb_player_placeholder.png';
      break;
    
  }
  
  if ( strlen( @$data['indexphotofilename'] ) )
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
