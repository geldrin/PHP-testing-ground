<?php

$config = Array(
   
  'action' => Array(
    'type'  => 'inputHidden',
    'value' => 'insert'
  ),

  'id' => Array(
    'type'  => 'inputHidden',
    'value' => '0'
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
    'postfix'     => sprintf(
      '<br/><br/><div class="info">
        Kitöltés esetén felülíródik.<br/>
        A felhasználó jelszavát mindig titkosítjuk ezért beállítás után nem visszanyerhető.<br/>
        Generált jelszó: %s</div>
      ',
      'asd'
    ),
    'type'        => 'inputText',
    'validation'  => Array(
    )
  ),
  
  'name' => Array(
    'displayname' => 'Név',
    'type'        => 'inputText',
    'validation'  => Array(
      Array( 'type' => 'required' )
    )
  ),
  
);

$listconfig = Array(

  'table'     => 'users',
  'modify'    => 'id',
  'order'     => Array('id DESC' ),
  
  'fields' => Array(

    Array(
      'displayname' => 'ID',
      'field' => 'id',
    ),

    Array(
      'field' => 'email',
      'displayname' => 'E-Mail',
    ),
    
    Array(
      'field' => 'name',
      'displayname' => 'Név',
    ),
    
  ),

);
