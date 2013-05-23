<?php

include_once( $this->bootstrap->config['libpath'] . 'clonefish/constants.php');

$config = array(
  
  'action' => array(
    'type'  => 'inputHidden',
    'value' => 'submitinvite'
  ),
  
  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('users', 'invite_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('users', 'invite_subtitle') . '</span>',
  ),
  
  'email' => Array(
    'displayname' => $l('users', 'email'),
    'type'        => 'inputText',
    'validation'  => Array(
      Array(
        'type'   => 'string',
        'regexp' => CF_EMAIL,
        'help'   => $l('users', 'emailhelp')
      ),
      Array(
        'type'   => 'database',
        'help' => $l('users','emailregisteredhelp'),
        'sql'    => "
          SELECT count(*) as counter
          FROM users
          WHERE
            email = <FORM.email> AND
            organizationid = '" . $this->controller->organization['id'] . "'
        ",
        'field' => 'counter',
        'value' => '0'
      )
    )
  ),
  
  'permissions[]' => array(
    'displayname' => $l('users', 'permissions'),
    'type'        => 'inputCheckboxDynamic',
    'values'      => $l->getLov('permissions'),
    'validation' => array(
    ),
  ),
  
  'departments[]' => array(
    'displayname' => $l('users', 'departments'),
    'type'        => 'inputCheckboxDynamic',
    'treeid'      => 'id',
    'treestart'   => '0',
    'treeparent'  => 'parentid',
    'sql'         => "
      SELECT id, name
      FROM departments
      WHERE
        organizationid = '" . $this->controller->organization['id'] . "' AND
        %s
      ORDER BY weight, name
    ",
    'validation' => array(
    ),
  ),
  
  'groups[]' => array(
    'displayname' => $l('users', 'groups'),
    'type'        => 'inputCheckboxDynamic',
    'sql'         => "
      SELECT g.id, g.name
      FROM groups AS g
      WHERE organizationid = '" . $this->controller->organization['id'] . "'
      ORDER BY g.name DESC
    ",
    'validation'  => array(
    ),
  ),
  
);

include( $this->bootstrap->config['modulepath'] . 'Visitor/Form/Configs/Timestampdisabledafter.php');

$db              = $this->bootstrap->getAdoDB();
$departmentcount = $db->getOne("
  SELECT COUNT(*)
  FROM departments
  WHERE organizationid = '" . $this->controller->organization['id'] . "'
");
$groupcount      = $db->getOne("
  SELECT COUNT(*)
  FROM groups
  WHERE organizationid = '" . $this->controller->organization['id'] . "'
");

if ( !$departmentcount )
  unset( $config['departments[]'] );

if ( !$groupcount )
  unset( $config['groups[]'] );
