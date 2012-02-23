<?php

function smarty_modifier_recordingsurl( $recording ) {

  if ( empty( $recording ) )
    return '';
  
  $url =
    'recordings/details/' . $recording['id'] . ',' .
    \Springboard\Filesystem::filenameize( $recording['title'] )
  ;
  
  return $url;
  
}
