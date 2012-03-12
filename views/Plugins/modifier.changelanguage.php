<?php
include_once('modifier.uri.php');

function smarty_modifier_changelanguage( $url, $language ) {
  
  $organization = \Bootstrap::getInstance()->getSmarty()->get_template_vars('organization');
  $baseuri      = smarty_modifier_uri( $organization, 'base' );
  
  if ( strlen( $url ) < strlen( $baseuri ) )
    return $url . "/$language/";
  elseif ( strlen( $url ) == strlen( $baseuri ) )
    return $url . "$language/";
  else
    return preg_replace( '/^' . preg_quote( $baseuri, '/' ) . '([a-z]{2})\//', $baseuri . $language . '/', $url );
  
}