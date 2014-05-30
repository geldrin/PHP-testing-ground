<?php
include_once('modifier.mb_wordwrap.php');

function smarty_modifier_commentlinkify( $item, $currenturl ) {

  $string     = htmlspecialchars( $item['text'], ENT_QUOTES, 'UTF-8', true );
  $currenturl = preg_replace(
    '/commentspage=\d+/', 'commentspage=PAGENUM', $currenturl
  );

  if ( strpos( $currenturl, 'PAGENUM' ) === false ) {
    $currenturl .=
      ( strpos( $currenturl, '?') === false? '?': '&' ) . 'commentspage=PAGENUM'
    ;
  }

  if ( $item['replyto'] ) {
    // a @nickname: replyokat linkeljuk be konkretan
    $string = preg_replace_callback(
      '/^@([^:]+):/',
      function( $match ) use( $item, $currenturl ) {
        $url  = str_replace( 'PAGENUM', $item['replypage'], $currenturl );
        return
          '<a class="replylink" href="' . $url . '&focus=' . $item['replyto'] . '" data-focusid="' . $item['replyto'] . '" data-pageid="' . $item['replypage'] . '">@' .
            $match[1] .
          '</a>:'
        ;
      },
      $string
    );
  }

  // url-le alakitas
  // olyan urlek, amiket nem eloz meg " vagy / jel.
  // ezzel kikerultuk:
  //  - a mar href="url" formaju urleket
  //  - a hibas, de mar elofordult urleket: 
  //      http://web.archive.org/web/*/http://www.fokk.hu

  $string =
    preg_replace(
      '/(?<!["\/])(http\:\/\/[\?\=\/\-\.A-Za-z0-9_\&\#\%\~\@\,\:\*]+)/mi',
      '*HREF*$1*/HREF*',
      $string
    );

  $string =
    preg_replace_callback(
      '/\*\n?HREF\*(.+)\*\n?\/\n?\n?HREF\*/Us',
      function( $matches ) {
        return
          '<a target="_blank" href="' . str_replace("\n", "", $matches[1] ) . '">' .
            $matches[1] .
          '</a>'
        ;
      },
      $string
    );

  $string =
    preg_replace_callback(
      '/([^\s]{60,})/mi',
      function( $matches ) {
        return
          preg_match( "/((<[^>]+)?href=\"[^\"]+\")/", $matches[1] )
            ? $matches[1]
            : smarty_modifier_mb_wordwrap( $matches[1], 60, "\n" )
        ;
      },
      $string
    );

  return nl2br( $string );

}




