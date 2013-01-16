<?php
$organizationid = $this->controller->organization['id'];
$config = array(
  
  'action' => array(
    'type'  => 'inputHidden',
    'value' => 'submitmodifycontributors'
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
    'prefix' => '<span class="legendsubtitle">' . $l('recordings', 'contributors_subtitle') . '</span>',
  ),
  
  'searchterm' => array(
    'type'        => 'inputText',
    'displayname' => $l('recordings', 'searchcontributor'),
    'rowlayout'   => '
      <tr>
        <td class="labelcolumn" colspan="2"><label for="%id%">%displayname%</label></td>
      </tr>
      <tr>
        <td class="elementcolumn" colspan="2">%prefix%%element%%postfix%%errordiv%</td>
      </tr>
    ',
  ),
  
  'contributorid' => array(
    'type' => 'inputHidden',
  ),
  
  'contributorrole' => array(
    'type'        => 'selectDynamic',
    'displayname' => $l('recordings', 'contributorrole'),
    'sql'         => "
      SELECT r.id, s.value AS name
      FROM roles AS r, strings AS s
      WHERE
        r.organizationid  = '$organizationid' AND
        r.ispersonrelated <> '0' AND
        s.translationof   = r.name_stringid AND
        s.language        = '" . \Springboard\Language::get() . "'
      ORDER BY weight, s.value
    ",
    'rowlayout' => '
      <tr id="contributorrolerow">
        <td>
          %prefix%
          <span id="contributorname"></span><label for="%id%">%displayname%</label>
          %element%%postfix%%errordiv%
        </td>
      </tr>
    ',
    'prefix' => '
      <a id="cancelcontributor" href="#" class="ui-state-default ui-corner-all">
        <span class="ui-icon ui-icon-cancel"></span>
      </a>
    ',
  ),
  
);
