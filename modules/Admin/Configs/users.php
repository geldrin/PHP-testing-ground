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

  'timestampdisabledafter' => Array(
    'displayname' => 'Registration disabled after',
    'type'        => 'inputText',
    'validation'  => Array(
      Array(
        'type'     => 'date',
        'format'   => 'YYYY-MM-DD hh:mm:ss',
        'required' => false,
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
        o.id, CONCAT( s.value, ' - ', o.id )
      FROM 
        organizations AS o,
        strings AS s
      WHERE
        s.translationof = o.name_stringid AND
        s.language = 'hu' AND
        %s
      ORDER BY s.value
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
    'displayname' => 'Adminsztrátor?',
    'type'        => 'inputRadio',
    'values'      => $l->getLov('yesno'),
  ),
  
  'isclientadmin' => array(
    'displayname' => 'Ügyfél adminsztrátor?',
    'type'        => 'inputRadio',
    'values'      => $l->getLov('yesno'),
  ),
  
  'isliveadmin' => array(
    'displayname' => 'Élő közvetítés szerkesztő?',
    'type'        => 'inputRadio',
    'values'      => $l->getLov('yesno'),
  ),
  
  'iseditor' => array(
    'displayname' => 'Szerkesztő?',
    'type'        => 'inputRadio',
    'values'      => $l->getLov('yesno'),
  ),
  
  'isnewseditor' => array(
    'displayname' => 'Hírszerkesztő?',
    'type'        => 'inputRadio',
    'values'      => $l->getLov('yesno'),
  ),
  
  'isuploader' => array(
    'displayname' => 'Feltöltő?',
    'type'        => 'inputRadio',
    'values'      => $l->getLov('yesno'),
  ),
  
  'isapienabled' => array(
    'displayname' => 'API használatának engedélyezése?',
    'type'        => 'inputRadio',
    'values'      => $l->getLov('yesno'),
  ),
  
  'apiaddresses' => Array(
    'displayname' => 'API hozzáférésére jogosult IP címek',
    'html'        => 'style="width: 400px"',
    'postfix'     => '
    <br /><br />
    <div class="info">
      Több IP cím is megadható veszzővel (,) elválasztva. <br/>
      Tartományok megadásához használjon csillagot. <br/>
      Például: 192.168.* vagy 10.0.1.*
    </div>',
    'type'        => 'inputText',
    'validation'  => Array(
      array(
        'type'     => 'string',
        'required' => false,
        'regexp'   => '/^[0-9\.,\*]+$/',
        'help'     => 'Csak számok, pontok, csillagok és vesszők használhatóak.'
      ),
    )
  ),
  
  'disabled' => array(
    'displayname' => 'Banned?',
    'type'        => 'inputRadio',
    'values'      => $l->getLov('yesno'),
    'value'       => 0,
  ),
);

if ( $this->action == 'new' or $this->action == 'insert' ) {
  
  $config['password']['validation'] = array(
    array( 'type' => 'required' )
  );
  
}

$listconfig = Array(

  'table'     => 'users AS u LEFT JOIN organizations AS o ON o.id = u.organizationid',
  'modify'    => 'u.id',
  'delete'    => 'u.id',
  'order'     => Array('u.id DESC' ),
  
  'fields' => Array(

    Array(
      'displayname' => $l('admin', 'id'),
      'field' => 'u.id',
    ),

    Array(
      'field' => 'u.email',
      'displayname' => 'E-mail',
      'phptrigger' => '
         "<VALUE><br />" .
         "<a target=\"_blank\" " . 
           "href=\"users/loginas/?id=" . $fields["u.id"] . "\">".
         "<img style=\"vertical-align: middle; margin: 5px 0px; width: 18px\" " . 
           "src=\"images/sekkyumu/user.png\">Belépés</a>"
       ',
    ),

    Array(
      'field' => 'o.domain',
      'displayname' => 'Domain',
    ),

    Array(
      'field' => 'u.isuploader',
      'displayname' => 'upload',
      'lov' => $l->getLov('yes'),
    ),

    Array(
      'field' => 'u.iseditor',
      'displayname' => 'edit',
      'lov' => $l->getLov('yes'),
    ),

    Array(
      'field' => 'u.isnewseditor',
      'displayname' => 'newsedit',
      'lov' => $l->getLov('yes'),
    ),

    Array(
      'field' => 'u.isliveadmin',
      'displayname' => 'liveadmin',
      'lov' => $l->getLov('yes'),
    ),

    Array(
      'field' => 'u.isadmin',
      'displayname' => 'admin',
      'lov' => $l->getLov('yes'),
    ),

    Array(
      'field' => 'u.isclientadmin',
      'displayname' => 'clientadmin',
      'lov' => $l->getLov('yes'),
    ),

    Array(
      'field' => 'u.isapienabled',
      'displayname' => 'api',
      'lov' => $l->getLov('yes'),
    ),

    Array(
      'field' => 'u.timestamp',
      'displayname' => 'Regisztrált',
    ),

    Array(
      'field' => 'u.browser',
      'displayname' => 'Diagnosztika',
      'layout' => '<td><pre style="width: 300px; overflow-x: scroll; font-size: 10px">%s</pre></td>',
    ),

    Array(
      'field' => 'u.lastloggedin',
      'displayname' => 'Utolsó belépés',
    ),

  ),

);
