<?php
include_once( $this->bootstrap->config['libpath'] . 'clonefish/constants.php');

$config = array(

  'action' => array(
    'type'  => 'inputHidden',
    'value' => 'submitcreateteacher'
  ),

  'id' => array(
    'type'  => 'inputHidden',
    'value' => $this->application->getNumericParameter('id'),
  ),

  'email' => array(
    'displayname' => $l('live', 'teacher_email'),
    'type'        => 'inputText',
    'validation'  => array(
      array(
        'type'   => 'string',
        'regexp' => CF_EMAIL,
        'help'   => $l('users', 'emailhelp')
      ),
    ),
  ),

);
