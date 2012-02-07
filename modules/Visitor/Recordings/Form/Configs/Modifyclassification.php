<?php

$config = array(
  
  'action' => array(
    'type'  => 'inputHidden',
    'value' => 'submitmodifyclassification'
  ),
  
  'id' => array(
    'type'  => 'inputHidden',
    'value' => $this->application->getNumericParameter('id'),
  ),
  
  'forward' => array(
    'type'  => 'inputHidden',
    'value' => $this->application->getParameter('forward'),
  ),
  
  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('recordings', 'classification_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('recordings', 'classification_subtitle') . '</span>',
  ),
  
  'categories[]' => array( // TODO per organization
    'type'        => 'inputCheckboxDynamic',
    'displayname' => $l('recordings', 'categories'),
    'sql'         => "
      SELECT
        c.id, s.value as name
      FROM 
        categories AS c, strings s 
      WHERE
        c.name_stringid = s.translationof AND 
        s.language = '" . \Springboard\Language::get() . "' AND
        %s
      ORDER BY c.weight, name
    ",
    'treeid'      => 'id',
    'treestart'   => '0',
    'treeparent'  => 'parentid',
    'valuesql'    => "
      SELECT categoryid
      FROM recordings_categories
      WHERE recordingid = '" . $this->application->getNumericParameter('id') . "'
    ",
    'validation' => Array(
      Array( 'type' => 'required' )
    ),
  ),
  
  'genres[]' => array( // TODO per organization
    'type'        => 'inputCheckboxDynamic',
    'displayname' => $l('recordings', 'genres'),
    'sql'         => "
      SELECT
        g.id, s.value as name
      FROM 
        genres AS g, strings s 
      WHERE
        g.name_stringid = s.translationof AND 
        s.language = '" . \Springboard\Language::get() . "' AND
        %s
      ORDER BY g.weight, name
    ",
    'treeid'      => 'id',
    'treestart'   => '0',
    'treeparent'  => 'parentid',
    'valuesql'    => "
      SELECT
        rg.genreid
      FROM
        recordings_genres AS rg,
        genres AS g
      WHERE
        rg.recordingid = '" . $this->application->getNumericParameter('id') . "' AND
        g.id = rg.genreid
    ",
    'validation' => Array(
      Array( 'type' => 'required' )
    ),
  ),
  
  'keywords' => array(
    'type'        => 'inputText',
    'displayname' => $l('recordings', 'keywords'),
    'postfix'     => '<div class="smallinfo">' . $l('recordings', 'keywordshelp') . '</div>',
  ),
  
);
