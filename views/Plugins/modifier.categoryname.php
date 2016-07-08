<?php

function smarty_modifier_categoryname( $data ) {
  $ret = '';
  if ( isset( $data['name'] ) )
    $ret = $data['name'];

  $ret = htmlspecialchars( $ret, ENT_QUOTES, 'UTF-8');
  $ret = str_replace('_', '&shy;', $ret);
  return $ret;
}
