<?php

$config = array(

  'action' => array(
    'type'  => 'inputHidden',
    'value' => 'submitcreatechat'
  ),

  'recaptcharesponse' => array(
    'type'  => 'inputHidden',
    'value' => '',
  ),

  'text' => array(
    'displayname' => $l('live', 'chat_text'),
    'type'        => 'inputText',
    'validation'  => array(
      array(
        'type'     => 'string',
        'minimum'  => 2,
        'maximum'  => 512,
        'required' => true,
      ),
    ),
  ),
  
);
