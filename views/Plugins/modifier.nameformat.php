<?php

function smarty_modifier_nameformat( $name, $wantalias = false, $bracketizealias = false ) {

  if ( empty( $name ) )
    return '';
  
  $string = ( strlen( $name['nameprefix'] )? $name['nameprefix'] . ' ': '' );
  
  if ( $name['nameformat'] == 'straight' ) {
    
    if ( \Springboard\Language::get() == 'hu' )
      $string = $string . $name['namelast'] . ' ' . $name['namefirst'];
    else
      $string = $string . $name['namefirst'] . ' ' . $name['namelast'];
    
  } else
    $string = $string . $name['namefirst'] . ' ' . $name['namelast'];
  
  if ( $wantalias and @$name['namealias'] ) {
    
    if ( $bracketizealias )
      $string .= ' (';
    else
      $string .= ', ';
    
    $l       = \Bootstrap::getInstance()->getLocalization();
    $string .= $l('', 'namealias') . ': ' . $name['namealias'];
    
    if ( $bracketizealias )
      $string .= ')';
    
  }
  
  return $string;
  
}
