<?php

function smarty_modifier_timeformat( $seconds, $playertimestamp = false ) {
  
  $ret = '';
  $l   = \Bootstrap::getInstance()->getLocalization();
  
  if ( ( $hours = floor( $seconds / 3600 ) ) > 0 ) { // is it more than an hour?
    
    $ret .= $hours . $l('', 'time_hour_short'). ' ';
    $seconds = $seconds - ( $hours * 3600 ); // deduct the number of hours
    
  }
  
  if ( ( $minutes = floor( $seconds / 60 ) ) > 0 ) {
    
    $ret .= $minutes . $l('', 'time_minute_short'). ' ';
    $seconds = $seconds - ( $minutes * 60 );
    
  }
  
  if ( $seconds > 0 or ( !strlen( $ret ) and $seconds == 0 ) )
    $ret .= $seconds . $l('', 'time_second_short'). ' ';
  
  if ( $playertimestamp )
    return $hours .'h' . $minutes . 'm' . $seconds . 's';
  
  return trim( $ret );
  
}
