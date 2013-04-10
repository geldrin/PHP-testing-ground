<?php

$organizationid = $this->controller->organization['id'];
$config = Array(
  
  'action' => Array(
    'type'  => 'inputHidden',
    'value' => 'submitinvite'
  ),
  
  'id' => Array(
    'type'     => 'inputHidden',
    'value'    => $this->application->getNumericParameter('id'),
    'readonly' => true,
  ),
  
  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('groups', 'invite_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('groups', 'create_subtitle') . '</span>',
  ),
  
  'users' => array(
    'type'        => 'selectDynamic',
    'displayname' => $l('groups', 'users'),
    'html'        => 'multiple="multiple"', // TODO nameformat
    'sql'         => "
      SELECT u.id, u.nickname
      FROM users AS u
      WHERE
        u.organizationid = '$organizationid' AND
        NOT EXISTS (
          SELECT *
          FROM groups_members AS gm
          WHERE
            gm.userid  = u.id AND
            gm.groupid = '" . $this->groupModel->id . "'
        )
      ORDER BY u.nickname
    ",
  ),
  
  'email' => array(
    'type'        => 'inputText',
    'displayname' => $l('groups', 'email'),
  ),
  
);
