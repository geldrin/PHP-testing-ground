<?php

function smarty_modifier_baseuri( $organization, $scheme = null ) {
  
  if ( !$scheme )
    $scheme = SSL? 'https://': 'http://';
  
  return $scheme . $organization['domain'] . '/';
  
}
