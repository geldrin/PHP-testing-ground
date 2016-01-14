<?php

function smarty_function_perpageurlparam( $params, $smarty ) {
  $getparams = array();
  $pos = strpos( $_SERVER['REQUEST_URI'], '?' );

  if ( $pos === false )
    $ret = $_SERVER['REQUEST_URI'] . '?';
  else {
    $ret = substr( $_SERVER['REQUEST_URI'], 0, $pos + 1 );
    parse_str( substr( $_SERVER['REQUEST_URI'], $pos + 1 ), $getparams );
  }

  $getparams['perpage'] = $params['perpage'];
  $ret .= http_build_query( $getparams );
  return htmlspecialchars( $ret, ENT_QUOTES, 'UTF-8');
}
