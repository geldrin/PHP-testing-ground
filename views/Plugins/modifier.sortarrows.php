<?php

function smarty_modifier_sortarrows( $string, $isactive = null, $order = null, $currentorder = null ) {
  
  if ( $isactive === null and $order and $currentorder )
    $isactive = $order == $currentorder;

  $class = $isactive ? ' sortarrow-active' : '';

  $string = str_replace('UP', '<div class="sortarrow sortarrow-up' . $class . '"></div>', $string );
  $string = str_replace('DN', '<div class="sortarrow sortarrow-down' . $class . '"></div>', $string );

  return $string;

}
