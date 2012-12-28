<?php
$language       = \Springboard\Language::get();
$organizationid = $this->controller->organization['id'];
$config         = array(
  
  'q' => array(
    'displayname' => $l('search', 'q'),
    'type'        => 'inputText',
    'html'        => 'class="clearonclick" data-origval="' . $l('search', 'q') . '"',
    'value'       => $l('search', 'q'),
    'validation'  => array(
      array(
        'type'     => 'string',
        'required' => false,
        'minimum'  => 3,
      ),
    ),
  ),
  
  'wholeword' => array(
    'displayname' => $l('search', 'wholeword'),
    'type'        => 'inputRadio',
    'values'      => $l->getLov('search_wholeword'),
    'value'       => 0,
    'validation'  => array(
    ),
  ),
  
  'contributorname' => array(
    'displayname' => $l('search', 'contributorname'),
    'type'        => 'inputText',
    'html'        => 'class="clearonclick" data-origval="' . $l('search', 'contributorname') . '"',
    'value'       => $l('search', 'contributorname'),
    'validation'  => array(
    ),
  ),
  
  'contributorjob' => array(
    'displayname' => $l('search', 'contributorjob'),
    'type'        => 'inputText',
    'html'        => 'class="clearonclick" data-origval="' . $l('search', 'contributorjob') . '"',
    'value'       => $l('search', 'contributorjob'),
    'validation'  => array(
    ),
  ),
  
  'createdatefrom' => array(
    'displayname' => $l('search', 'createdatefrom'),
    'type'        => 'inputText',
    'html'        => 'class="clearonclick datepicker" data-origval="' . $l('search', 'createdatefrom') . '"',
    'value'       => $l('search', 'createdatefrom'),
    'validation'  => array(
    ),
  ),
  
  'createdateto' => array(
    'displayname' => $l('search', 'createdateto'),
    'type'        => 'inputText',
    'html'        => 'class="clearonclick datepicker" data-origval="' . $l('search', 'createdateto') . '"',
    'value'       => $l('search', 'createdateto'),
    'validation'  => array(
    ),
  ),
  
  'uploaddatefrom' => array(
    'displayname' => $l('search', 'uploaddatefrom'),
    'type'        => 'inputText',
    'html'        => 'class="clearonclick datepicker" data-origval="' . $l('search', 'uploaddatefrom') . '"',
    'value'       => $l('search', 'uploaddatefrom'),
    'validation'  => array(
    ),
  ),
  
  'uploaddateto' => array(
    'displayname' => $l('search', 'uploaddateto'),
    'type'        => 'inputText',
    'html'        => 'class="clearonclick datepicker" data-origval="' . $l('search', 'uploaddateto') . '"',
    'value'       => $l('search', 'uploaddateto'),
    'validation'  => array(
    ),
  ),
  
  'languages' => array(
    'type'        => 'selectDynamic',
    'displayname' => $l('search', 'languages'),
    'values'      => array('' => $l('search', 'languages') ),
    'sql'         => "
      SELECT DISTINCT l.id, s.value
      FROM
        languages AS l,
        strings AS s,
        recordings AS r
      WHERE
        r.languageid     = l.id AND
        l.name_stringid  = s.translationof AND
        s.language       = '" . $language . "' AND
        r.status         = 'onstorage' AND
        r.organizationid = '" . $organizationid . "'
      ORDER BY l.weight
    ",
  ),
  
  'department' => array(
    'displayname' => $l('search', 'department'),
    'type'        => 'selectDynamic',
    'values'      => array( $l('search', 'department') ),
    'treeid'      => 'id',
    'treestart'   => '0',
    'treeparent'  => 'parentid',
    'sql'         => "
      SELECT id, name
      FROM departments
      WHERE
        organizationid = '$organizationid' AND
        %s
      ORDER BY weight, name
    ",
    'validation'  => array(
    ),
  ),
  
  'category' => array(
    'displayname' => $l('search', 'category'),
    'type'        => 'selectDynamic',
    'values'      => array( $l('search', 'category') ),
    'treeid'      => 'id',
    'treestart'   => '0',
    'treeparent'  => 'parentid',
    'sql'         => "
      SELECT c.id, s.value AS name
      FROM
        categories AS c,
        strings AS s
      WHERE
        c.organizationid = '$organizationid' AND
        c.name_stringid  = s.translationof AND
        s.language       = '$language' AND
        %s
      ORDER BY c.weight, name
    ",
    'validation'  => array(
    ),
  ),
  
);
