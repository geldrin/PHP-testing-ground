<?php

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
        s.translationof   = ct.name_stringid AND
        s.language        = '" . \Springboard\Language::get() . "' AND
        ct.isfavorite     = 0 AND
        ct.organizationid = '" . $this->controller->organization['id'] . "' AND
        %s
      ORDER BY ct.weight
    ",
    'treeid'      => 'id',
    'treestart'   => '0', // TODO event channel types only
    'treeparent'  => 'ct.parentid',
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
    'type'        => 'inputText',
    'html'        =>
      'class="inputtext inputbackground clearonclick datetimepicker margin" ' .
      'data-dateyearrange="' . date('Y') . ':' . ( date('Y') + 1 ) . '"' .
      'data-datetimefrom="' . date('Y-m-d 00:00') . '"'
    ,
    'value'       => date('Y-m-d H:m'),
    'validation'  => array(
      array(
        'type'       => 'date',
        'format'     => 'YYYY-MM-DD hh:mm',
        'lesseqthan' => 'endtimestamp',
        'help'       => $l('live', 'starttimestamp_help'),
      )
    ),
  ),
  
  'endtimestamp' => array(
    'displayname' => $l('live', 'endtimestamp'),
    'type'        => 'inputText',
    'html'        =>
      'class="inputtext inputbackground clearonclick datetimepicker margin" ' .
      'data-dateyearrange="' . date('Y') . ':' . ( date('Y') + 1 ) . '"' .
      'data-datetimefrom="' . date('Y-m-d 00:00') . '"'
    ,
    'value'       => date('Y-m-d H:m', strtotime('+1 day')),
    'validation'  => array(
      array(
        'type'          => 'date',
        'format'        => 'YYYY-MM-DD hh:mm',
        'greatereqthan' => 'starttimestamp',
        'help'          => $l('live', 'endtimestamp_help'),
      )
    ),
  ),
);

if ( !isset( $user ) )
  $user = $this->bootstrap->getSession('user');

if ( !isset( $groupModel ) )
  $groupModel = $this->bootstrap->getModel('groups');

$groupsexist =
  $groupModel->getGroupCount( $user, $this->controller->organization['id'] )
;

if ( !isset( $departmentModel ) ) {
  
  $departmentModel = $this->bootstrap->getModel('departments');
  $departmentModel->addFilter('organizationid', $this->controller->organization['id'] );
  
}

$departmentsexist = $departmentModel->getCount();
$accesstypes = $l->getLov('liveaccesstype');
if ( !$groupsexist and !$departmentsexist )
  unset( $accesstypes['departmentsorgroups'] );

$config['accesstype'] = array(
  'displayname' => $l('recordings', 'accesstype'),
  'itemlayout'  => $this->radioitemlayout,
  'type'        => 'inputRadio',
  'value'       => 'public',
  'values'      => $accesstypes,
  'rowlayout'   => '
    <tr %errorstyle%>
      <td class="labelcolumn" style="width: 170px;">
        <label for="%id%">%displayname%</label>
      </td>
      <td class="elementcolumn">%prefix%%element%%postfix%%errordiv%</td>
    </tr>
  ',
);

$config['departments[]'] = array(
  'displayname' => $l('recordings', 'departments'),
  'type'        => 'inputCheckboxDynamic',
  'html'        => '',
  'sql'         => "
    SELECT id, name
    FROM departments
    WHERE %s AND organizationid = '" . $this->controller->organization['id'] . "'
    ORDER BY weight, name
  ",
  'prefix'      => '<div class="formoverflowframe" id="departmentscontainer">',
  'postfix'     =>
    '</div><div class="smallinfo">' .
      $l('recordings', 'accesstype_departments_postfix') .
    '</div>'
  ,
  'itemlayout'  => $this->checkboxitemlayout,
  'treeid'      => 'id',
  'treestart'   => 0,
  'treestartinclusive' => true,
  'treeparent'  => 'parentid',
);

$config['groups[]'] = array(
  'displayname' => $l('recordings', 'groups'),
  'prefix'      => '<div id="groupscontainer">',
  'postfix'     => '</div>',
  'type'        => 'inputCheckboxDynamic',
  'itemlayout'  => $this->checkboxitemlayout,
  'sql'         => "
    SELECT g.id, g.name
    FROM groups AS g
    WHERE
      g.organizationid = '" . $this->controller->organization['id'] . "'
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
);

if (
     $this->parentchannelModel and $this->parentchannelModel->id and
     !$this->parentchannelModel->row['ispublic']
   ) {
  
  $config['ispublic']['html']    = 'disabled="disabled"';
  $config['ispublic']['postfix'] = $l('live', 'ispublic_disabled');
  
}
