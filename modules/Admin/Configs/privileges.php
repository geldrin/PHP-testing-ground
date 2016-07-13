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
    'displayname' => 'Jog azonosító',
    'type'        => 'inputText',
    'validation'  => Array(
      array('type' => 'required'),
    )
  ),

  'comment' => Array(
    'displayname' => 'A jog leírása',
    'type'        => 'inputText',
    'validation'  => Array(
    )
  ),

  'roles[]' => array(
    'type'        => 'inputCheckboxDynamic',
    'displayname' => 'Szerepek',
    'divide'      => 1,
    'sql'         => "
      SELECT id, name
      FROM userroles
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
  $config['roles[]']['valuesql'] = "
    SELECT userroleid
    FROM userroles_privileges
    WHERE privilegeid = '$id'
  ";
}

$listconfig = Array(

  'table'     => 'privileges AS p',
  'modify'    => 'p.id',
  'order'     => Array( 'p.name' ),

  'fields' => Array(

    Array(
      'field' => 'p.id',
      'displayname' => 'ID',
    ),

    Array(
      'field' => 'p.name',
      'displayname' => 'Jog azonosító',
    ),

    Array(
      'field' => 'p.comment',
      'displayname' => 'Jog leírás',
    ),

  ),

);
