<?php

function smarty_block_box( $params, $content, &$smarty ) {

  $out = '
  <div class="box '. @$params['class'] .'">
    <div class="box_topleft"></div>
    <div class="box_top"></div>
    <div class="box_topright"></div>
    <div class="box_content">

'.
      ( strlen( @$params['h1'] ) ? '<h1>' . $params['h1'] . '</h1>': '' ) .
      ( strlen( @$params['h2'] ) ? '<h2>' . $params['h2'] . '</h2>': '' ) . 
      $content .'

    </div>
    <div class="box_bottomleft"></div>
    <div class="box_bottom"></div>
    <div class="box_bottomright"></div>
  </div>
';

  return $out;
 
}

?>