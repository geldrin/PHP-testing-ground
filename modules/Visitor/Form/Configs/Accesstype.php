<?php
if ( !isset( $user ) )
  $user = $this->bootstrap->getSession('user');

if ( !isset( $userModel ) ) {
  
  $userModel = $this->bootstrap->getModel('users');
  $userModel->select( $user['id'] );
  
}

$accesstypes = $l->getLov('accesstype');
if ( $userModel->getGroupCount() == 0 )
  unset( $accesstypes['groups'] );

$config['accesstype'] = array(
  'displayname' => $l('recordings', 'accesstype'),
  'itemlayout'  => '%radio% %label% <br/>',
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
);

$config['groups[]'] = array(
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
);
