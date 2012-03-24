<?php

$config = Array(

  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('users', 'changepass_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('users', 'changepass_subtitle') . '</span>',
  ),

  'action' => Array(
    'type'  => 'inputHidden',
    'value' => 'submitchangepassword'
  ),
  
  'a' => Array(
    'type'  => 'inputHidden',
    'value' => $this->application->getParameter('a'),
  ),
  
  'b' => Array(
    'type'  => 'inputHidden',
    'value' => $this->application->getParameter('b'),
  ),
  
  'password' => Array(
    'displayname' => $l('users', 'changepass_newpassword'),
    'type'        => 'inputPassword',
    'validation'  => Array(
      array(
        'type' => 'string',
        'minimum' => 4,
        'maximum' => 512,
      )
    )
  ),

  'confirmpassword' => Array(
    'displayname' => $l('users', 'changepass_newpasswordverify'),
    'type'        => 'inputPassword',
    'validation' => Array(
      array(
        'type'   => 'string',
        'equals' => 'password',
      )
    )

  ),

);
