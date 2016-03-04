<?php

function smarty_modifier_avatarphoto( $data ) {

  $avatar = 'images/avatar_placeholder.png';
  if ( isset( $data['avatarstatus'] ) and $data['avatarstatus'] == 'onstorage' ) {
    
    if ( isset( $data['userid'] ) )
      $id = $data['userid'];
    else
      $id = $data['id'];
    
    $avatar =
      'files/users/' .
      \Springboard\Filesystem::getTreeDir( $id ) . '/avatar/' .
      $id . '.' . \Springboard\Filesystem::getExtension( $data['avatarfilename'], 'jpg' )
    ;
    
  }
  
  $bootstrap = \Bootstrap::getInstance();
  $staticuri = $bootstrap->getSmarty()->get_template_vars('STATIC_URI');
  
  return $staticuri . $avatar;
  
}
