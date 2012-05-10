<?php

function smarty_modifier_activesortlink( $string, $defaultorder, $currentorder, $searchfor = null ) {
  
  if ( !$searchfor )
    $searchfor = $defaultorder;
  
  if ( strpos( $currentorder, $searchfor ) === 0 )
    $string = str_replace('%s', $currentorder, $string );
  else
    $string = str_replace('%s', $defaultorder, $string );
  
  return $string;
  
}
