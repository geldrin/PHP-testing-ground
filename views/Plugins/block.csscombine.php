<?php

function smarty_block_csscombine( $params, $content, $smarty ) {
  
  $bootstrap = $smarty->get_template_vars('bootstrap');
  $language  = $smarty->get_template_vars('language');
  $baseuri   = $smarty->get_template_vars('BASE_URI');
  $staticuri = $smarty->get_template_vars('STATIC_URI');
  
  if ( !$bootstrap->config['combine']['css'] )
    return $content;
  
  preg_match_all('/<link[^>]+href="(' . preg_quote( $staticuri, '/' ) . '[^>"]+\.css)"+[^>]*>/mi', $content, $results );
  
  if ( isset( $results[1] ) && count( $results[1] ) ) {
    
    $out = array();
    
    foreach ( $results[1] as $key => $href ) {
      $content = str_replace( $results[0][ $key ], '', $content );
      $out[] = rawurlencode( str_replace( $staticuri, '', $href ) );
    }
    
    $content .=
      '<link rel="StyleSheet" type="text/css" href="' .
        $baseuri . $language .
        '/combine/css?url[]=' . implode( '&url[]=', $out ) .
      '" />'
    ;
    
  }
  
  return '  ' . trim( $content ) . "\n";
  
}
