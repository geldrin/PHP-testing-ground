<?php
$organization = $this->bootstrap->getOrganization();
$config = Array(

  'action' => Array(
    'type'  => 'inputHidden',
    'value' => 'insert'
  ),

  'id' => Array(
    'type'  => 'inputHidden',
    'value' => '0'
  ),

  'organizationid' => Array(
    'type'  => 'inputHidden',
    'value' => $organization->id,
  ),

  'shortname' => Array(
    'displayname' => 'Nyelv azonosító',
    'type'        => 'inputText',
    'validation'  => Array(
      Array( 'type' => 'string', 'minimum' => 2, 'maximum' => 3, 'required' => true ),
    )
  ),

  'originalname' => Array(
    'displayname' => 'Nyelv neve a saját nyelvén',
    'type'        => 'inputText',
    'validation'  => Array(
      Array( 'type' => 'required' )
    )
  ),

  'name_stringid' => Array(
    'displayname' => 'Nyelv neve',
    'type'        => 'inputTextMultiLanguage',
    'languages'   => $l->getLov('languages'),
    'value'       => 0,
    'validation'  => Array(
    )
  ),
  
  'weight' => Array(
    'displayname' => 'Súlyozás',
    'type'        => 'inputText',
    'value'       => 100,
    'validation'  => Array(
      Array( 'type' => 'number', 'real' => 0, 'required' => true ),
    )
  ),

);

$listconfig = Array(

  'table'     => 'languages l, strings s',
  'where'     => '
    l.name_stringid = s.translationof AND
    s.language = "' . \Springboard\Language::get() . '"
  ',
  'modify'    => 'l.id',
  'delete'    => 'l.id',
  'order'     => Array( 'l.weight' ),
  
  'fields' => Array(

    Array(
      'field' => 'l.id',
      'displayname' => 'ID',
    ),
    Array(
      'field' => 's.value',
      'displayname' => 'Nyelv',
    ),

  ),

);
