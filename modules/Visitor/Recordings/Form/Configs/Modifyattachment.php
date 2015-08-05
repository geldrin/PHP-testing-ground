<?php

$config = Array(
  
  'action' => Array(
    'type'  => 'inputHidden',
    'value' => 'submitmodifyattachment'
  ),
  
  'id' => Array(
    'type'  => 'inputHidden',
    'value' => $this->application->getNumericParameter('id'),
  ),
  
  'forward' => Array(
    'type'  => 'inputHidden',
    'value' => $this->application->getParameter('forward'),
  ),
  
  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('recordings', 'modifyattachment_title'),
    'prefix' => '<span class="legendsubtitle">%s</span>',
  ),

  'title' => array(
    'type'        => 'inputText',
    'displayname' => $l('recordings', 'attachment_title') . '<span class="required">*</span>',
    'validation'  => array(
      Array(
        'type'     => 'string',
        'required' => true,
      ),
    ),
  ),

  'isdownloadable' => array(
    'type'        => 'inputRadio',
    'displayname' => $l('recordings', 'isdownloadable'),
    'values'      => $l->getLov('noyes'),
    'value'       => '1',
  ),
  
);
