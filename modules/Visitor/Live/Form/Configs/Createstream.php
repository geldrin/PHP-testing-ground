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
  
  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('live', 'createstream_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('live', 'createstream_subtitle') . '</span>',
  ),
  
  'name' => array(
    'displayname' => $l('live', 'streamname'),
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
  
  'quality' => array(
    'displayname' => $l('live', 'quality'),
    'type'        => 'inputRadio',
    'values'      => $l->getLov('quality'),
    'value'       => 0,
  ),
  
  'compatibility[]' => array(
    'displayname' => $l('live', 'compatibility'),
    'type'        => 'inputCheckboxDynamic',
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
