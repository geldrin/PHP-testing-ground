<?php
$organization = $this->bootstrap->getOrganization();
$organizationids = array_merge(
  array($organization->id ),
  $organization->children
);

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
  
  'email' => array(
    'displayname' => $l('users', 'email'),
    //'postfix'     => $l('users', 'emailpostfix'),
    'type'        => 'inputText',
    'validation'  => array(
      array( 'type' => 'required' ),
      array(
        'type' => 'database',
        'help' => $l('users','emailregisteredhelp'),
        'sql' => 
          "SELECT count(*) as counter
           FROM users " .
          "WHERE " .
            "email = <FORM.email> AND " .
            "organizationid IN('" . implode("', '", $organizationids ) . "')",
        'field' => 'counter',
        'value' => '0'
      )
    ),
  ),
  
  'permissions[]' => array(
    'displayname' => $l('users', 'permissions'),
    'type'        => 'inputCheckboxDynamic',
    'values'      => $l->getLov('permissions'),
    'validation' => array(
    ),
  ),
  
);
