<?php

$interfacelanguages = array();
foreach( $this->application->config['languages'] as $language )
  $interfacelanguages[ $language ] = $language;

$config = Array(
  
  'target' => Array(
    'type'  => 'inputHidden',
    'value' => 'insert'
  ),

  'id' => Array(
    'type'  => 'inputHidden',
    'value' => '0'
  ),

  'email' => Array(
    'displayname' => 'E-mail address',
    'type'        => 'inputText',
    'validation'  => Array(
      Array( 'type' => 'required' )
    )
  ),

  'password' => Array(
    'displayname' => 'Password',
    'type'        => 'inputText',
    'postfix'     => sprintf(
      '<br/><br/><div class="info">
        Kitöltés esetén felülíródik.<br/>
        A felhasználó jelszavát mindig titkosítjuk ezért beállítás után nem visszanyerhető.<br/>
        Generált jelszó: %s</div>
      ',
      $this->bootstrap->getEncryption()->randomPassword( 8 )
    ),
  ),

  'timestamp' => Array(
    'displayname' => 'Registration timestamp',
    'type'        => 'inputText',
    'value'       => date("Y-m-d H:i:s"),
    'validation'  => Array(
      Array(
        'type' => 'date',
        'format' => 'YYYY-MM-DD hh:mm:ss' 
      ),
    ),
  ),

  'lastloggedin' => Array(
    'displayname' => 'Last login timestamp',
    'type'        => 'inputText',
    'value'       => date("Y-m-d H:i:s"),
    'validation'  => Array(
      Array(
        'type' => 'date',
        'format' => 'YYYY-MM-DD hh:mm:ss' 
      ),
    ),
  ),
  
  'nickname' => array(
    'displayname' => 'Nick Name',
    'type'        => 'inputText',
  ),
  
  'namefirst' => array(
    'displayname' => 'First Name',
    'type'        => 'inputText',
  ),
  
  'namelast' => array(
    'displayname' => 'Last Name',
    'type'        => 'inputText',
  ),
  
  'organizationid' => array(
    'displayname' => 'Organization',
    'type'        => 'selectDynamic',
    'sql'         => "
      SELECT 
        id, CONCAT( IF(LENGTH(nameoriginal) > 0, nameoriginal, nameenglish ), ' - ', id )
      FROM 
        organizations
      WHERE
        %s
      ORDER BY
        IF(LENGTH(nameoriginal), nameoriginal, nameenglish )
    ",
    'value'       => '0',
    'treeid'      => 'id',
    'treestart'   => '0',
    'treeparent'  => 'parentid',
  ),
  
  'browser' => array(
    'displayname' => 'Browser',
    'type'        => 'textarea',
    'html'        => 'rows="10" cols="150"',
  ),
  
  'validationcode' => array(
    'displayname' => 'Validation code',
    'type'        => 'inputText',
  ),
  
  'nameformat' => array(
    'displayname' => 'Name Format',
    'type'        => 'select',
    'values'      => array(
      'straight' => 'Normal - LN FN',
      'reverse'  => 'Reverse - FN LN',
    ),
  ),
  
  'language' => array(
    'displayname' => 'Interface language',
    'type'        => 'select',
    'values'      => $interfacelanguages,
  ),
  
  'newsletter' => array(
    'displayname' => 'Recieving newsletter?',
    'type'        => 'inputRadio',
    'values'      => $l->getLov('yesno'),
  ),
  
  'isadmin' => array(
    'displayname' => 'Admin?',
    'type'        => 'inputRadio',
    'values'      => $l->getLov('yesno'),
  ),
  
  'iseditor' => array(
    'displayname' => 'Editor?',
    'type'        => 'inputRadio',
    'values'      => $l->getLov('yesno'),
  ),
  
  'isuploader' => array(
    'displayname' => 'Uploader?',
    'type'        => 'inputRadio',
    'values'      => $l->getLov('yesno'),
  ),
  
  'disabled' => array(
    'displayname' => 'Banned?',
    'type'        => 'inputRadio',
    'values'      => $l->getLov('yesno'),
  ),
);

$listconfig = Array(

  'table'     => 'users',
  'modify'    => 'id',
  'delete'    => 'id',
  'order'     => Array('id DESC' ),
  
  'fields' => Array(

    Array(
      'displayname' => $l('admin', 'id'),
      'field' => 'id',
    ),

    Array(
      'field' => 'email',
      'displayname' => 'E-mail',
      'phptrigger' => '
         "<VALUE><br />" .
         "<a target=\"_blank\" " . 
           "href=\"users/loginas/?id=" . $fields["id"] . "\">".
         "<img style=\"vertical-align: middle; margin: 5px 0px; width: 18px\" " . 
           "src=\"images/sekkyumu/user.png\">Belépés</a>"
       ',
    ),

    Array(
      'field' => 'isuploader',
      'displayname' => 'upload',
      'lov' => $l->getLov('yes'),
    ),

    Array(
      'field' => 'iseditor',
      'displayname' => 'edit',
      'lov' => $l->getLov('yes'),
    ),

    Array(
      'field' => 'isadmin',
      'displayname' => 'admin',
      'lov' => $l->getLov('yes'),
    ),

    Array(
      'field' => 'timestamp',
      'displayname' => 'Regisztrált',
    ),

    Array(
      'field' => 'browser',
      'displayname' => 'Diagnosztika',
      'layout' => '<td><pre style="width: 300px; overflow-x: scroll; font-size: 10px">%s</pre></td>',
    ),

    Array(
      'field' => 'lastloggedin',
      'displayname' => 'Utolsó belépés',
    ),

  ),

);
