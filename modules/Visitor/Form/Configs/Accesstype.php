<?php
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
$accesstypes = $l->getLov('accesstype');
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
  'validation'  => array(
    array(
      'type' => 'required',
      'help' => $l('recordings', 'groupshelp'),
      'anddepend' => Array(
        Array(
          'js'  => '<FORM.accesstype> == "departmentsorgroups"',
          'php' => '<FORM.accesstype> == "departmentsorgroups"',
        ),
        Array( // valami clonefish bug van, a !<FORM.groups> nemmukodik
          'js'  => '!clonefishGetFieldValue( "recordings_modifysharing", "groups", "inputCheckboxDynamic")',
          'php' => '!count( <FORM.groups[]> )',
        ),
      ),
    ),
  ),
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
          'js'  => '<FORM.accesstype> == "departmentsorgroups"',
          'php' => '<FORM.accesstype> == "departmentsorgroups"',
        ),
        Array(
          'js'  => '!clonefishGetFieldValue( "recordings_modifysharing", "departments", "inputCheckboxDynamic")',
          'php' => '!count( <FORM.departments[]> )',
        ),
      ),
    ),
  ),
);

if ( $this->controller->organization['tokenverifyurl'] )
  $config['istokenrequired'] = array(
    'displayname' => $l('recordings', 'istokenrequired'),
    'itemlayout'  => $this->radioitemlayout,
    'type'        => 'inputRadio',
    'value'       => '1',
    'values'      => $l->getLov('istokenrequired'),
    'rowlayout'   => '
      <tr %errorstyle%>
        <td class="labelcolumn" style="width: 170px;">
          <label for="%id%">%displayname%</label>
        </td>
        <td class="elementcolumn">%prefix%%element%%postfix%%errordiv%</td>
      </tr>
    ',
  );
