<?php
$user           = $this->bootstrap->getSession('user');
$organizationid = $this->controller->organization['id'];

$config = array(

  'action' => array(
    'type'  => 'inputHidden',
    'value' => 'submitcreate'
  ),
  
  'parent' => array(
    'type'  => 'inputHidden',
    'value' => $this->application->getNumericParameter('parent'),
  ),

  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('channels', 'create_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('channels', 'create_subtitle') . '</span>',
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
    'sql'         => "
      SELECT ct.id, s.value
      FROM channel_types AS ct, strings AS s
      WHERE
        s.translationof = ct.name_stringid AND
        s.language      = '" . \Springboard\Language::get() . "' AND
        ct.isfavorite   = 0 AND
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
  /*
  'parentid' => Array(
    'displayname' => $l('channels', 'parentid'),
    'type'        => 'selectDynamic',
    'values'      => Array( 0 => $l('channels', 'noparent') ),
    'sql'         => "
      SELECT id, title
      FROM channels
      WHERE 
        organizationid = '" . $organizationid . "' AND
        %s
    ",
    'treeid'      => 'id',
    'treeparent'  => 'parentid',
    'treestart'   => '0',
  ),
  */
  
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
    'prefix'      => '<div id="groupscontainer">',
    'postfix'     => '</div>',
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
  
);

if (
     $this->parentchannelModel and $this->parentchannelModel->id and
     !$this->parentchannelModel->row['ispublic']
   ) {
  
  $config['ispublic']['html']    = 'disabled="disabled"';
  $config['ispublic']['postfix'] = $l('channels', 'ispublic_disabled');
  
}
