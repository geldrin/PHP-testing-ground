<?php

function smarty_modifier_jsonescape( $data, $encodenonscalar = false, $isattribute = false ) {

  if ( $encodenonscalar ) {

    foreach( $data as $key => $value ) {

      // nem rekurzivan encodoljuk a nonscalar dolgokat
      if ( is_array( $value ) )
        $data[ $key ] = smarty_modifier_jsonescape( $value, false );

    }

  }

  $ret = json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );
  if ( $isattribute )
    $ret = trim( $ret, '"' );

  return $ret;
}
