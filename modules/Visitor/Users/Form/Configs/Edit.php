<?php

$config = array(
  
  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('users', 'modify_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('users', 'modify_subtitle') . '</span>',
  ),
  
  'action' => array(
    'type'  => 'inputHidden',
    'value' => 'submitedit'
  ),
  
  'forward' => array(
    'type'  => 'inputHidden',
    'value' => $this->application->getParameter('forward'),
  ),
  
  'email' => array(
    'displayname' => $l('users', 'email'),
    'type'        => 'inputText',
    'html'        => 'disabled="disabled"',
  ),

  'nickname' => array(
    'displayname' => $l('users', 'username'),
    'type'        => 'inputText',
    'validation'  => array(
      array(
        'type'      => 'string',
        'required'  => true,
        'minimum'   => 4,
        'maximum'   => 20,
        'help'      => $l('users', 'usernamehelp'),
        'jsregexp'  => '/^[a-zA-ZáéíóúöüőűÁÉÍÓÚÖÜŐŰ0-9.-][a-zA-ZáéíóúöüőűÁÉÍÓÚÖÜŐŰ0-9 .-]{2,20}[a-zA-ZáéíóúöüőűÁÉÍÓÚÖÜŐŰ0-9.-]$/',
        'phpregexp' => '/^[a-zA-ZáéíóúöüőűÁÉÍÓÚÖÜŐŰ0-9.-][a-zA-ZáéíóúöüőűÁÉÍÓÚÖÜŐŰ0-9 .-]{2,20}[a-zA-ZáéíóúöüőűÁÉÍÓÚÖÜŐŰ0-9.-]$/ui'
      ),
      array(
        'type' => 'database',
        'help' => $l('users','usernameregistered'),
        'sql'  => "
          SELECT count(*) as counter
          FROM users
          WHERE
            nickname = <FORM.nickname> AND
            id <> " . $this->userModel->row['id'] . " AND
            organizationid = '" . $this->controller->organization['id'] . "'
        ",
        'field' => 'counter',
        'value' => '0'
      )
    ),
  ),
  
  'nameprefix' => array(
    'displayname' => $l('users', 'nameprefix'),
    'type'        => 'select',
    'values'      => array('' => $l('users', 'nonameprefix') ) + $l->getLov('title'),
    'validation'  => array(
    ),
  ),
  
  'namefirst' => array(
    'displayname' => $l('users', 'firstname'),
    'type'        => 'inputText',
    'validation'  => array(
      array( 'type' => 'required' ),
    ),
  ),
  
  'namelast' => array(
    'displayname' => $l('users', 'lastname'),
    'type'        => 'inputText',
    'validation'  => array(
      array( 'type' => 'required' ),
    ),
  ),
  
  'nameformat' => array(
    'displayname' => $l('users', 'nameformat'),
    'type'        => 'select',
    'values'      => array(
      'straight' => $l('users', 'nameformatstraight'),
      'reverse'  => $l('users', 'nameformatreverse'),
    ),
    'value'       => \Springboard\Language::get() == 'en' ? 'reverse' : 'straight',
    'validation'  => array(
      array( 'type' => 'required' ),
    ),
  ),
  
  'nameformat' => array(
    'displayname' => $l('users', 'nameformat'),
    'type'        => 'select',
    'values'      => array(
      'straight' => $l('users', 'nameformatstraight'),
      'reverse'  => $l('users', 'nameformatreverse'),
    ),
    'value'       => 'straight', // maybe default to a saner default based on browser language?
    'validation'  => array(
      array( 'type' => 'required' ),
    ),
  ),
  
  'password' => array(
    'displayname' => $l('users', 'newpassword'),
    'type'        => 'inputPassword',
    'validation' => array(
      array(
        'type' => 'string',
        'minimum' => 4,
        'maximum' => 512,
        'required' => false,
      )
    )
  ),
  
  'confirmpassword' => Array(
    'displayname' => $l('users', 'verifynewpassword'),
    'type'        => 'inputPassword',
    'validation' => Array(
      array(
        'type'   => 'string',
        'equals' => 'password',
      )
    )
  ),
  
  'permissions[]' => array(
    'displayname' => $l('users', 'permissions'),
    'type'        => 'inputCheckboxDynamic',
    'itemlayout'  => $this->checkboxitemlayout,
    'values'      => $l->getLov('permissions'),
    'validation' => array(
    ),
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
    'valuesql'    => "
      SELECT departmentid
      FROM users_departments
      WHERE userid = '" . $this->userModel->row['id'] . "'
    ",
    'validation' => array(
    ),
  ),
  
  'groups[]' => array(
    'displayname' => $l('users', 'groups'),
    'type'        => 'inputCheckboxDynamic',
    'validation'  => array(
    ),
  ),
  
);

