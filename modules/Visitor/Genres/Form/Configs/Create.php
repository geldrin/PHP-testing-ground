<?php

$organization = $this->bootstrap->getOrganization();
$config = Array(
  
  'action' => Array(
    'type'  => 'inputHidden',
    'value' => 'submitcreate'
  ),
  
  'organizationid' => Array(
    'type'  => 'inputHidden',
    'value' => $organization->id,
  ),
  
  'name_stringid' => Array(
    'displayname' => $l('genres', 'name'),
    'type'        => 'inputTextMultilanguage',
    'languages'   => $l->getLov('languages'),
    'validation'  => Array(
    )
  ),
  
  'parentid' => Array(
    'displayname' => $l('genres', 'parentid'),
    'type'        => 'selectDynamic',
    'values'      => Array( 0 => $l('genres', 'noparent') ),
    'sql'         => "
      SELECT 
        g.id, s.value
      FROM 
        genres g, strings s
      WHERE 
        g.name_stringid = s.translationof AND
        s.language = 'hu' AND
        g.organizationid = '" . $organization->id . "' AND
        %s
    ",
    'treeid'      => 'id',
    'treeparent'  => 'parentid',
    'treestart'   => '0',
  ),
  
  'weight' => Array(
    'displayname' => $l('', 'weight'),
    'type'        => 'inputText',
    'value'       => 100,
    'validation'  => Array(
      Array( 'type' => 'number' )
    )
  ),

);
