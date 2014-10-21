<?php

include_once('modifier.mb_wordwrap.php');

function smarty_modifier_autolink( $string ) {

  // url-le alakitas

  // URLs not preceded by " or /
  // thus avoiding:
  //  - existing href="url" URLs
  //  - nonstandard URLs:
  //      http://web.archive.org/web/*/http://www.fokk.hu

  $string = preg_replace(
    '/(?<!["\/])(http(s?)\:\/\/[\?\=\/\-\.A-Za-z0-9_\&\#\%\~\@\,\;\:\*]+)/mi',
    '*HREF*$1*/HREF*',
    $string
  );

  $string = preg_replace_callback(
    '/\*HREF\*(.+)\*\/HREF\*/Us',
    'replaceToAnchorTag',
    $string
  );
  

  // wordwrap
  $string = preg_replace_callback(
    '/([^\s]{80,})/mi',
    'wordWrapExceptURL', 
    $string
  );

  return $string;

}

function replaceToAnchorTag( $matches ) {

  return 
    '<a target="_blank" href="' . 
      str_replace("\n","", $matches[1] ) . '">' . 
      $matches[1] . 
    '</a>'
  ;
  
}

function wordWrapExceptURL( $matches ) {

  if ( 
       preg_match(
         "/^(.*(<[^>]+)?(src|href|data|value)=\"[^\"]+\"[^>]*>)(.+)(<\/a>.*)$/", 
          $matches[1], $submatches 
       ) 
     ) {

    // URL with partial HTML tag and no whitespace
    return 
      $submatches[1] . 
      smarty_modifier_mb_wordwrap( $submatches[4], 80, "\n" ) . 
      $submatches[5]
    ;

  }
  else {

    // rest of partial HTML chunks should be left untouched
    return
      preg_match( "/((<[^>]+)?(src|href|data|value)=\"[^\"]+\")/", $matches[1] ) 
        ? $matches[1] : smarty_modifier_mb_wordwrap( $matches[1], 80, "\n" )
    ;
    
  }
  
}
