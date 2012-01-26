#!/usr/bin/php
<?php
if ( $argc == 3 ) {
  
  $subject = '[teleconnect] Suhosin - ';
  $pos     = strpos( $argv[2], '(attacker' );
  
  if ( $pos !== false )
    $subject .= substr( $argv[2], 0, $pos );
  else
    $subject .= $argv[2];
  
  $body    =
    "Suhosin alert of class: " . $argv[1] . "\n" .
    "Message was: " . $argv[2] . "\n\n" .
    "Received at: " . date('Y-m-d H:i:s')
  ;
  
} else {
  
  $subject = '[teleconnect] Suhosin script called with less than 3 arguments!';
  $body    = "Arguments received were:\n" . var_export( $argv, true );
  
}

mail('dev@dotsamazing.com', $subject, $body );
