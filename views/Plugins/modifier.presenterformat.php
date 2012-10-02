<?php
include_once("modifier.nameformat.php");

function smarty_modifier_presenterformat( $presenters ) {
  
  if ( empty( $presenters ) )
    return '';
  
  $names = array();
  foreach( $presenters as $presenter )
    $names[] = smarty_modifier_nameformat( $presenter, true );
  
  return implode(', ', $names );
  
}
