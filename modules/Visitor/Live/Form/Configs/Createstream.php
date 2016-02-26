<?php

$config = array(

  'action' => array(
    'type'  => 'inputHidden',
    'value' => 'submitcreatestream'
  ),
  
  'id' => array(
    'type'  => 'inputHidden',
    'value' => $this->application->getNumericParameter('id'),
  ),
  
  'qualitytag' => array(
    'displayname' => $l('live', 'quality'),
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
  
  'weight' => array(
    'displayname' => $l('live', 'stream_weight'),
    'postfix'     => '<div class="smallinfo">' . $l('live', 'stream_weight_postfix') . '</div>',
    'type'        => 'inputText',
    'value'       => 100,
    'validation'  => array(
      array(
        'type'     => 'number',
        'help'     => $l('live', 'stream_weight_help'),
        'real'     => 0,
        'required' => true,
        'minimum'  => -PHP_INT_MAX,
        'maximum'  => PHP_INT_MAX,
      ),
    ),
  ),
  
  'compatibility[]' => array(
    'displayname' => $l('live', 'compatibility'),
    'type'        => 'inputCheckboxDynamic',
    'itemlayout'  => $this->checkboxitemlayout,
    'values'      => $l->getLov('live_compatibility'),
    'html'        => 'class="livecompatibility"',
    'postfix'     =>
      '<div class="smallinfo desktop hidden">' . $l('live', 'compatibility_desktop') . '</div>' .
      '<div class="smallinfo mobile hidden">' . $l('live', 'compatibility_mobile') . '</div>'
    ,
    'validation'  => array(
      array(
        'type' => 'required',
      ),
    ),
  ),
  
);
