<?php

$config = array(
  
  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('users', 'modify_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('users', 'modify_subtitle') . '</span>',
  ),
  
  'action' => array(
    'type'  => 'inputHidden',
    'value' => 'submitedit'
  ),
  
  'forward' => array(
    'type'  => 'inputHidden',
    'value' => $this->application->getParameter('forward'),
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
    'values'      => $l->getLov('permissions'),
    'validation' => array(
    ),
  ),
  
);
