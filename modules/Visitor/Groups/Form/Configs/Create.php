<?php
$depend = Array(
  Array(
    'js'  => '<FORM.source> == "directory"',
    'php' => '<FORM.source> == "directory"',
  ),
);

$organizationid = $this->controller->organization['id'];
$config = Array(

  'action' => Array(
    'type'  => 'inputHidden',
    'value' => 'submitcreate'
  ),

  'organizationid' => Array(
    'type'     => 'inputHidden',
    'value'    => $organizationid,
    'readonly' => true,
  ),

  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('groups', 'create_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('groups', 'create_subtitle') . '</span>',
  ),

  'name' => Array(
    'displayname' => $l('groups', 'name'),
    'type'        => 'inputText',
    'validation'  => Array(
      array('type' => 'required'),
    )
  ),

);

$user = $this->bootstrap->getSession('user');
if ( $user['isadmin'] or $user['isclientadmin'] ) {
  $config = array_merge( $config, Array(

    'source' => Array(
      'displayname' => $l('groups', 'source'),
      'type'        => 'select',
      'values'      => $l->getLov('groups_source'),
      'validation'  => Array(
        array('type' => 'required'),
      ),
    ),

    'organizationdirectoryid' => Array(
      'displayname' => $l('groups', 'organizationdirectoryid'),
      'type'        => 'selectDynamic',
      'values' => array(
        '' => '',
      ),
      'sql'         => "
        SELECT id, CONCAT(name, ' - ', type)
        FROM organizations_directories
        WHERE
          organizationid = '$organizationid' AND
          disabled       = 0
      ",
      'validation'  => array(
        array(
          'type'      => 'string',
          'required'  => true,
          'minimum'   => 1,
          'help'      => $l('groups', 'organizationdirectoryidhelp'),
          'anddepend' => $depend,
        ),
      ),
    ),

    'organizationdirectoryldapdn' => Array(
      'displayname' => $l('groups', 'organizationdirectoryldapdn'),
      'type'        => 'inputText',
      'validation'  => Array(
        array(
          'type' => 'required',
          'anddepend' => $depend,
        ),
      )
    ),
  ));

}
