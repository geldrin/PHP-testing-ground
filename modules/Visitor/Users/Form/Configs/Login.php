<?php

$config = Array(
   
  'action' => Array(
    'type'  => 'inputHidden',
    'value' => 'submitlogin'
  ),

  'email' => Array(
    'displayname' => 'E-Mail',
    'type'        => 'inputText',
    'validation'  => Array(
      Array( 'type' => 'required' )
    )
  ),

  'password' => Array(
    'displayname' => 'Jelszó',
    'type'        => 'inputPassword',
    'validation'  => Array(
      Array( 'type' => 'required' )
    )
  ),
  
);
