<?php
$organization = $this->bootstrap->getOrganization();
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
    'type'  => 'inputHidden',
    'value' => $organization->id,
  ),
  
  'name_stringid' => Array(
    'displayname' => $l('recordings', 'genres'),
    'type'        => 'inputTextMultilanguage',
    'languages'   => $l->getLov('languages'),
    'validation'  => Array(
    )
  ),

  'parentid' => Array(

    'displayname' => 'A következő alfaja:',
    'type'        => 'selectDynamic',
    'values'      => Array( 0 => '--- legkülső szintű műfaj ---' ),
    'sql'         => "
      SELECT 
        g.id, s.value
      FROM 
        genres g, strings s
      WHERE 
        g.name_stringid = s.translationof AND
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

$listconfig = Array(

  'table'      => "
    genres AS g
    LEFT JOIN
      strings s
    ON
      g.name_stringid = s.translationof AND 
      s.language = 'hu'
  ",

  'order'      => Array( 'g.weight' ),

  'treeid'             => 'g.id',
  'treestart'          => '0',
  'treeparent'         => 'parentid',
  'treestartinclusive' => true,
  
  'deletesql'  => Array("
    SELECT count(*)
    FROM genres
    WHERE
      parentid='<PARENTID>' AND organizationid = '" . $organization->id . "'"
  ),

  'type'       => 'tree',
  'modify'     => 'g.id',
  'delete'     => 'g.id',
  'where'      => "organizationid = '" . $organization->id . "'",
  
  'fields' => Array(
  
    Array(
      'field' => 's.value',
      'displayname' => 'Megnevezés',
    ),
    
    Array(
      'displayname' => $l('admin', 'id'),
      'field' => 'g.id',
    ),

    Array(
      'displayname' => 'Súlyozás',
      'field' => 'g.weight',
    ),

  ),

);
