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

  'name' => Array(
    'displayname' => 'Szerep azonosító',
    'type'        => 'inputText',
    'validation'  => Array(
      array('type' => 'required'),
    )
  ),

  'privileges[]' => array(
    'type'        => 'inputCheckboxDynamic',
    'displayname' => 'Jogok',
    'divide'      => 1,
    'divider'     => '<br/>',
    'sql'         => "
      SELECT id, CONCAT(name, ' (', comment, ')') AS value
      FROM privileges
      ORDER BY name
    ",
    'handlers' => array(
      'clearcache' => array(
        array(
          'glob' => 'roles-*',
        ),
      ),
    ),
  ),
);

$action = $this->application->getParameter('action');
if ( $action == 'modify' or $action == 'update' ) {
  $id = $this->application->getNumericParameter('id');
  $config['privileges[]']['valuesql'] = "
    SELECT privilegeid
    FROM userroles_privileges
    WHERE userroleid = '$id'
  ";
}

$listconfig = Array(

  'table'     => 'userroles AS ur',
  'modify'    => 'ur.id',
  'order'     => Array( 'ur.name' ),

  'fields' => Array(

    Array(
      'field' => 'ur.id',
      'displayname' => 'ID',
    ),

    Array(
      'field' => 'ur.name',
      'displayname' => 'Szerep azonosító',
    ),

  ),

);
