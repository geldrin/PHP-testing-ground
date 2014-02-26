<?php

include_once( $this->bootstrap->config['libpath'] . 'clonefish/constants.php');

$config = array(
  
  'action' => array(
    'type'  => 'inputHidden',
    'value' => 'submitinvite'
  ),
  
  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('users', 'invite_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('users', 'invite_subtitle') . '</span>',
  ),
  
  'invitetype' => array(
    'type'        => 'inputCheckboxDynamic',
    'displayname' => $l('users', 'invite_invitetype'),
    'values'      => $l->getLov('invite_invitetype'),
  ),

  'fs_content' => array(
    'type'   => 'fieldset',
    'legend' => $l('users', 'invite_content'),
    'prefix' => '<span class="legendsubtitle"></span>',
  ),

  'contenttype' => array(
    'type'        => 'inputRadio',
    'displayname' => $l('users', 'invite_contenttype'),
    'values'      => $l->getLov('invite_contenttype'),
  ),

  'recordingid' => array(
    'type'        => 'inputText',
    'displayname' => $l('users', 'invite_recording'),
    'rowlayout'   => '
      <tr>
        <td class="labelcolumn" colspan="2"><label for="%id%_search">%displayname%</label></td>
      </tr>
      <tr>
        <td class="elementcolumn" colspan="2">
          <input type="hidden" name="%id%" id="%id%"/>
          %prefix%
          <input type="text" name="%id%_search" id="%id%_search"/>
          <div id="%id%_foundwrap" class="foundwrap">
            <a id="%id%_cancel" href="#" class="ui-state-default ui-corner-all cancel">
              <span class="ui-icon ui-icon-cancel"></span>
            </a>
            <div id="%id%_title" class="title"></div>
          </div>
          %postfix%%errordiv%
        </td>
      </tr>
    ',
  ),

  'livefeedid' => array(
    'type'        => 'inputText',
    'displayname' => $l('users', 'invite_livefeed'),
    'rowlayout'   => '
      <tr>
        <td class="labelcolumn" colspan="2"><label for="%id%_search">%displayname%</label></td>
      </tr>
      <tr>
        <td class="elementcolumn" colspan="2">
          <input type="hidden" name="%id%" id="%id%"/>
          %prefix%
          <input type="text" name="%id%_search" id="%id%_search"/>
          <div id="%id%_foundwrap" class="foundwrap">
            <a id="%id%_cancel" href="#" class="ui-state-default ui-corner-all cancel">
              <span class="ui-icon ui-icon-cancel"></span>
            </a>
            <div id="%id%_title" class="title"></div>
          </div>
          %postfix%%errordiv%
        </td>
      </tr>
    ',
  ),

  'channelid' => array(
    'type'        => 'inputText',
    'displayname' => $l('users', 'invite_channel'),
    'rowlayout'   => '
      <tr>
        <td class="labelcolumn" colspan="2"><label for="%id%_search">%displayname%</label></td>
      </tr>
      <tr>
        <td class="elementcolumn" colspan="2">
          <input type="hidden" name="%id%" id="%id%"/>
          %prefix%
          <input type="text" name="%id%_search" id="%id%_search"/>
          <div id="%id%_foundwrap" class="foundwrap">
            <a id="%id%_cancel" href="#" class="ui-state-default ui-corner-all cancel">
              <span class="ui-icon ui-icon-cancel"></span>
            </a>
            <div id="%id%_title" class="title"></div>
          </div>
          %postfix%%errordiv%
        </td>
      </tr>
    ',
  ),

  'fs_group' => array(
    'type'   => 'fieldset',
    'legend' => $l('users', 'invite_group'),
    'prefix' => '<span class="legendsubtitle"></span>',
  ),
  
  'departments[]' => array(
    'displayname' => $l('users', 'departments'),
    'type'        => 'inputCheckboxDynamic',
    'treeid'      => 'id',
    'treestart'   => '0',
    'treeparent'  => 'parentid',
    'sql'         => "
      SELECT id, name
      FROM departments
      WHERE
        organizationid = '" . $this->controller->organization['id'] . "' AND
        %s
      ORDER BY weight, name
    ",
    'validation' => array(
    ),
  ),
  
  'groups[]' => array(
    'displayname' => $l('users', 'groups'),
    'type'        => 'inputCheckboxDynamic',
    'sql'         => "
      SELECT g.id, g.name
      FROM groups AS g
      WHERE organizationid = '" . $this->controller->organization['id'] . "'
      ORDER BY g.name DESC
    ",
    'validation'  => array(
    ),
  ),
  
  'fs_permission' => array(
    'type'   => 'fieldset',
    'legend' => $l('users', 'invite_permission'),
    'prefix' => '<span class="legendsubtitle"></span>',
  ),
  
  'permissions[]' => array(
    'displayname' => $l('users', 'permissions'),
    'type'        => 'inputCheckboxDynamic',
    'values'      => $l->getLov('permissions'),
    'validation' => array(
    ),
  ),
  
  'fs_user' => array(
    'type'   => 'fieldset',
    'legend' => $l('users', 'invite_user'),
    'prefix' => '<span class="legendsubtitle"></span>',
  ),

  'usertype' => array(
    'type'        => 'inputRadio',
    'displayname' => $l('users', 'invite_usertype'),
    'values'      => $l->getLov('invite_usertype'),
  ),

  'email' => Array(
    'displayname' => $l('users', 'email'),
    'type'        => 'inputText',
    'validation'  => Array(
      Array(
        'type'   => 'string',
        'regexp' => CF_EMAIL,
        'help'   => $l('users', 'emailhelp')
      ),
    ),
  ),
  
  'encoding' => Array(
    'displayname' => $l('users', 'encoding'),
    'type'        => 'select',
    'values'      => array(
      'Windows-1252' => 'Windows Central European (Windows-1252)',
      'ISO-8859-2'   => 'ASCII Central European (ISO-8859-2)',
      'UTF-16LE'     => 'UTF16LE',
      'UTF-8'        => 'UTF-8',
      'ASCII'        => 'ASCII',
    ),
    'validation' => array(
      array('type' => 'required'),
    ),
  ),
  
  'delimeter' => Array(
    'displayname' => $l('users', 'delimeter'),
    'type'        => 'select',
    'values'      => array(
      ';'   => ';',
      ','   => ',',
      'tab' => 'tab',
    ),
    'value'       => ';',
    'validation' => array(
      array('type' => 'required'),
    ),
  ),
  
  'invitefile' => Array(
    'displayname' => $l('users', 'invitefile'),
    'type'        => 'inputFile',
    'validation'  => Array(
      array(
        'type'             => 'file',
        'required'         => true,
        'help'             => $l('users', 'invitefile_help'),
        'imagecreatecheck' => false,
        'extensions'       => Array('csv', 'txt',),
      )
    )
  ),
  
);

include( $this->bootstrap->config['modulepath'] . 'Visitor/Form/Configs/Timestampdisabledafter.php');

$db              = $this->bootstrap->getAdoDB();
$departmentcount = $db->getOne("
  SELECT COUNT(*)
  FROM departments
  WHERE organizationid = '" . $this->controller->organization['id'] . "'
");
$groupcount      = $db->getOne("
  SELECT COUNT(*)
  FROM groups
  WHERE organizationid = '" . $this->controller->organization['id'] . "'
");

if ( !$departmentcount )
  unset( $config['departments[]'] );

if ( !$groupcount )
  unset( $config['groups[]'] );