include( $this->bootstrap->config['modulepath'] . 'Visitor/Form/Configs/Timestampdisabledafter.php');

$departmentModel = $this->bootstrap->getModel('departments');
$departmentModel->addFilter('organizationid', $this->controller->organization['id'] );

if ( $departmentModel->getCount() == 0 )
  unset( $config['departments[]'] );

$groups = $this->userModel->getGroups( $this->controller->organization['id'] );
if ( empty( $groups ) )
  unset( $config['groups[]'] );
else {
  $groupValues = array(); // ahol a user tag
  $groupAssoc  = array(); // groupid -> groupnev hashmap
  $groupHTML   = array(); // checkboxok
  $usedids     = array(); // a checkbox random id-k

  foreach( $groups as $group ) {
    // disabled az adott checkbox ha non-lokalis a group (activedirectorybol jon)
    if ( $group['source'] == 'directory' )
      $groupDisabled = 'disabled="disabled"';
    else {
      $this->localGroups[ $group['id'] ] = true;
      $groupDisabled = '';
    }

    // ha tagja a csoportnak akkor jeloljuk
    if ( $group['memberid'] ) {
      $groupValues[ $group['id'] ] = true;
      $groupChecked = 'checked="checked"';
    } else
      $groupChecked = '';

    // adjuk at clonefishnek a biztonsag kedveert, de manualisan generaljuk a htmlt
    $groupAssoc[ $group['id'] ] = $group['name'];
    while(true) {
      $randomid = mt_rand( 10000, 20000 ); // az intervallum nem fedi a CF-t
      if ( isset( $usedids[ $randomid ] ) )
        continue;

      $usedids[ $randomid ] = true;
      break;
    }

    $checkbox = strtr(
      '<input %disabled%%checked% id="checkbox%randomid%" type="checkbox" ' .
      'name="groups[%value%]" value="%value%"/>',
      array(
        '%disabled%' => $groupDisabled,
        '%checked%'  => $groupChecked,
        '%randomid%' => $randomid,
        '%value%'    =>
          // paranoidsagbol
          htmlspecialchars( $group['id'], ENT_QUOTES, 'UTF-8', true )
        ,
      )
    );
    $title = htmlspecialchars( $group['name'], ENT_QUOTES, 'UTF-8', true );
    $label = '<label for="checkbox' . $randomid . '">' . $title . '</label>';
    $groupHTML[] = strtr(
      $this->checkboxitemlayout,
      array(
        '%level%'           => '1', // ez egy szintu mindig
        '%indent%'          => '',  // ergo indent sincs
        '%checkbox%'        => $checkbox,
        '%valuehtmlescape%' => $title,
        '%label%'           => $label,
      )
    );
  }

  $config['groups[]']['values']    = $groupAssoc;
  $config['groups[]']['value']     = array_keys( $groupValues );
  $config['groups[]']['rowlayout'] = strtr('
      <tr %errorstyle%>
        <td class="labelcolumn">
          <label for="%id%">%displayname%</label>
        </td>
        <td class="elementcolumn">%prefix%%CUSTOMHTML%%postfix%%errordiv%</td>
      </tr>
    ',
    array(
      '%CUSTOMHTML%' => implode( "\n", $groupHTML ),
    )
  );

  // takaritunk magunk utan
  unset(
    $groupHTML,
    $groupAssoc,
    $groupValues,
    $usedids,
    $groups
  );
}

$config['lastloggedin'] = array(
  'displayname' => $l('users', 'lastloggedin'),
  'type'        => 'inputText',
  'html'        => 'disabled="disabled"',
);
$config['lastloggedinipaddress'] = array(
  'displayname' => $l('users', 'lastloggedinipaddress'),
  'type'        => 'textarea',
  'html'        => 'disabled="disabled"',
);

if ($this->userModel->row['source'] !== 'local' and $this->userModel->row['source']) {
  $config['externalid'] = array(
    'displayname' => $l('users', 'externalid'),
    'type'        => 'inputText',
  );

  foreach( $this->basefields as $field ) {
    if ( !isset( $config[ $field ] ) )
      $field .= '[]';

    if ( !isset( $config[ $field ] ) )
      continue;

    $config[ $field ]['html'] = 'disabled="disabled"';
    unset( $config[ $field ]['validation'] );
  }

}
