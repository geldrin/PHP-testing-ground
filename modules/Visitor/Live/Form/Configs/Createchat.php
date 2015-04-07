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

  'isquestion' => array(
    'displayname' => $l('live', 'chatquestion'),
    'type'        => 'inputCheckbox',
    'onvalue'     => 1,
    'offvalue'    => 0,
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
