<?php

$config = Array(

  'action' => Array(
    'type'  => 'inputHidden',
    'value' => 'insert'
  ),

  'id' => Array(
    'type'  => 'inputHidden',
    'value' => '0'
  ),

  'shortname' => Array(
    'displayname' => 'Rövid azonosító',
    'type'        => 'inputText',
    'validation'  => Array(
      Array( 'type' => 'required' )
    )
  ),

  'title_stringid' => Array(
    'displayname' => 'Cím',
    'type'        => 'inputTextMultiLanguage',
    'languages'   => $l->getLov('languages'),
    'value'       => 0,
    'validation'  => Array(
    )
  ),

  'body_stringid' => Array(
    'displayname' => 'Szöveg',
    'type'        => 'tinymceMultiLanguage',
    'languages'   => $l->getLov('languages'),
    'value'       => 0,
    'width'       => 950,
    'config'      => $l->getLov('tinymceadmin'),
    'validation'  => Array(
    )
  ),

);

$listconfig = Array(

  'table'     => 'contents c, strings s',
  'where'     => 'c.title_stringid = s.translationof AND language = "hu"',
  'modify'    => 'c.id',
  'delete'    => 'c.id',

  'fields' => Array(

    Array(
      'field' => 'c.shortname',
      'displayname' => 'ID',
    ),
    Array(
      'field' => 's.value',
      'displayname' => 'Cím',
    ),

  ),

);
