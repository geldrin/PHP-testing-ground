<?php

$config = array(
  
  'action' => array(
    'type'     => 'inputHidden',
    'value'    => 'submitnewcomment',
    'readonly' => true,
  ),
  
  'recordingid' => array(
    'type'  => 'inputHidden',
    'value' => $this->application->getNumericParameter('id'),
  ),
  
  'text' => array(
    'displayname' => $l('recordings', 'yourcomment'),
    'type'        => 'textarea',
    'validation'  => array(
      array('type' => 'required'),
    ),
  ),
  
);
