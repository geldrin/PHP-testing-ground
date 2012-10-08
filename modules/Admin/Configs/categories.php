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
  
  'organizationid' => Array(
    'displayname' => 'Intézmény',
    'type'        => 'selectDynamic',
    'sql'         => "
      SELECT
        o.id, CONCAT( s.value, ' - ', o.id )
      FROM
        organizations AS o,
        strings AS s
      WHERE
        s.translationof = o.name_stringid AND
        s.language = 'hu' AND
        %s
      ORDER BY s.value
    ",
    'treeid'      => 'id',
    'treeparent'  => 'parentid',
    'treestart'   => '0',
  ),
  
  'name_stringid' => Array(
    'displayname' => $l('recordings', 'categories'),
    'type'        => 'inputTextMultilanguage',
    'languages'   => $l->getLov('languages'),
    'validation'  => Array(
    )
  ),
  
  'namehyphenated_stringid' => Array(
    'displayname' => 'Kategória neve kötőjelekkel',
    'type'        => 'inputTextMultilanguage',
    'languages'   => $l->getLov('languages'),
    'validation'  => Array(
    )
  ),

  'parentid' => Array(

    'displayname' => 'A következő alfaja:',
    'type'        => 'selectDynamic',
    'values'      => Array( 0 => '--- legkülső szintű kategória ---' ),
    'sql'         => "
      SELECT 
        c.id, s.value
      FROM 
        categories c, strings s
      WHERE 
        c.name_stringid = s.translationof AND
        s.language = 'hu' AND
        %s
    ",
    'treeid'      => 'id',
    'treeparent'  => 'parentid',
    'treestart'   => '0',
  ),

  'weight' => Array(
    'displayname' => 'Súlyozás',
    'type'        => 'inputText',
    'value'       => 100,
    'validation'  => Array(
      Array( 'type' => 'number' )
    )
  ),

);

//TODO listaba organizationoket, rendezni szerintuk
$listconfig = Array(

  'table'      => "
    categories AS c
    LEFT JOIN
      strings s
    ON
      c.name_stringid = s.translationof AND 
      s.language = 'hu'
  ",

  'order'      => Array( 'c.weight' ),

  'treeid'             => 'c.id',
  'treestart'          => '0',
  'treeparent'         => 'parentid',
  'treestartinclusive' => true,

  'deletesql'  => Array("
    SELECT count(*)
    FROM categories
    WHERE
      parentid='<PARENTID>'"
  ),

  'type'       => 'tree',
  'modify'     => 'c.id',
  'delete'     => 'c.id',
  
  'fields' => Array(
  
    Array(
      'field' => 's.value',
      'displayname' => 'Megnevezés',
    ),
    
    Array(
      'displayname' => $l('admin', 'id'),
      'field' => 'c.id',
    ),

    Array(
      'displayname' => 'Súlyozás',
      'field' => 'c.weight',
    ),

  ),

);
