<?php

$organizationid = $this->controller->organization['id'];
$config = Array(
  
  'action' => Array(
    'type'  => 'inputHidden',
    'value' => 'submitcreate'
  ),
  
  'organizationid' => Array(
    'type'     => 'inputHidden',
    'value'    => $organizationid,
    'readonly' => true,
  ),
  
  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('groups', 'create_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('groups', 'create_subtitle') . '</span>',
  ),
  
  'name' => Array(
    'displayname' => $l('groups', 'name'),
    'type'        => 'inputText',
    'validation'  => Array(
      array('type' => 'required'),
    )
  ),
  
);
