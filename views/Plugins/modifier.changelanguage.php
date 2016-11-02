<?php
function smarty_modifier_changelanguage( $url, $language, $baseuri = null ) {
  if ( $baseuri === null )
    $baseuri = \Bootstrap::getInstance()->baseuri;

  if ( strlen( $url ) < strlen( $baseuri ) )
    $newurl = $url . "/$language/";
  elseif ( strlen( $url ) == strlen( $baseuri ) )
    $newurl = $url . "$language/";
  else
    $newurl = preg_replace(
      '#^' . preg_quote( $baseuri, '#' ) . '([a-z]{2})/#',
      $baseuri . $language . '/',
      $url
    );

  $pos = strpos( $newurl, '?');
  if ( $pos === false )
    return $newurl;

  parse_str( substr( $newurl, $pos + 1 ), $params );
  if ( isset( $params['forward'] ) and $params['forward'] )
    $params['forward'] = smarty_modifier_changelanguage(
      $params['forward'], $language, $baseuri
    );
  else
    return $newurl;

  $newurl = substr( $newurl, 0, $pos );
  $newurl .= '?' . http_build_query( $params );

  return $newurl;
}
