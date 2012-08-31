<?php
$user           = $this->bootstrap->getSession('user');
$organizationid = $this->controller->organization['id'];

$config = array(

  'action' => array(
    'type'  => 'inputHidden',
    'value' => 'submitcreatefeed'
  ),
  
  'id' => array(
    'type'  => 'inputHidden',
    'value' => $this->application->getNumericParameter('id'),
  ),
  
  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('live', 'createfeed_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('live', 'createfeed_subtitle') . '</span>',
  ),
  
  'name' => array(
    'displayname' => $l('live', 'feedname'),
    'type'        => 'inputText',
    'validation'  => array(
      array(
        'required' => true,
        'type'     => 'string',
        'minimum'  => 2,
        'maximum'  => 512,
      ),
    ),
  ),

  'isexternal' => array(
    'displayname' => $l('live', 'external'),
    'type'        => 'inputRadio',
    'values'      => $l->getLov('yesno'),
    'value'       => 0,
  ),
  
  'numberofstreams' => array(
    'displayname' => $l('live', 'numberofstreams'),
    'type'        => 'inputRadio',
    'values'      => $l->getLov('numberofstreams'),
    'value'       => 1,
    'divider'     => '<br/>',
    'divide'      => 1,
  ),
  
  'accesstype' => array(
    'displayname' => $l('recordings', 'accesstype'),
    'itemlayout'  => '%radio% %label% <br/>',
    'type'        => 'inputRadio',
    'value'       => 'public',
    'values'      => $l->getLov('accesstype'),
  ),
  
  'organizations[]' => array(
    'displayname' => $l('recordings', 'organizations'),
    'type'        => 'inputCheckboxDynamic',
    'html'        => '',
    'sql'         => "
      SELECT
        o.id AS `o.id`, CONCAT( sname.value, ' (', snameshort.value,  ')' ) AS name
      FROM
        organizations AS o,
        strings AS sname,
        strings AS snameshort
      WHERE
        sname.translationof      = o.name_stringid AND
        sname.language           = '" . \Springboard\Language::get() . "' AND
        snameshort.translationof = o.nameshort_stringid AND
        snameshort.language      = '" . \Springboard\Language::get() . "' AND
        %s
      ORDER BY sname.value
    ",
    'prefix'      => '<div class="formoverflowframe" id="organizationscontainer">',
    'postfix'     => '</div>',
    'itemlayout'  =>
      '<div class="cbxdynamiclevel%level%">'.
        '<span class="indent">%indent%</span> %checkbox% '.
        '<span title="%valuehtmlescape%">%label%</span>'.
      '</div>' . "\r\n"
    ,
    'treeid'      => 'o.id',
    'treestart'   => $organizationid,
    'treestartinclusive' => true,
    'treeparent'  => 'parentid',
  ),
  
  'groups[]' => array(
    'displayname' => $l('recordings', 'groups'),
    'type'        => 'inputCheckboxDynamic',
    'sql'         => "
      SELECT g.id, g.name
      FROM
        groups AS g,
        groups_members AS gm
      WHERE
        gm.userid = '" . $user['id'] . "' AND
        g.id      = gm.groupid
      ORDER BY g.name DESC",
    'validation'  => array(
      array(
        'type' => 'required',
        'help' => $l('recordings', 'groupshelp'),
        'anddepend' => Array(
          Array(
            'js'  => '<FORM.accesstype> == "groups"',
            'php' => '<FORM.accesstype> == "groups"',
          )
        ),
      ),
    ),
  ),
  
  'defaultmoderation' => array(
    'displayname' => $l('live', 'defaultmoderation'),
    'type'        => 'select',
    'values'      => $l->getLov('defaultmoderation'),
    'value'       => 0,
  ),
  
);
