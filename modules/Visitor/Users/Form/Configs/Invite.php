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
  
);
