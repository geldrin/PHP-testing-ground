<?php
function smarty_function_l( $params, $smarty ) {
  $bootstrap = $smarty->get_template_vars('bootstrap');
  $l         = $bootstrap->getLocale();
  
  if ( !isset( $params['module'] ) )
    $params['module'] = '';
  
  return $l( $params['module'], $params['key'] );
}
