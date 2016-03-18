<?php

function smarty_modifier_sortarrows( $string, $isactive = null, $order = null, $currentorder = null ) {

/*
egyelore nem hasznaljuk
  if ( $isactive === null and $order and $currentorder )
    $isactive = $order == $currentorder;
*/

  $string = str_replace('UP', '<div class="sortarrow sortarrow-up"></div>', $string );
  $string = str_replace('DN', '<div class="sortarrow sortarrow-down"></div>', $string );

  return $string;

}
