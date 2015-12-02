<?php

$config = array(
  
  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('users', 'modify_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('users', 'modify_subtitle') . '</span>',
  ),
  
  'action' => array(
    'type'  => 'inputHidden',
    'value' => 'submitmodify'
  ),
  
  'nickname' => array(
    'displayname' => $l('users', 'username'),
    'type'        => 'inputText',
    'validation'  => array(
      array(
        'type'      => 'string',
        'required'  => true,
        'minimum'   => 4,
        'maximum'   => 20,
        'help'      => $l('users', 'usernamehelp'),
        'jsregexp'  => '/^[a-zA-ZáéíóúöüőűÁÉÍÓÚÖÜŐŰ0-9.-][a-zA-ZáéíóúöüőűÁÉÍÓÚÖÜŐŰ0-9 .-]{2,20}[a-zA-ZáéíóúöüőűÁÉÍÓÚÖÜŐŰ0-9.-]$/',
        'phpregexp' => '/^[a-zA-ZáéíóúöüőűÁÉÍÓÚÖÜŐŰ0-9.-][a-zA-ZáéíóúöüőűÁÉÍÓÚÖÜŐŰ0-9 .-]{2,20}[a-zA-ZáéíóúöüőűÁÉÍÓÚÖÜŐŰ0-9.-]$/ui'
      ),
      array(
        'type' => 'database',
        'help' => $l('users','usernameregistered'),
        'sql'  => "
          SELECT count(*) as counter
          FROM users
          WHERE
            nickname = <FORM.nickname> AND
            id <> " . $this->user['id'] . " AND
            organizationid = '" . $this->controller->organization['id'] . "'
        ",
        'field' => 'counter',
        'value' => '0'
      )
    ),
  ),
  
  'nameprefix' => array(
    'displayname' => $l('users', 'nameprefix'),
    'type'        => 'select',
    'values'      => array('' => $l('users', 'nonameprefix') ) + $l->getLov('title'),
    'validation'  => array(
    ),
  ),
  
  'namefirst' => array(
    'displayname' => $l('users', 'firstname'),
    'type'        => 'inputText',
    'validation'  => array(
      array( 'type' => 'required' ),
    ),
  ),
  
  'namelast' => array(
    'displayname' => $l('users', 'lastname'),
    'type'        => 'inputText',
    'validation'  => array(
      array( 'type' => 'required' ),
    ),
  ),
  
  'nameformat' => array(
    'displayname' => $l('users', 'nameformat'),
    'type'        => 'select',
    'values'      => array(
      'straight' => $l('users', 'nameformatstraight'),
      'reverse'  => $l('users', 'nameformatreverse'),
    ),
    'value'       => \Springboard\Language::get() == 'en' ? 'reverse' : 'straight',
    'validation'  => array(
      array( 'type' => 'required' ),
    ),
  ),
  
  'newsletter' => array(
    'displayname' => $l('users', 'newsletter'),
    'type'        => 'inputCheckbox',
    'itemlayout'  => $this->checkboxitemlayout,
    'onvalue'     => 1,
    'offvalue'    => 0,
    'value'       => 1,
    'validation'  => array(
    ),
  ),
  
  'nameformat' => array(
    'displayname' => $l('users', 'nameformat'),
    'type'        => 'select',
    'values'      => array(
      'straight' => $l('users', 'nameformatstraight'),
      'reverse'  => $l('users', 'nameformatreverse'),
    ),
    'value'       => 'straight', // maybe default to a saner default based on browser language?
    'validation'  => array(
      array( 'type' => 'required' ),
    ),
  ),
  
  'password' => array(
    'displayname' => $l('users', 'newpassword'),
    'type'        => 'inputPassword',
    'validation' => array(
      array(
        'type' => 'string',
        'minimum' => 4,
        'maximum' => 512,
        'required' => false,
      )
    )
  ),
  
  'confirmpassword' => Array(
    'displayname' => $l('users', 'verifynewpassword'),
    'type'        => 'inputPassword',
    'validation' => Array(
      array(
        'type'   => 'string',
        'equals' => 'password',
      )
    )
  ),
  
  'avatarfilename' => Array(
    'displayname' => $l('users', 'avatarfilename'),
    'type'        => 'inputFile',
    'validation'  => Array(
      array(
        'type'             => 'file',
        'required'         => false,
        'help'             => $l('users', 'avatarfilename_help'),
        'imagecreatecheck' => true,
        'types'            => Array('gif', 'jpg', 'png'),
      )
    )
  ),

  'fs2' => array(
    'type'   => 'fieldset',
    'legend' => $l('users', 'permissions'),
    'prefix' => '<span class="legendsubtitle"></span>',
  ),

  'permissions[]' => array(
    'displayname' => $l('users', 'permissions'),
    'type'        => 'inputCheckboxDynamic',
    'html'        => 'disabled="disabled"',
    'itemlayout'  => $this->checkboxitemlayout,
    'values'      => $l->getLov('permissions'),
    'validation'  => array(
    ),
  ),
  
  'departments[]' => array(
    'displayname' => $l('users', 'departments'),
    'type'        => 'inputCheckboxDynamic',
    'html'        => 'disabled="disabled"',
    'itemlayout'  => $this->checkboxitemlayout,
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
      WHERE userid = '" . $this->userModel->row['id'] . "'
    ",
    'validation' => array(
    ),
  ),
  
  'groups[]' => array(
    'displayname' => $l('users', 'groups'),
    'type'        => 'inputCheckboxDynamic',
    'html'        => 'disabled="disabled"',
    'itemlayout'  => $this->checkboxitemlayout,
    'sql'         => "
      SELECT id, name
      FROM groups
      WHERE organizationid = '" . $this->controller->organization['id'] . "'
      ORDER BY name DESC
    ",
    'valuesql' => "
      SELECT groupid
      FROM groups_members
      WHERE userid = '" . $this->userModel->row['id'] . "'
    ",
    'validation'  => array(
    ),
  ),
  
);

if ( !$this->userModel->canUploadAvatar() )
  unset( $config['avatarfilename'] );

$departmentModel = $this->bootstrap->getModel('departments');
$departmentModel->addFilter('organizationid', $this->controller->organization['id'] );

if ( $departmentModel->getCount() == 0 )
  unset( $config['departments[]'] );

$groupModel = $this->bootstrap->getModel('groups');
$groupModel->addFilter('organizationid', $this->controller->organization['id'] );

if ( $groupModel->getCount() == 0 )
  unset( $config['groups[]'] );

if ($this->userModel->row['source'] !== 'local' and $this->userModel->row['source']) {

  foreach( $this->basefields as $field ) {
    if ( !isset( $config[ $field ] ) )
      $field .= '[]';

    if ( !isset( $config[ $field ] ) )
      continue;

    $config[ $field ]['html'] = 'disabled="disabled"';
    unset( $config[ $field ]['validation'] );
  }

}
