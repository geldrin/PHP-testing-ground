<?php

include_once( $this->bootstrap->config['libpath'] . 'clonefish/constants.php');
$encodedemail = rawurlencode( $this->application->getParameter('email') );
$lang         = \Springboard\Language::get();

$config = Array(

  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('users', 'forgotpass_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('users', 'forgotpass_subtitle') . '</span>',
  ),

  'action' => Array(
    'type'  => 'inputHidden',
    'value' => 'submitforgotpassword'
  ),

  'email' => Array(
    'displayname' => $l('users', 'email'),
    'type'        => 'inputText',
    'value'       => $this->application->getParameter('email'),
    'validation'  => Array(
      Array(
        'type'   => 'string',
        'regexp' => CF_EMAIL,
        'help'   => $l('users', 'emailhelp')
      ),
      Array(
        'type'   => 'database',
        'help'   => sprintf(
          $l('users','login_error'),
          $lang . '/users/forgotpassword?email=' . $encodedemail,
          $lang . '/users/resend?email=' . $encodedemail
        ),
        'sql'    => "
          SELECT count(*) as counter
          FROM users
          WHERE
            email = <FORM.email> AND
            disabled = '" . \Model\Users::USER_VALIDATED . "' AND
            organizationid = '" . $this->controller->organization['id'] . "'
        ",
        'field' => 'counter',
        'value' => '1'
      )
    )
  ),

);
