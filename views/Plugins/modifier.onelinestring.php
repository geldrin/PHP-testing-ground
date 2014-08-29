<?php

function smarty_modifier_onelinestring( $str ) {
  
  // \s includes: \r \n ' ' horizontal tab, etc
  $str = preg_replace( '/\s+/', ' ', $str );
  return trim( $str );
  
}
