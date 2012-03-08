<?php
function smarty_modifier_changelanguage( $url, $language ) {
  
  $baseuri = \Bootstrap::getInstance()->getSmarty()->get_template_vars('BASE_URI');
  
  if ( strlen( $url ) < strlen( $baseuri ) )
    return $url . "/$language/";
  elseif ( strlen( $url ) == strlen( $baseuri ) )
    return $url . "$language/";
  else
    return preg_replace( '/^' . preg_quote( $baseuri, '/' ) . '([a-z]{2})\//', $baseuri . $language . '/', $url );
  
}