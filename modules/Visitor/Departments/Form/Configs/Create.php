<?php

$organizationid = $this->controller->organization['id'];
$config = Array(
  
  'action' => Array(
    'type'  => 'inputHidden',
    'value' => 'submitcreate'
  ),
  
  'organizationid' => Array(
    'type'     => 'inputHidden',
    'value'    => $organizationid,
    'readonly' => true,
  ),

  'name' => Array(
    'displayname' => $l('departments', 'name'),
    'type'        => 'inputText',
    'validation'  => array(
      array('type' => 'required'),
    ),
  ),
  
  'nameshort' => Array(
    'displayname' => $l('departments', 'nameshort'),
    'type'        => 'inputText',
  ),
  
  'parentid' => Array(
    'displayname' => $l('departments', 'parentid'),
    'type'        => 'selectDynamic',
    'values'      => Array( 0 => $l('departments', 'noparent') ),
    'sql'         => "
      SELECT id, name
      FROM departments
      WHERE
        organizationid = '" . $organizationid . "' AND
        %s
      ORDER BY weight, name
    ",
    'treeid'      => 'id',
    'treeparent'  => 'parentid',
    'treestart'   => '0',
    'value'       => $this->application->getNumericParameter('parentid'),
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
