<?php

$dbvalidation =
  array(
    'type' => 'database',
    'help' => $l('users','emailregisteredhelp'),
    'sql' => 
      "SELECT count(*) as counter 
       FROM users " .
      "WHERE " .
        "email = <FORM.email>",
    'field' => 'counter',
    'value' => '0'
  );

$config = array(
  
  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('users', 'register_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('users', 'register_subtitle') . '</span>',
  ),
  
  'target' => array(
    'type'  => 'inputHidden',
    'value' => 'submitsignup'
  ),

  'forward' => array(
    'type'  => 'inputHidden',
    'value' => ( $this->application->getParam('forward') ?: '' )
  ),

  'email' => array(
    'displayname' => $l('users', 'email'),
    'type'        => 'inputText',
    'validation'  => array(
      array(
        'type' => 'string',
        'regexp' => CF_EMAIL,
        'help' => $l('users', 'emailhelp')
      ),
      $dbvalidation
    ),
  ),

  'password' => array(
    'displayname' => $l('users', 'password'),
    'type'        => 'inputPassword',
    'validation' => array(
      array( 'type' => 'required' ),
      array(
        'type' => 'string',
        'minimum' => 4,
        'maximum' => 512,
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
    'value'       => LANGUAGE == 'en' ? 'reverse' : 'straight',
    'validation'  => array(
      array( 'type' => 'required' ),
    ),
  ),
  
  'nickname' => array(
    'displayname' => $l('users', 'username'),
    'type'        => 'inputText',
    'validation'  => array(
      array( 'type' => 'required' ),
      array(
        'type' => 'database',
        'help' => $l('users','usernameregistered'),
        'sql'  => "
          SELECT count(*) as counter
          FROM users
          WHERE nickname = <FORM.nickname>
        ",
        'field' => 'counter',
        'value' => '0'
      )
    ),
  ),
  
  'newsletter' => array(
    'displayname' => $l('users', 'newsletter'),
    'type'        => 'inputCheckbox',
    'onvalue'     => 1,
    'offvalue'    => 0,
    'value'       => 1,
    'validation'  => array(
    ),
  ),
  
  'tos' => array(
    'displayname' => $l('sitewide', 'userstos'),
    'type'        => 'inputCheckbox',
    'postfix'     =>
      '<a href="' . LANGUAGE . '/contents/userstos' .
      '" id="termsofservice" target="_blank">' . $l('sitewide', 'userstospostfix') . '</a>'
    ,
    'validation'  => array(
      array(
        'type' => 'required',
        'help' => $l('sitewide', 'userstoshelp'),
      )
    ),
  ),
  
);
