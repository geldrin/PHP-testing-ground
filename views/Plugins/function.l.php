<?php

function smarty_function_l( $params, $smarty ) {
  
  $bootstrap = $smarty->get_template_vars('bootstrap');
  $l         = $bootstrap->getLocalization();
  
  if ( !isset( $params['module'] ) )
    $params['module'] = '';
  
  $ret = $l( $params['module'], $params['key'] );
  
  if ( isset( $params['assign'] ) ) {
    
    $smarty->assign( $params['assign'], $ret );
    return '';
    
  } else
    return $ret;
  
}
