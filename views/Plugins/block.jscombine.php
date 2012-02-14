<?php

function smarty_block_jscombine( $params, $content, &$smarty ) {
  
  $bootstrap = $smarty->get_template_vars('bootstrap');
  $scheme    = SSL? 'https://': 'http://';
  $staticuri = $scheme . $bootstrap->config['staticuri'];
  
  if ( !$bootstrap->config['combine']['js'] )
    return $content;
  
  preg_match_all('/<script[^>]+src="([^"]+)"[^>]*><\/script>/mi', $content, $results );
  
  if ( isset( $results[1] ) && count( $results[1] ) ) {
    
    $out = array();
    
    foreach ( $results[1] as $key => $href ) {
      
      $content = str_replace( $results[0][ $key ], '', $content );
      if ( strpos( $href, $staticuri ) !== false )
        $out[] = rawurlencode( str_replace( $staticuri, '', $href ) );
      else
        $out[] = rawurlencode( $href );
      
    }
    
    $content .=
      '<script type="text/javascript" src="' .
        $scheme . $bootstrap->config['baseuri'] . \Springboard\Language::get() .
        '/combine/js?url[]=' . implode( '&url[]=', $out ) .
      '"></script>'
    ;
    
  }
  
  return '  ' . trim( $content ) . "\n";
  
}
