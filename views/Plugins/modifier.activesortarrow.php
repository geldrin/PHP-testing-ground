<?php

function smarty_modifier_activesortarrow( $string, $searchfor, $currentorder = null ) {
  
  $staticuri = \Bootstrap::getInstance()->getSmarty()->get_template_vars('STATIC_URI');
  if ( strpos( $currentorder, $searchfor ) === 0 ) {
    
    if ( strpos( $currentorder, '_desc') !== false ) {
      
      $string = str_replace('UP', '<img src="' . $staticuri . 'images/sortarrow_down.png" />', $string );
      $string = str_replace('DN', '<img src="' . $staticuri . 'images/sortarrow_down.png" />', $string );
      
    } else {
      
      $string = str_replace('UP', '<img src="' . $staticuri . 'images/sortarrow_up.png" />', $string );
      $string = str_replace('DN', '<img src="' . $staticuri . 'images/sortarrow_up.png" />', $string );
      
    }
    
  } else {
    
    $string = str_replace('UP', '<img src="' . $staticuri . 'images/sortarrow_up_gray.png" />', $string );
    $string = str_replace('DN', '<img src="' . $staticuri . 'images/sortarrow_down_gray.png" />', $string );
    
  }
  
  return $string;
  
}
