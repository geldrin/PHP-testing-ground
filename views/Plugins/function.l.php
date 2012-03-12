<?php

function smarty_function_l( $params, $smarty ) {
  
  $bootstrap = $smarty->get_template_vars('bootstrap');
  $l         = $bootstrap->getLocalization();
  
  if ( !isset( $params['module'] ) )
    $params['module'] = '';
  
  if ( !isset( $params['language'] ) )
    $language = $smarty->get_template_vars('language');
  else
    $language = $params['language'];
  
  // {l lov=userstatus key=disabled} -> $lov['userstatus']['disabled']
  if ( isset( $params['lov'] ) ) {
    
    $lov = $l->getLov( $params['lov'], $language );
    if ( isset( $params['key'] ) )
      $ret = $lov[ $params['key'] ];
    else
      $ret = $lov;
    
  } else
    $ret = $l( $params['module'], $params['key'], $language );
  
  if ( isset( $params['assign'] ) ) {
    
    $smarty->assign( $params['assign'], $ret );
    return '';
    
  } else
    return $ret;
  
}
