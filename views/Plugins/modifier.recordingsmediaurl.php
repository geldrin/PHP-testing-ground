<?php

function smarty_modifier_recordingsurl( $recording, $type, $highquality ) {

  if ( empty( $recording ) )
    return '';
  
  $config    = \Bootstrap::getInstance()->config;
  $extension = 'mp4';
  $postfix   = '';
  $host      = '';
  $isaudio   = $recording['mastermediatype'] == 'audio';
  
  if ( $highquality and !$isaudio )
    $postfix = '_hq';
  
  if ( $isaudio )
    $extension = 'mp3';
  
  switch( $type ) {
    
    case 'mobilehttp':
      //http://stream.videotorium.hu:1935/vtorium/_definst_/mp4:671/2671/2671_2608_mobile.mp4/playlist.m3u8
      $host = $config['wowza']['httpurl'];
      
      if ( $isaudio )
        $sprintfterm = '%3$s:%s/%s.%s/playlist.m3u8';
      else
        $sprintfterm = '%3$s:%s/%s_mobile' . $postfix . '.%s/playlist.m3u8';
      
      break;
    
    case 'mobilertsp':
      //rtsp://stream.videotorium.hu:1935/vtorium/_definst_/mp4:671/2671/2671_2608_mobile.mp4
      $host = $config['wowza']['rtspurl'];
      
      if ( $isaudio )
        $sprintfterm = '%3$s:%s/%s.%s';
      else
        $sprintfterm = '%3$s:%s/%s_mobile' . $postfix . '.%s';
      
      break;
    
    case 'content':
      
      $sprintfterm = '%s/%s_content' . $postfix . '.%s';
      break;
    
    default:
      
      $sprintfterm = '%s/%s' . $postfix . '.%s';
      break;
    
  }
  
  return $host . sprintf( $sprintfterm,
    \Springboard\Filesystem::getTreeDir( $recording['id'] ),
    $recording['id'],
    $extension
  );
  
}
