<?php

$config = Array(
  
  'action' => Array(
    'type'  => 'inputHidden',
    'value' => 'submituploadattachment'
  ),
  
  'forward' => Array(
    'type'  => 'inputHidden',
    'value' => $this->application->getParameter('forward')
  ),
  
  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('recordings', 'uploadattachment_title'),
  ),
  
  'recordingid' => array(
    'type'  => 'inputHidden',
    'value' => $this->application->getNumericParameter('recordingid'),
  ),
  
  'title' => array(
    'type'        => 'inputText',
    'displayname' => $l('recordings', 'attachment_title'),
    'validation'  => array(
    ),
  ),
  
  'isdownloadable' => array(
    'type'        => 'inputRadio',
    'displayname' => $l('recordings', 'isdownloadable'),
    'values'      => $l->getLov('noyes'),
    'value'       => '1',
  ),
  
  'file' => Array(
    'type'        => 'inputFile',
    'displayname' => $l('recordings', 'attachment'),
    'validation'  => Array(
      Array(
        'type'       => 'file',
        'required'   => true,
        'help'       => $l('recordings', 'attachment_help'),
      ),
    ),
  ),
  
);
