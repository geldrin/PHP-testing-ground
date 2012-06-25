<?php

$config = array(

  'action' => array(
    'type'  => 'inputHidden',
    'value' => 'submitcreatefeed'
  ),
  
  'event' => array(
    'type'  => 'inputHidden',
    'value' => $this->application->getNumericParameter('event'),
  ),
  
  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('live', 'createfeed_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('live', 'createfeed_subtitle') . '</span>',
  ),
  
  'name' => array(
    'displayname' => $l('live', 'feedname'),
    'type'        => 'inputText',
    'validation'  => array(
      array(
        'required' => true,
        'type'     => 'string',
        'minimum'  => 2,
        'maximum'  => 512,
      ),
    ),
  ),

  'isexternal' => array(
    'displayname' => $l('live', 'external'),
    'type'        => 'inputRadio',
    'values'      => $l->getLov('yesno'),
    'value'       => 0,
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
