<?php

include_once( $this->bootstrap->config['libpath'] . 'clonefish/constants.php');
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
  
  'userid' => Array(
    'type'  => 'inputHidden',
  ),
  
  'permissions' => Array(
    'type'  => 'inputHidden',
    'value' => '',
  ),
  
  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('groups', 'invite_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('groups', 'create_subtitle') . '</span>',
  ),
  
  'email' => array(
    'type'        => 'inputText',
    'displayname' => $l('groups', 'email'),
    'postfix'     => '<a href="#" title="' . $l('', 'delete') . '" class="delete ui-icon ui-icon-circle-close"></a>',
    'rowlayout'   => '
      <tr %errorstyle%>
        <td class="labelcolumn">
          <label for="%id%">%displayname%</label>
        </td>
        <td class="elementcolumn">
          <div id="emailwrap">%prefix%%element%%postfix%</div>
          %errordiv%
        </td>
      </tr>
    ',
    'validation'  => array(
      array(
        'type'      => 'string',
        'regexp'    => CF_EMAIL,
        'help'      => $l('users', 'emailhelp'),
        'anddepend' => array(
          array(
            'type' => 'custom',
            'php'  => '<FORM.userid> == ""',
            'js'   => '<FORM.userid> == ""',
          ),
        ),
      ),
    ),
  ),
  
);
