<?php

include_once( $this->bootstrap->config['libpath'] . 'clonefish/constants.php');

$config = Array(
  
  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('users', 'resend_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('users', 'resend_subtitle') . '</span>',
  ),
  
  'action' => Array(
    'type'  => 'inputHidden',
    'value' => 'submitresend'
  ),
  
  'email' => Array(
    'displayname' => $l('users', 'email'),
    'type'        => 'inputText',
    'value'       => $this->application->getParameter('email'),
    'validation'  => Array(
      Array(
        'type'   => 'string',
        'regexp' => CF_EMAIL,
        'help'   => $l('users', 'emailhelp'),
      ),
      Array(
        'type'   => 'database',
        'help'   => $l('users', 'resendhelp'),
        'sql'    => "
          SELECT count(*) as counter
          FROM users
          WHERE
            email = <FORM.email> AND
            disabled = '" . \Model\Users::USER_UNVALIDATED . "' AND
            organizationid = '" . $this->controller->organization['id'] . "'
        ",
        'field' => 'counter',
        'value' => '1'
      )
    )
  ),
  
);
