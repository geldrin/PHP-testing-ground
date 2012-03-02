<?php

function smarty_modifier_sortarrows( $string, $isactive = null, $order = null, $currentorder = null ) {
  
  if ( $isactive === null and $order and $currentorder )
    $isactive = $order == $currentorder;
  
  $postfix = $isactive ? '_red' : '';

  $staticuri = ( SSL? 'https://': 'http://' ) . \Bootstrap::getInstance()->config['staticuri'];
  $string = str_replace('UP', '<img src="' . $staticuri . 'images/sortarrow_up' . $postfix . '.png" />', $string );
  $string = str_replace('DN', '<img src="' . $staticuri . 'images/sortarrow_down' . $postfix . '.png" />', $string );

  return $string;

}
