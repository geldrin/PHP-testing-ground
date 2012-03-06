<?php

function smarty_modifier_jsonescape( $data, $encodescalar = false ) {
  
  if ( $encodescalar ) {
    
    foreach( $data as $key => $value ) {
      
      if ( is_array( $value ) )
        $data[ $key ] = smarty_modifier_jsonescape( $value, $encodescalar );
      
    }
    
  }
  
  return json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );
}
