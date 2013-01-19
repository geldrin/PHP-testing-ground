<?php
$organizationid = $this->controller->organization['id'];
$language       = \Springboard\Language::get();
$config         = array(
  
  'action' => array(
    'type'     => 'inputHidden',
    'value'    => 'submitcreate',
    'readonly' => true,
  ),
  
  'recordingid' => array(
    'type'     => 'inputHidden',
    'value'    => $this->application->getNumericParameter('recordingid'),
    'readonly' => true,
  ),
  
  'orgid' => array(
    'type' => 'inputHidden',
  ),
  
  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('contributors', 'create_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('contributors', 'create_subtitle') . '</span>',
  ),
  
  'nameprefix' => array(
    'displayname' => $l('contributors', 'nameprefix'),
    'type'        => 'select',
    'values'      => array('' => $l('contributors', 'nonameprefix') ) + $l->getLov('title'),
    'validation'  => array(
      array( 'type' => 'required' )
    ),
  ),
  
  'namefirst' => array(
    'displayname' => $l('contributors', 'firstname'),
    'type'        => 'inputText',
    'validation'  => array(
      array( 'type' => 'required' ),
    ),
  ),
  
  'namelast' => array(
    'displayname' => $l('contributors', 'lastname'),
    'type'        => 'inputText',
    'validation'  => array(
      array( 'type' => 'required' ),
    ),
  ),
  
  'nameformat' => array(
    'displayname' => $l('contributors', 'nameformat'),
    'type'        => 'select',
    'values'      => array(
      'straight' => $l('contributors', 'nameformatstraight'),
      'reverse'  => $l('contributors', 'nameformatreverse'),
    ),
    'value'       => $language == 'en' ? 'reverse' : 'straight',
    'validation'  => array(
      array( 'type' => 'required' ),
    ),
  ),
  
  'contributorrole' => array(
    'type'        => 'selectDynamic',
    'displayname' => $l('contributors', 'contributorrole'),
    'sql'         => "
      SELECT r.id, s.value AS name
      FROM roles AS r, strings AS s
      WHERE
        r.organizationid  = '$organizationid' AND
        r.ispersonrelated <> '0' AND
        s.translationof   = r.name_stringid AND
        s.language        = '$language'
      ORDER BY weight, s.value
    ",
  ),
  
  'organization' => array(
    'type'        => 'inputText',
    'displayname' => $l('contributors', 'organization'),
  ),
  
  'selectedorganization' => array(
    'type'      => 'text',
    'value'     => $l('contributors', 'selectedorganization'),
    'rowlayout' => '
      <tr id="selectedorganizationrow">
        <td colspan="2">
          %prefix%
          %element%<span id="selectedorganization">' . htmlspecialchars( @$this->values['organization'], ENT_QUOTES, 'UTF-8', true ) . '</span>
          %postfix%
        </td>
      </tr>
    ',
    'prefix' => '
      <a id="clearorganization" href="#" class="ui-state-default ui-corner-all">
        <span class="ui-icon ui-icon-cancel"></span>
      </a>
    ',
  ),
  
);
