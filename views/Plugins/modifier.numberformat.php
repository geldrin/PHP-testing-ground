<?php

function smarty_modifier_numberformat( $number, $decimals = false, $language = null ) {
  
  if ( $decimals === false )
    $decimals = 0;
  
  if ( !$language )
    $language = \Springboard\Language::get();
  
  if ( $language == 'hu' )
    return number_format( $number, $decimals, '.', ' ' );
  else
    return number_format( $number, $decimals );
  
}
