<?php

function smarty_modifier_avatarphoto( $data ) {
  
  $avatar = 'images/avatar_placeholder.png';
  if ( isset( $data['avatarstatus'] ) and $data['avatarstatus'] == 'onstorage' ) {
    
    $avatar =
      'files/users/' .
      \Springboard\Filesystem::getTreeDir( $data['id'] ) . '/' .
      $data['id'] . '.jpg'
    ;
    
  }
  
  $bootstrap = \Bootstrap::getInstance();
  $staticuri = $bootstrap->getSmarty()->get_template_vars('STATIC_URI');
  
  return $staticuri . $avatar;
  
}
