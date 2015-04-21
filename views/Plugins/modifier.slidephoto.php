<?php

function smarty_modifier_slidephoto( $data, $staticuri, $type = null ) {
  $bs = \Bootstrap::getInstance();
  switch( $type ) {
    case 'wide':
      $replace = $bs->config['videothumbnailresolutions']['wide'];
      break;
    case 'player':
      $replace = $bs->config['videothumbnailresolutions']['player'];
      break;
    default:
      $replace = $bs->config['videothumbnailresolutions']['4:3'];
      break;
  }
  $pos = strpos( $replace, 'x');
  $dir = substr( $replace, 0, $pos ); // az x elotti resz erdekel minket

  // https://dam.codebasehq.com/projects/teleconnect/tickets/1335
  return sprintf("%sfiles/recordings/%s/ocr/%s/%s_%s.jpg",
    $staticuri,
    \Springboard\Filesystem::getTreeDir( $data['recordingid'] ),
    $dir,
    $data['recordingid'],
    $data['id']
  );
}
