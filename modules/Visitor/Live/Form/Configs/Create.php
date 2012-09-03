<?php
$user           = $this->bootstrap->getSession('user');

$config = array(

  'action' => array(
    'type'  => 'inputHidden',
    'value' => 'submitcreate'
  ),
  
  'parent' => array(
    'type'  => 'inputHidden',
    'value' => $this->application->getNumericParameter('parent'),
  ),
  
  'isliveevent' => array(
    'type'     => 'inputHidden',
    'value'    => '1',
    'readonly' => true,
  ),
  
  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('live', 'create_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('live', 'create_subtitle') . '</span>',
  ),
  
  'title' => array(
    'displayname' => $l('live', 'title'),
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
    'displayname' => $l('live', 'subtitle'),
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
    'displayname' => $l('live', 'description'),
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
    'displayname' => $l('live', 'channelsubtype'),
    'type'        => 'selectDynamic',
    'sql'         => "
      SELECT ct.id, s.value
      FROM channel_types AS ct, strings AS s
      WHERE
        s.translationof = ct.name_stringid AND
        s.language = '" . \Springboard\Language::get() . "' AND
        ct.isfavorite = 0 AND
        %s
      ORDER BY ct.weight
    ",
    'treeid'      => 'id',
    'treestart'   => '0', // TODO event channel types only
    'treeparent'  => 'ct.parentid',
    'values' => array(0 => 'TODO'),
    'validation'  => array(
      /* TODO
      array(
        'type' => 'required',
        'help' => $l('live', 'channelsubtypehelp'),
      ),
      */
    ),
  ),
  
  'starttimestamp' => array(
    'displayname' => $l('live', 'starttimestamp'),
    'type'        => 'selectDate',
    'postfix'     => '<div class="datepicker"></div>',
    'format'      => '%Y-%M-%D',
    'yearfrom'    => date('Y') + 1,
    'value'       => date('Y-m-d'),
    'validation'  => array(
    ),
  ),
  
  'endtimestamp' => array(
    'displayname' => $l('live', 'endtimestamp'),
    'type'        => 'selectDate',
    'postfix'     => '<div class="datepicker"></div>',
    'format'      => '%Y-%M-%D',
    'yearfrom'    => date('Y') + 1,
    'value'       => date('Y-m-d', strtotime('+1 day')),
    'validation'  => array(
    ),
  ),
  
  'accesstype' => array(
    'displayname' => $l('recordings', 'accesstype'),
    'itemlayout'  => '%radio% %label% <br/>',
    'type'        => 'inputRadio',
    'value'       => 'public',
    'values'      => $l->getLov('accesstype'),
  ),
  
  'departments[]' => array(
    'displayname' => $l('recordings', 'departments'),
    'type'        => 'inputCheckboxDynamic',
    'html'        => '',
    'sql'         => "
      SELECT id, name
      FROM departments
      WHERE %s
      ORDER BY weight, name
    ",
    'prefix'      => '<div class="formoverflowframe" id="departmentscontainer">',
    'postfix'     => '</div>',
    'itemlayout'  =>
      '<div class="cbxdynamiclevel%level%">'.
        '<span class="indent">%indent%</span> %checkbox% '.
        '<span title="%valuehtmlescape%">%label%</span>'.
      '</div>' . "\r\n"
    ,
    'treeid'      => 'id',
    'treestart'   => $user['departmentid'],
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
  $config['ispublic']['postfix'] = $l('live', 'ispublic_disabled');
  
}
