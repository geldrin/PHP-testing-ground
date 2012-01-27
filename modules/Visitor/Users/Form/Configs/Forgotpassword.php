<?php

include_once( $this->bootstrap->config['libpath'] . 'clonefish/constants.php');

$dbvalidation =
  Array(
    'type' => 'database',
    'help' => sprintf( $l('users','login_error'), \Springboard\Language::get() . '/users/forgotpassword' ),
    'sql' => 
      "SELECT count(*) as counter 
       FROM users " .
      "WHERE " .
        "email = <FORM.email> AND " .
        "disabled = 0",
    'field' => 'counter',
    'value' => '1'
  );

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
      'validation'  => Array(
        Array( 'type' => 'string', 'regexp' => CF_EMAIL, 'help' => $l('users', 'emailhelp') ),
        $dbvalidation
      )
    ),

  );

?>