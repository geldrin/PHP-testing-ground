<?php
include_once('modifier.numberformat.php');

function smarty_modifier_sizeformat( $bytes, $precision = 2, $basepow = 0 ) {

  if ( $bytes <= 0 )
    return '';

  $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
  $pow   = floor( log( $bytes, 1024 ) ) + $basepow;
  $pow   = min( $pow, count( $units ) - 1 );

  $bytes /= pow( 1024, $pow - $basepow );

  return smarty_modifier_numberformat( $bytes, $precision ) . ' ' . $units[ $pow ];

}
