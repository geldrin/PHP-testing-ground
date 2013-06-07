<?php

function smarty_modifier_uri( $organization, $type, $scheme = null ) {
  
  if ( $scheme === null )
    $scheme = SSL? 'https://': 'http://';
  
  $orgkey = 'domain';
  if ( $type == 'static' )
    $orgkey = 'staticdomain';
  
  return $scheme . $prefix . $organization[ $orgkey ] . '/';
  
}
