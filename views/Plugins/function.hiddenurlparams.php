<?php

function smarty_function_hiddenurlparams( $params, $smarty ) {
  $pos = strpos( $_SERVER['REQUEST_URI'], '?' );
  if ( $pos === false )
    return '';

  $getparams = array();
  $ret = array();

  parse_str( substr( $_SERVER['REQUEST_URI'], $pos + 1 ), $getparams );
  foreach( $getparams as $key => $value ) {
    if ( $key === 'perpage' )
      continue;

    $ret[] = sprintf(
      '<input type="hidden" name="%s" value="%s"/>',
      htmlspecialchars( $key, ENT_QUOTES, 'UTF-8'),
      htmlspecialchars( $value, ENT_QUOTES, 'UTF-8')
    );
  }

  return implode("\n", $ret );
}
