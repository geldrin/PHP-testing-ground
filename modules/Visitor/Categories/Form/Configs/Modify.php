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
  
  'organizationid' => Array(
    'type'     => 'inputHidden',
    'value'    => $organizationid,
    'readonly' => true,
  ),
  
  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('categories', 'modify_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('categories', 'modify_subtitle') . '</span>',
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
        c.organizationid = '" . $organizationid . "' AND
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
