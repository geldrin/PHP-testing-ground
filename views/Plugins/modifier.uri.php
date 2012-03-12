<?php

function smarty_modifier_uri( $organization, $type, $scheme = null ) {
  
  if ( $scheme === null )
    $scheme = SSL? 'https://': 'http://';
  
  $prefix = '';
  
  if ( $type == 'static' )
    $prefix = 'static.';
  
  return $scheme . $prefix . $organization['domain'] . '/';
  
}
