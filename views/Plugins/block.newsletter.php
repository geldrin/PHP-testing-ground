<?php

function smarty_block_newsletter( $params, $content, &$smarty ) {
  
  if ( !$content )
    return;
  
  $astyle   = 'color: #1e97d4;';
  $h1style  = 'font-size: 20px; margin: 0 0 10px 0; padding: 0; font-weight: bold;';
  $h2style  = 'font-size: 17px; margin: 0 0 10px 0; padding: 0; font-weight: bold;';
  $h3style  = 'font-size: 15px; margin: 0 0 10px 0; padding: 0; font-weight: bold;';
  $ulstyle  = 'list-style-type:disc;';
  $listyle  = 'font-size: 13px;';
  $pstyle   = 'font-size: 13px;';
  $imgstyle = 'border: 0;';

  preg_match_all( '/(<a[^>]+>).+<\/a>/msUi', $content, $results );
  foreach ( $results[1] as $result ) {

    $original = $result;

    if ( strpos( $result, 'style="' ) !== false )
      $result   = str_replace( 'style="', 'style="' . $astyle . ';', $result );
    else
      $result = str_replace( '<a ', '<a style="' . $astyle . '" ', $result );

    $content = str_replace( $original, $result, $content );

  }
  
  preg_match_all( '/(<h1[^>]*>.+<\/h1>)/msUui', $content, $results );
  
  foreach ( $results[0] as $result ) {

    $original = $result;

    if ( strpos( $result, 'style="' ) !== false )
      $result   = str_replace( 'style="', 'style="' . $h1style . ';', $result );
    else
      $result = str_replace( '<h1', '<h1 style="' . $h1style . '" ', $result );

    $content = str_replace( $original, $result, $content );

  }
  
  preg_match_all( '/(<h2[^>]*>.+<\/h2>)/msUui', $content, $results );
  
  foreach ( $results[0] as $result ) {

    $original = $result;

    if ( strpos( $result, 'style="' ) !== false )
      $result   = str_replace( 'style="', 'style="' . $h2style . ';', $result );
    else
      $result = str_replace( '<h2', '<h2 style="' . $h2style . '" ', $result );

    $content = str_replace( $original, $result, $content );

  }
  
  preg_match_all( '/(<h3[^>]*>.+<\/h3>)/msUi', $content, $results );
  foreach ( $results[0] as $result ) {

    $original = $result;

    if ( strpos( $result, 'style="' ) !== false )
      $result   = str_replace( 'style="', 'style="' . $h3style . ';', $result );
    else
      $result = str_replace( '<h3', '<h3 style="' . $h3style . '" ', $result );

    $content = str_replace( $original, $result, $content );

  }
  
  
  preg_match_all( '/(<ul[^>]*>).+<\/ul>/msUi', $content, $results );
  foreach ( $results[1] as $result ) {

    $original = $result;

    if ( strpos( $result, 'style="' ) !== false )
      $result   = str_replace( 'style="', 'style="' . $ulstyle . ';', $result );
    else
      $result = str_replace( '<ul', '<ul style="' . $ulstyle . '" ', $result );

    $content = str_replace( $original, $result, $content );

  }
  
  
  preg_match_all( '/(<li[^>]*>).+<\/li>/msUi', $content, $results );
  foreach ( $results[1] as $result ) {

    $original = $result;

    if ( strpos( $result, 'style="' ) !== false )
      $result   = str_replace( 'style="', 'style="' . $listyle . ';', $result );
    else
      $result = str_replace( '<li', '<li style="' . $listyle . '" ', $result );

    $content = str_replace( $original, $result, $content );

  }
  
  preg_match_all( '/(<p[^>]*>).+<\/p>/msUi', $content, $results );
  foreach ( $results[1] as $result ) {

    $original = $result;

    if ( strpos( $result, 'style="' ) !== false )
      $result   = str_replace( 'style="', 'style="' . $pstyle . ';', $result );
    else
      $result = str_replace( '<p', '<p style="' . $pstyle . '" ', $result );

    $content = str_replace( $original, $result, $content );

  }
  
  preg_match_all( '/(<img[^>]+\/>)/msUi', $content, $results );
  foreach ( $results[0] as $result ) {

    $original = $result;

    if ( strpos( $result, 'style="' ) !== false )
      $result   = str_replace( 'style="', 'style="' . $imgstyle . ';', $result );
    else
      $result = str_replace( '<img ', '<img style="' . $imgstyle . '" ', $result );

    $content = str_replace( $original, $result, $content );

  }

  return $content;

}

?>