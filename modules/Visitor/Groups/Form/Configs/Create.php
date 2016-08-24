<?php

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

  'name' => Array(
    'displayname' => $l('groups', 'name'),
    'type'        => 'inputText',
    'validation'  => Array(
      array(
        'type' => 'required',
      ),
    )
  ),

);

if (
     \Model\Userroles::userHasPrivilege(
       null,
       'groups_remotegroups',
       'or',
       'isadmin', 'isclientadmin'
     )
   ) {
  $depend = Array(
    Array(
      'js'  => '<FORM.source> == "directory"',
      'php' => '<FORM.source> == "directory"',
    ),
  );

  $config['name']['validation'][0]['anddepend'] = Array(
    Array(
      'js'  => '<FORM.source> != "directory"',
      'php' => '<FORM.source> != "directory"',
    ),
  );

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
        array(
          'type' => 'database',
          'anddepend' => $depend,
          'help' => $l('groups','organizationdirectoryldapdn_duplicate'),
          'sql'  =>  "
            SELECT count(*) as counter
            FROM groups
            WHERE
              organizationdirectoryldapdn = <FORM.organizationdirectoryldapdn> AND
              organizationid = '" . $this->controller->organization['id'] . "'
          ",
          'field' => 'counter',
          'value' => '0',
        ),
      )
    ),
  ));

}
