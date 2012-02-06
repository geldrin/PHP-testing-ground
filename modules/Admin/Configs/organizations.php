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
  
  'parentid' => Array(
    'displayname' => 'Szülő intézmény',
    'type'        => 'selectDynamic',
    'values'      => array( 0 => 'Nincs szülő intézmény' ),
    'sql'         => "
      SELECT 
        id, CONCAT( IF(LENGTH(nameoriginal) > 0, nameoriginal, nameenglish ), ' - ', id )
      FROM 
        organizations
      WHERE
        %s
      ORDER BY
        IF(LENGTH(nameoriginal), nameoriginal, nameenglish )
    ",
    'treeid'      => 'id',
    'treeparent'  => 'parentid',
    'treestart'   => '0',
  ),
  
  'nameoriginal' => array(
    'displayname' => 'Eredeti név',
    'type'        => 'inputText',
    'validation'  => array(
      array( 'type' => 'required' ),
      array(
        'type' => 'string',
        'minimum' => 2,
        'maximum' => 512,
      ),
    ),
  ),
  
  'nameshortoriginal' => array(
    'displayname' => 'Rövid eredeti név',
    'type'        => 'inputText',
    'validation'  => array(
      array( 'type' => 'required' ),
      array(
        'type' => 'string',
        'minimum' => 2,
        'maximum' => 512,
      ),
    ),
  ),

  'nameenglish' => array(
    'displayname' => 'Angol név',
    'type'        => 'inputText',
    'validation'  => array(
      array(
        'type' => 'string',
        'minimum'  => 2,
        'maximum'  => 512,
        'required' => false,
      ),
    ),
  ),
  
  'nameshortenglish' => array(
    'displayname' => 'Rövid angol név',
    'type'        => 'inputText',
    'validation'  => array(
      array(
        'type' => 'string',
        'minimum'  => 2,
        'maximum'  => 512,
        'required' => false,
      ),
    ),
  ),
  
  'url' => array(
    'displayname' => 'URL',
    'type'        => 'inputText',
    'validation'  => array(
      array(
        'type' => 'string',
        'minimum'  => 2,
        'maximum'  => 512,
        'required' => false,
      ),
    ),
  ),
  
  'domain' => array(
    'displayname' => 'Domain',
    'type'        => 'inputText',
    'validation'  => array(
      array(
        'type' => 'string',
        'minimum'  => 2,
        'maximum'  => 512,
        'required' => false,
      ),
    ),
  ),
  
  'bordercolor' => array(
    'displayname' => 'Kereső szegély színe',
    'type'        => 'inputText',
    'validation'  => array(
      array(
        'type' => 'string',
        'minimum'  => 6,
        'maximum'  => 6,
        'required' => false,
      ),
    ),
  ),
  
);

$listconfig = Array(

  'table'     => 'organizations',
  'order'     => Array( 'id DESC' ),
  'modify'    => 'id',

  'fields' => Array(
    
    Array(
      'field' => 'id',
      'displayname' => 'ID',
    ),
    
    Array(
      'field' => 'domain',
      'displayname' => 'Domain',
    ),

    Array(
      'field' => 'nameoriginal',
      'displayname' => 'Eredeti név',
    ),
    
    Array(
      'field' => 'nameshortoriginal',
      'displayname' => 'Rövid név',
    ),


  ),

);
