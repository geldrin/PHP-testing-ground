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

  'organizationid' => Array(
    'displayname' => 'Intézmény',
    'type'        => 'selectDynamic',
    'sql'         => "
      SELECT
        o.id, CONCAT( s.value, ' - ', o.id )
      FROM
        organizations AS o,
        strings AS s
      WHERE
        s.translationof = o.name_stringid AND
        s.language = 'hu' AND
        %s
      ORDER BY s.value
    ",
    'values' => array(
      '0' => '--- Nincs intézmény (globális) ---',
    ),
    'treeid'      => 'id',
    'treeparent'  => 'parentid',
    'treestart'   => '0',
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
    'type'        => 'tinymceMultiLanguage2',
    'languages'   => $l->getLov('languages'),
    'config'      => $l->getLov('tinymceadmin'),
    'width'       => 450,
    'height'      => 550,
    'value'       => 0,
    'validation'  => Array(
    )
  ),

);

$listconfig = Array(

  'table'     => '
    help_contents hc
    LEFT JOIN organizations AS o ON(
      o.id = hc.organizationid
    )
    LEFT JOIN strings AS s ON(
      hc.title_stringid = s.translationof AND
      s.language = "hu"
    )
  ',
  'modify'    => 'hc.id',
  'delete'    => 'hc.id',
  'order'     => array('hc.organizationid ASC', 's.value'),
  // 'order'     => array('s.value', 'hc.organizationid ASC',),
  'fields' => Array(
    Array(
      'field' => 'o.name',
      'displayname' => 'Intézmény',
      'phptrigger' => '"<VALUE>" ?: "-- Globális --"',
    ),
    Array(
      'field' => 'hc.shortname',
      'displayname' => 'ID',
    ),
    Array(
      'field' => 's.value',
      'displayname' => 'Cím',
    ),

  ),

);
