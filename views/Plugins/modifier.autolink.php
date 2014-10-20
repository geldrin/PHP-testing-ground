<?php

include_once('modifier.mb_wordwrap.php');

function smarty_modifier_autolink( $string ) {

  // url-le alakitas

  // URLs not preceded by " or /
  // thus avoiding:
  //  - existing href="url" URLs
  //  - nonstandard URLs:
  //      http://web.archive.org/web/*/http://www.fokk.hu

  $string =
    preg_replace(
      '/(?<!["\/])(http(s?)\:\/\/[\?\=\/\-\.A-Za-z0-9_\&\#\%\~\@\,\;\:\*]+)/mi',
      '*HREF*$1*/HREF*',
      $string
    );

  $string =
    preg_replace(
      '/\*\n?HREF\*(.+)\*\n?\/\n?\n?HREF\*/Use',
      '"<a target=\"_blank\" href=\"" . str_replace("\n","","$1") . "\">$1</a>"', 
      $string
    );

  // wordwrap
  
  $string =
    preg_replace(
      '/([^\s]{80,})/mie',
      'exceptURL( "$1" )', 
      $string
    );

  return $string;

}

function exceptURL( $string ) {

  if ( 
       preg_match(
         "/^(.*(<[^>]+)?(src|href|data|value)=\"[^\"]+\"[^>]*>)(.+)(<\/a>.*)$/", $string, $matches 
       ) 
     ) {
    // URL with partial HTML tag and no whitespace
    return $matches[1] . smarty_modifier_mb_wordwrap( $matches[4], 80, "\n" ) . $matches[5];
  }
  else {

    // rest of partial HTML chunks should be left untouched
    return
      preg_match( "/((<[^>]+)?(src|href|data|value)=\"[^\"]+\")/", $string ) 
        ? $string : smarty_modifier_mb_wordwrap( $string, 80, "\n" )
    ;
    
  }
  
}
