<?php

function smarty_modifier_uri( $organization, $type, $scheme = null ) {

  if ( $scheme === null )
    $scheme = SSL? 'https://': 'http://';

  $orgkey = 'domain';
  if ( $type == 'static' or $type == 'emaillogo' )
    $orgkey = 'staticdomain';

  $url = $scheme . $organization[ $orgkey ] . '/';
  if ( $type == 'emaillogo' ) {

    $lang  = \Springboard\Language::get();
    $index = 'emaillogofilename';
    if ( $lang == 'en' )
      $index .= 'en';

    if ( @$organization[ $index ] )
      $url .= sprintf(
        'files/organizations/%s/%s',
        $organization['id'],
        $organization[ $index ]
      );
    else
      $url .= 'images/email_header.png';

  }

  return $url;

}
