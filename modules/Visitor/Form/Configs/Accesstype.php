<?php
if ( !isset( $user ) )
  $user = $this->bootstrap->getSession('user');

if ( !isset( $groupModel ) )
  $groupModel = $this->bootstrap->getModel('groups');

if ( !isset( $departmentModel ) ) {
  
  $departmentModel = $this->bootstrap->getModel('departments');
  $departmentModel->addFilter('organizationid', $this->controller->organization['id'] );
  
}

$accesstypes = $l->getLov('accesstype');
if ( $groupModel->getGroupCount( $user ) == 0 )
  unset( $accesstypes['groups'] );

if ( $departmentModel->getCount() == 0 )
  unset( $accesstypes['departments'] );

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
      g.userid         = '" . $user['id'] . "' AND
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
