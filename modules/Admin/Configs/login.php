<?php

$config = Array(
   
  'action' => Array(
    'type'  => 'inputHidden',
    'value' => 'login'
  ),

  'email' => Array(
    'displayname' => 'E-mail',
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
