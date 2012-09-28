<?php

$config = array(

  'action' => array(
    'type'  => 'inputHidden',
    'value' => 'submitcreate'
  ),
  
  'parent' => array(
    'type'  => 'inputHidden',
    'value' => $this->application->getNumericParameter('parent'),
  ),

  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('channels', 'create_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('channels', 'create_subtitle') . '</span>',
  ),
  
  'title' => array(
    'displayname' => $l('channels', 'nameoriginal'),
    'type'        => 'inputText',
    'validation'  => array(
      array( 'type' => 'required' ),
      array(
        'type' => 'string',
        'minimum' => 4,
        'maximum' => 512,
      ),
    ),
  ),
  
  'subtitle' => array(
    'displayname' => $l('channels', 'subtitleoriginal'),
    'type'        => 'inputText',
    'validation'  => array(
      array(
        'type'     => 'string',
        'minimum'  => 4,
        'maximum'  => 512,
        'required' => false,
      ),
    ),
  ),
  
  'description' => array(
    'displayname' => $l('channels', 'descriptionoriginal'),
    'type'        => 'textarea',
    'validation'  => array(
      array(
        'type' => 'string',
        'minimum'  => 4,
        'required' => false,
      ),
    ),
  ),
  
  'channeltypeid' => array(
    'displayname' => $l('channels', 'channeltype'),
    'type'        => 'selectDynamic',
    'sql'         => "
      SELECT ct.id, s.value
      FROM channel_types AS ct, strings AS s
      WHERE
        s.translationof = ct.name_stringid AND
        s.language      = '" . \Springboard\Language::get() . "' AND
        ct.isfavorite   = 0 AND
        %s
      ORDER BY ct.weight
    ",
    'treeid'      => 'id',
    'treestart'   => '0',
    'treeparent'  => 'ct.parentid',
    'validation'  => array(
      array( 'type' => 'required' ),
      array(
        'type'     => 'number',
        'help'     => $l('channels', 'channeltypehelp'),
      ),
    ),
  ),
  
  'ispublic' => array(
    'displayname' => $l('channels', 'ispublic'),
    'type'        => 'inputRadio',
    'values'      => $l->getLov('yesno'),
    'value'       => 0,
    'validation'  => array(
    ),
  ),
  
);

if (
     $this->parentchannelModel and $this->parentchannelModel->id and
     !$this->parentchannelModel->row['ispublic']
   ) {
  
  $config['ispublic']['html']    = 'disabled="disabled"';
  $config['ispublic']['postfix'] = $l('channels', 'ispublic_disabled');
  
}
