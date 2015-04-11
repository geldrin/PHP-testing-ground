<?php

function smarty_modifier_slidephoto( $data, $staticuri ) {

  return
    $staticuri . 'files/recordings/' .
    \Springboard\Filesystem::getTreeDir( $data['recordingid'] ) . '/slides/' .
    $data['id']
  ;

}
