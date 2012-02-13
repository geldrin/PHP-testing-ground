<?php

function smarty_modifier_title( $data, $prefix = 'title' ) {
  
  $originalkey = $prefix . 'original';
  $englishkey  = $prefix . 'english';
  
  if ( !isset( $data[ $originalkey ] ) )
    $title = '';
  else
    $title = $data[ $originalkey ];
  
  if (
       !strlen( $title ) or
       (
         \Springboard\Language::get() == 'en' and
         isset( $data[ $englishkey ] ) and strlen( $data[ $englishkey ] )
       )
     )
    $title = $data[ $englishkey ];
  
  return $title;
  
}
