<?php

include_once( $this->bootstrap->config['libpath'] . 'clonefish/constants.php');
$language = \Springboard\Language::get();
$config = array(

  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('users', 'register_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('users', 'register_subtitle') . '</span>',
  ),

  'action' => array(
    'type'  => 'inputHidden',
    'value' => 'submitsignup'
  ),

  'forward' => array(
    'type'  => 'inputHidden',
    'value' => ( $this->application->getParameter('forward') ?: '' ),
  ),

  'inviteid' => array(
    'type'  => 'inputHidden',
    'value' => ( $this->invite and $this->invite['id'] )? $this->invite['id']: '',
  ),

  'email' => array(
    'displayname' => $l('users', 'email'),
    'type'        => 'inputText',
    'validation'  => array(
      array(
        'type'   => 'string',
        'regexp' => CF_EMAIL,
        'help'   => $l('users', 'emailhelp')
      ),
      array(
        'type' => 'database',
        'help' => $l('users','emailregisteredhelp'),
        'sql'  =>  "
          SELECT count(*) as counter
          FROM users
          WHERE
            email = <FORM.email> AND
            organizationid = '" . $this->controller->organization['id'] . "'
        ",
        'field' => 'counter',
        'value' => '0'
      ),
    ),
  ),

  'password' => array(
    'displayname' => $l('users', 'password'),
    'type'        => 'inputPassword',
    'validation' => array(
      array(
        'type'     => 'string',
        'minimum'  => 4,
        'maximum'  => 512,
        'required' => true,
      )
    )
  ),

  'confirmpassword' => Array(
    'displayname' => $l('users', 'verifypassword'),
    'type'        => 'inputPassword',
    'validation' => Array(
      array(
        'type'   => 'string',
        'equals' => 'password',
      )
    )
  ),

  'nameprefix' => array(
    'displayname' => $l('users', 'nameprefix'),
    'type'        => 'select',
    'values'      => array('' => $l('users', 'nonameprefix') ) + $l->getLov('title'),
    'validation'  => array(
      array( 'type' => 'required' )
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
    'value'       => $language == 'en' ? 'reverse' : 'straight',
    'validation'  => array(
      array( 'type' => 'required' ),
    ),
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
            organizationid = '" . $this->controller->organization['id'] . "'
        ",
        'field' => 'counter',
        'value' => '0'
      )
    ),
  ),

  'organizationaffiliation' => array(
    'displayname' => $l('users', 'organizationaffiliation'),
    'type'        => 'inputText',
    'validation'  => array(
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

  'tos' => array(
    'displayname' => $l('', 'userstos'),
    'type'        => 'inputCheckbox',
    'itemlayout'  => $this->checkboxitemlayout,
    'postfix'     =>
      '<a href="' . $language . '/contents/userstos' .
      '" id="termsofservice" target="_blank">' . $l('', 'userstospostfix') . '</a>'
    ,
    'validation'  => array(
      array(
        'type' => 'required',
        'help' => $l('', 'userstoshelp'),
      )
    ),
  ),

);

if ( \Springboard\Language::get() == 'hu' ) {
  $namefirst = array( 'namefirst' => $config['namefirst'], );
  unset( $config['namefirst'] );

  $config = \Springboard\Tools::insertAfterKey( $config, $namefirst, 'namelast' );
}

if ( $this->controller->organization['isnicknamehidden'] )
  unset( $config['nickname'] );

if ( $this->controller->organization['isorganizationaffiliationrequired'] ) {
  $config['organizationaffiliation']['displayname'] .= ' <span class="required">*</span>';
  $config['organizationaffiliation']['validation'][] = array(
    'type'      => 'string',
    'required'  => true,
    'minimum'   => 3,
    'maximum'   => 100,
    'help'      => $l('users', 'organizationaffiliationhelp'),
  );
}

if ( $this->invite ) {
  if ( $this->invite['namefirst'] )
    $config['namefirst']['value'] = $this->invite['namefirst'];

  if ( $this->invite['namelast'] )
    $config['namelast']['value'] = $this->invite['namelast'];

  if ( $this->invite['email'] )
    $config['email']['value'] = $this->invite['email'];
}
