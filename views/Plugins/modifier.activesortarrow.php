<?php

function smarty_modifier_activesortarrow( $string, $searchfor, $currentorder = null ) {
  
  if ( strpos( $currentorder, $searchfor ) === 0 ) {
    
    $staticuri = \Bootstrap::getInstance()->getSmarty()->get_template_vars('STATIC_URI');
    $string = str_replace('UP', '<img src="' . $staticuri . 'images/sortarrow_up.png" />', $string );
    $string = str_replace('DN', '<img src="' . $staticuri . 'images/sortarrow_down.png" />', $string );
    
  } else {
    
    $string = str_replace('UP', '<span class="spacer"></span>', $string );
    $string = str_replace('DN', '<span class="spacer"></span>', $string );
    
  }
  
  return $string;
  
}
