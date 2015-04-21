<?php

function smarty_modifier_slidephoto( $data, $staticuri, $type = null ) {
  // https://dam.codebasehq.com/projects/teleconnect/tickets/1335
  return sprintf("%sfiles/recordings/%s/ocr/220/%s_%s.jpg",
    $staticuri,
    \Springboard\Filesystem::getTreeDir( $data['recordingid'] ),
    $data['recordingid'],
    $data['id']
  );
}
