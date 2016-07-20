<?php

function smarty_modifier_categoryname( $data, $replaceby = '&shy;' ) {
  $ret = '';
  if ( isset( $data['name'] ) )
    $ret = $data['name'];

  $ret = htmlspecialchars( $ret, ENT_QUOTES, 'UTF-8');

  $ret = str_replace('_', $replaceby, $ret);
  return $ret;
}