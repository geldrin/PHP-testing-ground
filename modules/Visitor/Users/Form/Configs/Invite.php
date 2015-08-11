<?php
$tinymceconfig = $l->getLov('tinymcevisitor') + array(
  'content_css' =>
    $this->controller->toSmarty['STATIC_URI'] .
    'css/style_tinymce_content' . $this->bootstrap->config['version'] . '.css,' .
    '/contents/layoutwysywygcss?' . $this->bootstrap->config['version']
  ,
  'init_instance_callback' => 'tinyMCEInstanceInit',
);

$smarty = $this->bootstrap->getSmarty();
include_once( $this->bootstrap->config['libpath'] . 'clonefish/constants.php');
include_once( \SMARTY_DIR . 'plugins/modifier.date_format.php' );
$this->bootstrap->includeTemplatePlugin('jsonescape');
$language  = \Springboard\Language::get();
$userModel = $this->bootstrap->getModel('users');
$templates = $userModel->getInviteTemplates( $this->controller->organization['id'] );

$invitetemplates = $l->getLov('invite_templates');
foreach( $templates as $template ) {

  $prefix = mb_substr( trim( strip_tags( $template['prefix'] ) ), 0, 30 );
  $invitetemplates[ $template['id'] ] =
    smarty_modifier_date_format( $template['timestamp'], $l('', 'smarty_dateformat_longer') ) .
    ' | ' . $prefix . '...'
  ;

}

$config    = array(
  
  'action' => array(
    'type'  => 'inputHidden',
    'value' => 'submitinvite'
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
    'value'       => 'single',
    'itemlayout'  => $this->radioitemlayout,
  ),

  'email' => Array(
    'displayname' => $l('users', 'email'),
    'type'        => 'inputText',
    'validation'  => Array(
      Array(
        'type'      => 'string',
        'regexp'    => CF_EMAIL,
        'help'      => $l('users', 'emailhelp'),
        'anddepend' => array(
          array(
            'js'  => '<FORM.usertype> == "single"',
            'php' => '<FORM.usertype> == "single"',
          ),
        ),
      ),
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
        'anddepend'        => array(
          array(
            'js'  => '<FORM.usertype> == "multiple"',
            'php' => '<FORM.usertype> == "multiple"',
          ),
        ),
      )
    )
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
      array(
        'type'      => 'required',
        'anddepend' => array(
          array(
            'js'  => '<FORM.usertype> == "multiple"',
            'php' => '<FORM.usertype> == "multiple"',
          ),
        ),
      ),
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
    'validation'  => array(
      array(
        'type'      => 'required',
        'anddepend' => array(
          array(
            'js'  => '<FORM.usertype> == "multiple"',
            'php' => '<FORM.usertype> == "multiple"',
          ),
        ),
      ),
    ),
  ),
);

include( $this->bootstrap->config['modulepath'] . 'Visitor/Form/Configs/Timestampdisabledafter.php');

$config['invitationvaliduntil']                = $config['timestampdisabledafter'];
$config['invitationvaliduntil']['displayname'] = $l('users', 'invitationvaliduntil');
$config['invitationvaliduntil']['postfix']     = str_replace(
  'class="timestampdisabledafter"',
  'class="invitationvaliduntil"',
  $config['invitationvaliduntil']['postfix']
);

