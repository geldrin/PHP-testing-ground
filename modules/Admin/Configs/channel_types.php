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
    'values'      => array( '' => '' ),
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
    'validation'  => Array(
      Array( 'type' => 'number', 'minimum' => '1', 'help' => 'Kötelező választani intézményt!' )
    )
  ),
  
  'parentid' => Array(
    'displayname' => 'Szülő típus',
    'type'        => 'selectDynamic',
    'values'      => array( 0 => 'Nincs szülő típus' ),
    'sql'         => "
      SELECT
        ct.id, CONCAT( s.value, ' - ', ct.id )
      FROM
        channel_types AS ct,
        strings AS s
      WHERE
        s.translationof = ct.name_stringid AND
        s.language = 'hu' AND
        %s
      ORDER BY s.value
    ",
    'treeid'      => 'id',
    'treeparent'  => 'parentid',
    'treestart'   => '0',
  ),
  
  'name_stringid' => Array(
    'displayname' => 'Típus',
    'type'        => 'inputTextMultiLanguage',
    'languages'   => $l->getLov('languages'),
    'value'       => 0,
    'validation'  => Array(
    )
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

$listconfig = Array(
  
  'treeid'             => 'ct.id',
  'treestart'          => '0',
  'treeparent'         => 'ct.parentid',
  'treestartinclusive' => true,
  
  'type'      => 'tree',
  'table'     => 'channel_types ct, strings s, organizations AS o, strings AS os',
  'where'     => '
    ct.name_stringid = s.translationof AND
    s.language       = "hu" AND
    o.id             = ct.organizationid AND
    o.name_stringid  = os.translationof AND
    os.language      = "hu"
  ',
  'deletesql'  => Array("
    SELECT COUNT(*)
    FROM channel_types
    WHERE
      parentid='<PARENTID>'"
  ),
  'modify'    => 'ct.id',
  'delete'    => 'ct.id',
  'order'     => Array( 'ct.organizationid, ct.weight, s.value' ),
  
  'fields' => Array(

    Array(
      'field' => 'ct.id',
      'displayname' => 'ID',
    ),
    
    Array(
      'field' => 's.value',
      'displayname' => 'Cím',
    ),
    
    Array(
      'field' => 'os.value',
      'displayname' => 'Intézmény',
    ),
    
  ),

);
