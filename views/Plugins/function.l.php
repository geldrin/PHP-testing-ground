<?php
function smarty_function_l( $params, $smarty ) {
  $bootstrap = $smarty->get_template_vars('bootstrap');
  $l         = $bootstrap->getLocalization();
  
  if ( !isset( $params['module'] ) )
    $params['module'] = '';
  
  //TODO handle params[escape], params[sprintf] params[assign]
  return $l( $params['module'], $params['key'] );
}
