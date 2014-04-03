<?php

include_once( $this->bootstrap->config['libpath'] . 'clonefish/constants.php');
$user   = $this->bootstrap->getSession('user');
$config = array(
  
  'action' => array(
    'type'  => 'inputHidden',
    'value' => 'submitmassinvite'
  ),
  
  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('users', 'massinvite_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('users', 'massinvite_subtitle') . '</span>',
  ),
  
  'encoding' => Array(
    'displayname' => $l('users', 'encoding'),
    'type'        => 'select',
    'values'      => array(
      'Windows-1252' => 'Windows Central European (Windows-1252)',
      'ISO-8859-2'   => 'ASCII Central European (ISO-8859-2)',
      'UTF-16LE'     => 'UTF16LE',
      'UTF-8'        => 'UTF-8',
      'ASCII'        => 'ASCII',
    ),
    'validation' => array(
      array('type' => 'required'),
    ),
  ),
  
  'delimeter' => Array(
    'displayname' => $l('users', 'delimeter'),
    'type'        => 'select',
    'values'      => array(
      ';'   => ';',
      ','   => ',',
      'tab' => 'tab',
    ),
    'value'       => ';',
    'validation' => array(
      array('type' => 'required'),
    ),
  ),
  
  'invitefile' => Array(
    'displayname' => $l('users', 'invitefile'),
    'type'        => 'inputFile',
    'validation'  => Array(
      array(
        'type'             => 'file',
        'required'         => true,
        'help'             => $l('users', 'invitefile_help'),
        'imagecreatecheck' => false,
        'extensions'       => Array('csv', 'txt',),
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
    'valuesql'    => "
      SELECT departmentid
      FROM users_departments
      WHERE userid = '" . $user['id'] . "'
    ",
    'validation' => array(
    ),
  ),
  
  'groups[]' => array(
    'displayname' => $l('users', 'groups'),
    'type'        => 'inputCheckboxDynamic',
    'sql'         => "
      SELECT g.id, g.name
      FROM
        groups AS g,
        groups_members AS gm
      WHERE
        gm.userid        = '" . $user['id'] . "' AND
        g.id             = gm.groupid AND
        g.organizationid = '" . $this->controller->organization['id'] . "'
      ORDER BY g.name DESC
    ",
    'validation'  => array(
    ),
  ),
);

include( $this->bootstrap->config['modulepath'] . 'Visitor/Form/Configs/Timestampdisabledafter.php');
