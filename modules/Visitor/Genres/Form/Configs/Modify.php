<?php

$organizationid = $this->controller->organization['id'];
$config = Array(
  
  'action' => Array(
    'type'  => 'inputHidden',
    'value' => 'submitmodify'
  ),
  
  'id' => Array(
    'type'  => 'inputHidden',
    'value' => $this->application->getNumericParameter('id'),
  ),
  
  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('genres', 'modify_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('genres', 'modify_subtitle') . '</span>',
  ),
  
  'organizationid' => Array(
    'type'     => 'inputHidden',
    'value'    => $organizationid,
    'readonly' => true,
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
        g.organizationid = '" . $organizationid . "' AND
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
