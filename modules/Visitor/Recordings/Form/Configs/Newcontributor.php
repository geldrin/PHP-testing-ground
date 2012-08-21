<?php
$organizationid = $this->controller->organization['id'];
$config = array(
  
  'action' => array(
    'type'  => 'inputHidden',
    'value' => 'submitnewcontributor'
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
    'legend' => $l('recordings', 'newcontributor_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('recordings', 'newcontributor_subtitle') . '</span>',
  ),
  
  'roleid' => array(
    'displayname' => $l('recordings_contributor_role'),
    'type'        => 'selectDynamic',
    'sql'         => "
      SELECT r.id, s.value AS rolename
      FROM roles AS r, strings AS s
      WHERE
        r.organizationid  = '$organizationid' AND
        r.ispersonrelated = '1' AND
        s.translationof   = r.name_stringid AND
        s.language        = '" . \Springboard\Language::get() . "'
      ORDER BY r.weight
    ",
  ),
  
  'nameprefix' => array(
    'displayname' => $l('users', 'nameprefix'),
    'type'        => 'select',
    'values'      => array('' => $l('users', 'nonameprefix') ) + $l->getLov('title'),
    'validation'  => array(
      array( 'type' => 'required' )
    ),
  ),
  
  'namefirst' => array(
    'displayname' => $l('users', 'firstname'),
    'type'        => 'inputText',
    'validation'  => array(
      array( 'type' => 'required' ),
    ),
  ),
  
  'namelast' => array(
    'displayname' => $l('users', 'lastname'),
    'type'        => 'inputText',
    'validation'  => array(
      array( 'type' => 'required' ),
    ),
  ),
  
  'nameformat' => array(
    'displayname' => $l('users', 'nameformat'),
    'type'        => 'select',
    'values'      => array(
      'straight' => $l('users', 'nameformatstraight'),
      'reverse'  => $l('users', 'nameformatreverse'),
    ),
    'value'       => $language == 'en' ? 'reverse' : 'straight',
    'validation'  => array(
      array( 'type' => 'required' ),
    ),
  ),
  
);
