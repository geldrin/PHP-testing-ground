<?php

$config = array(

  'action' => array(
    'type'  => 'inputHidden',
    'value' => 'submitmodifyfeed'
  ),
  
  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('live', 'modifyfeed_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('live', 'modifyfeed_subtitle') . '</span>',
  ),
  
  'name' => array(
    'displayname' => $l('live', 'feedname'),
    'type'        => 'inputText',
    'validation'  => array(
      array(
        'type'     => 'string',
        'required' => true,
        'minimum'  => 2,
        'maximum'  => 512,
      ),
    ),
  ),
  
  'isexternal' => array(
    'displayname' => $l('live', 'external'),
    'type'        => 'inputRadio',
    'values'      => $l->getLov('yesno'),
    'value'       => 1,
  ),
  
  'numberofstreams' => array(
    'displayname' => $l('live', 'numberofstreams'),
    'type'        => 'inputRadio',
    'values'      => $l->getLov('numberofstreams'),
    'value'       => 1,
    'divider'     => '<br/>',
    'divide'      => 1,
  ),
  
);
