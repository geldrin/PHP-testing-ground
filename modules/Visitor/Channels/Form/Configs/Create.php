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
    'itemlayout'  => $this->checkboxitemlayout,
    'sql'         => "
      SELECT ct.id, s.value
      FROM channel_types AS ct, strings AS s
      WHERE
        s.translationof   = ct.name_stringid AND
        s.language        = '" . \Springboard\Language::get() . "' AND
        ct.isfavorite     = '0' AND
        ct.organizationid = '" . $this->controller->organization['id'] . "' AND
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
);

$tokenDisabled = true;
include( $this->bootstrap->config['modulepath'] . 'Visitor/Form/Configs/Accesstype.php');

if (
     $this->parentchannelModel and $this->parentchannelModel->id
   ) {

  $config['accesstype']['postfix']    = $l('channels', 'accesstype_disabled');
  $config['departments[]']['postfix'] = '';
  $config['accesstype']['html']       = 'disabled="disabled"';
  $config['departments[]']['html']    = 'disabled="disabled"';
  $config['groups[]']['html']         = 'disabled="disabled"';

  $channelid = $this->channelroot['id'];
  $config['departments[]']['valuesql'] = "
    SELECT departmentid
    FROM access
    WHERE
      channelid = '$channelid' AND
      departmentid IS NOT NULL
  ";
  $config['groups[]']['valuesql'] = "
    SELECT groupid
    FROM access
    WHERE
      channelid = '$channelid' AND
      groupid IS NOT NULL
  ";

}
