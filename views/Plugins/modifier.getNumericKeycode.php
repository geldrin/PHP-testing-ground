<?php
function smarty_modifier_getNumericKeycode( $keycode ) {
  if ( !$keycode )
    return '';

  $pos = strpos( $keycode, '_' );
  if ( $pos === false )
    return $keycode;

  return substr( $keycode, 0, $pos );
}
