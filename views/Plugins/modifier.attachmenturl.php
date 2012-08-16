<?php

function smarty_modifier_attachmenturl( $attachment, $recording, $staticuri ) {
  
  $url = "%sfiles/recordings/%s/attached_documents/%s.%s/%s";
  $url = sprintf( $url,
    $staticuri,
    \Springboard\Filesystem::getTreeDir( $recording['id'] ),
    $attachment['id'],
    rawurlencode( $attachment['masterextension'] ),
    rawurlencode( $attachment['masterfilename'] )
  );
  
  return $url;
  
}
