<?php

function smarty_modifier_mb_truncate( $string, $length, $add = '...' ) {
  
  if ( mb_strlen( $string ) <= $length )
    return $string;
  
  return mb_strcut( $string, 0, $length, 'UTF-8' ) . $add;

}
