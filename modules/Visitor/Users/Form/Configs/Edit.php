<?php

$config = array(
  
  'action' => array(
    'type'  => 'inputHidden',
    'value' => 'submitedit'
  ),
  
  'forward' => array(
    'type'  => 'inputHidden',
    'value' => $this->application->getParameter('forward'),
  ),
  
  'email' => array(
    'displayname' => $l('users', 'email'),
    'type'        => 'inputText',
    'html'        => 'disabled="disabled"',
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
            id <> " . $this->userModel->row['id'] . " AND
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

  'organizationaffiliation' => array(
    'displayname' => $l('users', 'organizationaffiliation'),
    'type'        => 'inputText',
    'validation'  => array(
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
  
  'permissions[]' => array(
    'displayname' => $l('users', 'permissions'),
    'type'        => 'inputCheckboxDynamic',
    'itemlayout'  => $this->checkboxitemlayout,
    'values'      => $l->getLov('permissions'),
    'validation' => array(
    ),
  ),
  
  'departments[]' => array(
    'displayname' => $l('users', 'departments'),
    'type'        => 'inputCheckboxDynamic',
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
    'rowlayout'   => '
      <tr %errorstyle%>
        <td class="labelcolumn">
          <label for="%id%">%displayname%</label>
        </td>
        <td class="elementcolumn">%prefix%%GROUPHTML%%postfix%%errordiv%</td>
      </tr>
    ',
    'validation'  => array(
    ),
  ),
  
);

if ( \Springboard\Language::get() == 'hu' ) {
  $namefirst = array( 'namefirst' => $config['namefirst'], );
  unset( $config['namefirst'] );

  $config = \Springboard\Tools::insertAfterKey( $config, $namefirst, 'namelast' );
}

include( $this->bootstrap->config['modulepath'] . 'Visitor/Form/Configs/Timestampdisabledafter.php');

$departmentModel = $this->bootstrap->getModel('departments');
$departmentModel->addFilter('organizationid', $this->controller->organization['id'] );

if ( $this->controller->organization['isnicknamehidden'] )
  unset( $config['nickname'] );

if ( $departmentModel->getCount() == 0 )
  unset( $config['departments[]'] );

$config = $this->setupConfig( $config );

$config['lastloggedin'] = array(
  'displayname' => $l('users', 'lastloggedin'),
  'type'        => 'inputText',
  'html'        => 'disabled="disabled"',
);
$config['lastloggedinipaddress'] = array(
  'displayname' => $l('users', 'lastloggedinipaddress'),
  'type'        => 'textarea',
  'html'        => 'disabled="disabled"',
);

if ($this->userModel->row['source'] !== 'local' and $this->userModel->row['source']) {
  $config['externalid'] = array(
    'displayname' => $l('users', 'externalid'),
    'type'        => 'inputText',
  );

  foreach( $this->basefields as $field ) {
    if ( !isset( $config[ $field ] ) )
      $field .= '[]';

    if ( !isset( $config[ $field ] ) )
      continue;

    $config[ $field ]['html'] = 'disabled="disabled"';
    unset( $config[ $field ]['validation'] );
  }

}
