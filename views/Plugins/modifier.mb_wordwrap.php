<?php

function smarty_modifier_mb_wordwrap( $string, $length, $separator = "\xc2\xad" ) {
  
  if ( mb_strlen( $string ) <= $length )
    return $string;   
  
  $parts = mb_split( '[\s\n\t]+', $string );
  $out   = '';
  
  foreach ( $parts as $key => $part ) {
    
    $i = 0;
    
    while ( $i < mb_strlen( $part ) ) {
      
      $current = mb_substr( $part, $i, $length );
      
      $i = $i + mb_strlen( $current );
      if ( $i < mb_strlen( $part ) )
        $out = $out . $current . $separator;
      else
        $out = $out . $current;
      
    }
    
    if ( $key < count( $parts ) )
      $out .= ' ';
    
  }
  
  return $out;
  
}