$config = $config + array(
  'fs_permission' => array(
    'type'   => 'fieldset',
    'legend' => $l('users', 'invite_permission'),
    'prefix' => '<span class="legendsubtitle"></span>',
  ),
  
  'permissions[]' => array(
    'displayname' => $l('users', 'permissions'),
    'type'        => 'inputCheckboxDynamic',
    'itemlayout'  => $this->checkboxitemlayout,
    'values'      => $l->getLov('permissions'),
    'validation' => array(
    ),
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
    'value'       => 'nocontent',
    'itemlayout'  => $this->radioitemlayout,
  ),

  'recordingid' => array(
    'type'        => 'inputText',
    'displayname' => $l('users', 'invite_recording'),
    'rowlayout'   => '
      <tr>
        <td class="labelcolumn elementcolumn" colspan="2">
          <div class="labelwrap">
            <label for="%id%_search">%displayname%</label>
          </div>
          <input type="hidden" name="%id%" id="%id%" value=""/>
          %prefix%
          <input type="text" name="%id%_search" id="%id%_search" data-searchurl="' . $language . '/recordings/search"/>
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
    'validation'  => array(
      array(
        'type'      => 'required',
        'help'      => $l('users', 'invite_recording_help'),
        'anddepend' => array(
          array(
            'js'  => '<FORM.contenttype> == "recordingid"',
            'php' => '<FORM.contenttype> == "recordingid"',
          ),
        ),
      ),
    ),
  ),

  'livefeedid' => array(
    'type'        => 'inputText',
    'displayname' => $l('users', 'invite_livefeed'),
    'rowlayout'   => '
      <tr>
        <td class="labelcolumn elementcolumn" colspan="2">
          <div class="labelwrap">
            <label for="%id%_search">%displayname%</label>
          </div>
          <input type="hidden" name="%id%" id="%id%" value=""/>
          %prefix%
          <input type="text" name="%id%_search" id="%id%_search" data-searchurl="' . $language . '/live/search"/>
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
    'validation'  => array(
      array(
        'type'      => 'required',
        'help'      => $l('users', 'invite_livefeed_help'),
        'anddepend' => array(
          array(
            'js'  => '<FORM.contenttype> == "livefeedid"',
            'php' => '<FORM.contenttype> == "livefeedid"',
          ),
        ),
      ),
    ),
  ),

  'channelid' => array(
    'type'        => 'inputText',
    'displayname' => $l('users', 'invite_channel'),
    'rowlayout'   => '
      <tr>
        <td class="labelcolumn elementcolumn" colspan="2">
          <div class="labelwrap">
            <label for="%id%_search">%displayname%</label>
          </div>
          <input type="hidden" name="%id%" id="%id%" value=""/>
          %prefix%
          <input type="text" name="%id%_search" id="%id%_search" data-searchurl="' . $language . '/channels/search"/>
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
    'validation'  => array(
      array(
        'type'      => 'required',
        'help'      => $l('users', 'invite_channel_help'),
        'anddepend' => array(
          array(
            'js'  => '<FORM.contenttype> == "channelid"',
            'php' => '<FORM.contenttype> == "channelid"',
          ),
        ),
      ),
    ),
  ),

  'fs_group' => array(
    'type'   => 'fieldset',
    'legend' => $l('users', 'invite_group'),
    'prefix' => '<span class="legendsubtitle"></span>',
  ),
  
  'departments[]' => array(
    'displayname' => $l('users', 'departments'),
    'type'        => 'inputCheckboxDynamic',
    'itemlayout'  => $this->checkboxitemlayout,
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
    'itemlayout'  => $this->checkboxitemlayout,
    'sql'         => "
      SELECT g.id, g.name
      FROM groups AS g
      WHERE
        organizationid = '" . $this->controller->organization['id'] . "' AND
        (
          source <> 'directory' OR
          source IS NULL
        )
      ORDER BY g.name DESC
    ",
    'validation'  => array(
    ),
  ),

  'fs_template' => array(
    'type'   => 'fieldset',
    'legend' => $l('users', 'invite_template'),
    'prefix' => '<span class="legendsubtitle"></span>',
  ),

  'templateid' => array(
    'displayname' => $l('users', 'templateid'),
    'type'        => 'select',
    'html'        =>
      'data-templateurl="' . $language . '/users/getinvitationtemplate"' .
      ' data-defaulttemplatesubject=' . smarty_modifier_jsonescape( $l('users', 'templatesubject_default') ) .
      ' data-defaulttemplatetitle=' . smarty_modifier_jsonescape( $l('users', 'templatetitle_default') ) .
      ' data-defaulttemplateprefix=' . smarty_modifier_jsonescape( $l('users', 'templateprefix_default') ) .
      ' data-defaulttemplatepostfix=' . smarty_modifier_jsonescape( $l('users', 'templatepostfix_default') ),
    'postfix'     => '<div class="loading" style="display: none;"></div>',
    'values'      => $invitetemplates,
  ),

  'templatesubject' => array(
    'type'        => 'inputText',
    'displayname' => $l('users', 'templatesubject'),
    'value'       => $this->application->getParameter(
      'templatesubject',
      $l('users', 'templatesubject_default')
    ),
  ),

  'templatetitle' => array(
    'type'        => 'inputText',
    'displayname' => $l('users', 'templatetitle'),
    'value'       => $this->application->getParameter(
      'templatetitle',
      $l('users', 'templatetitle_default')
    ),
  ),

  'templateprefix' => array(
    'displayname' => $l('users', 'templateprefix'),
    'type'        => 'tinyMCE',
    'jspath'      => $this->controller->toSmarty['BASE_URI'] . 'js/tiny_mce/tiny_mce.js',
    'width'       => 450,
    'height'      => 200,
    'config'      => $tinymceconfig,
    'value'       => $this->application->getParameter(
      'templateprefix',
      $l('users', 'templateprefix_default')
    ),
    'validation'  => Array(
    ),
  ),
  
  'templatepostfix' => array(
    'displayname' => $l('users', 'templatepostfix'),
    'type'        => 'tinyMCE',
    'jspath'      => $this->controller->toSmarty['BASE_URI'] . 'js/tiny_mce/tiny_mce.js',
    'width'       => 450,
    'height'      => 200,
    'config'      => $tinymceconfig,
    'value'       => $this->application->getParameter(
      'templatepostfix',
      $l('users', 'templatepostfix_default')
    ),
    'validation'  => Array(
    )
  ),
  
);

$groupid = $this->application->getNumericParameter('groupid');
if ( $groupid ) {
  $config['groups[]']['value'] = array(
    $groupid,
  );
}

$departmentid = $this->application->getNumericParameter('departmentid');
if ( $departmentid ) {
  $config['departments[]']['value'] = array(
    $departmentid,
  );
}

$db              = $this->bootstrap->getAdoDB();
$departmentcount = $db->getOne("
  SELECT COUNT(*)
  FROM departments
  WHERE organizationid = '" . $this->controller->organization['id'] . "'
");
$groupcount      = $db->getOne("
  SELECT COUNT(*)
  FROM groups
  WHERE
    organizationid = '" . $this->controller->organization['id'] . "' AND
    (
      source <> 'directory' OR
      source IS NULL
    )
");

if ( !$departmentcount )
  unset( $config['departments[]'] );

if ( !$groupcount )
  unset( $config['groups[]'] );
