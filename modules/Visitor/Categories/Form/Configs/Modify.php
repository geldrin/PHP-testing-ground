<?php

$organization = $this->bootstrap->getOrganization();
$config = Array(
  
  'action' => Array(
    'type'  => 'inputHidden',
    'value' => 'submitmodify'
  ),
  
  'id' => Array(
    'type'  => 'inputHidden',
    'value' => $this->application->getNumericParameter('id'),
  ),
  
  'organizationid' => Array(
    'type'  => 'inputHidden',
    'value' => $organization->id,
  ),
  
  'name_stringid' => Array(
    'displayname' => $l('categories', 'name'),
    'type'        => 'inputTextMultilanguage',
    'languages'   => $l->getLov('languages'),
    'validation'  => Array(
    )
  ),
  
  'parentid' => Array(
    'displayname' => $l('categories', 'parentid'),
    'type'        => 'selectDynamic',
    'values'      => Array( 0 => $l('categories', 'noparent') ),
    'sql'         => "
      SELECT 
        c.id, s.value
      FROM 
        categories c, strings s
      WHERE 
        c.name_stringid = s.translationof AND
        s.language = 'hu' AND
        c.organizationid = '" . $organization->id . "' AND
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
