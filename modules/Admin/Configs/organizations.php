<?php
include_once( $this->bootstrap->config['libpath'] . 'clonefish/constants.php');
$config = Array(
   
  'action' => Array(
    'type'  => 'inputHidden',
    'value' => 'insert'
  ),

  'id' => Array(
    'type'  => 'inputHidden',
    'value' => '0',
  ),
  
  'parentid' => Array(
    'displayname' => 'Szülő intézmény',
    'type'        => 'selectDynamic',
    'values'      => array( 0 => 'Nincs szülő intézmény' ),
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
    'treeid'      => 'id',
    'treeparent'  => 'parentid',
    'treestart'   => '0',
  ),
  
  'languages[]' => array(
    'displayname' => 'Támogatott nyelvek',
    'type'        => 'select',
    'html'        => 'multiple="multiple"',
    'values'      => $l->getLov('languages'),
    'value'       => array_keys( $l->getLov('languages') ),
    'validation'  => array(
    ),
  ),
  
  'name_stringid' => array(
    'displayname' => 'Név',
    'type'        => 'inputTextMultiLanguage',
    'languages'   => $l->getLov('languages'),
    'validation'  => array(
    ),
  ),
  
  'nameshort_stringid' => array(
    'displayname' => 'Rövid név',
    'type'        => 'inputTextMultiLanguage',
    'languages'   => $l->getLov('languages'),
    'validation'  => array(
    ),
  ),
  
  'introduction_stringid' => Array(
    'displayname' => 'Üdvözlő szöveg',
    'type'        => 'tinymceMultiLanguage',
    'languages'   => $l->getLov('languages'),
    'value'       => 0,
    'width'       => 305,
    'height'      => 500,
    'config'      => $l->getLov('tinymceadmin'),
    'validation'  => Array(
    )
  ),
  
  'url' => array(
    'displayname' => 'URL',
    'type'        => 'inputText',
    'validation'  => array(
      array(
        'type' => 'string',
        'minimum'  => 2,
        'maximum'  => 512,
        'required' => false,
      ),
    ),
  ),
  
  'domain' => array(
    'displayname' => 'Domain',
    'type'        => 'inputText',
    'validation'  => array(
      array(
        'type' => 'string',
        'minimum'  => 2,
        'maximum'  => 512,
        'required' => false,
      ),
    ),
    'handlers' => array(
      'clearcache' => array(
        array(
          'key' => 'organizations-%domain%',
        ),
      ),
    ),
  ),
  
  'staticdomain' => array(
    'displayname' => 'File kiszolgáló domain',
    'type'        => 'inputText',
    'validation'  => array(
      array(
        'type' => 'string',
        'minimum'  => 2,
        'maximum'  => 512,
        'required' => false,
      ),
    ),
  ),
  
  'cookiedomain' => array(
    'displayname' => 'Cookie domain (ami lefedi az összes többi domaint)',
    'type'        => 'inputText',
    'validation'  => array(
      array(
        'type' => 'string',
        'minimum'  => 2,
        'maximum'  => 512,
        'required' => false,
      ),
    ),
  ),
  
  'supportemail' => array(
    'displayname' => 'Support e-mail cím',
    'type'        => 'inputText',
    'validation'  => array(
      array(
        'type'     => 'string',
        'regexp'   => CF_EMAIL,
        'help'     => $l('users', 'emailhelp'),
        'required' => false,
      ),
    ),
  ),
  
  'mailerrorto' => array(
    'displayname' => 'Küldési hiba e-mail cím (Errors-To:)',
    'type'        => 'inputText',
    'validation'  => array(
      array(
        'type'     => 'string',
        'regexp'   => CF_EMAIL,
        'help'     => $l('users', 'emailhelp'),
        'required' => false,
      ),
    ),
  ),
  
  'linkcolor' => array(
    'displayname' => 'Linkek színe',
    'type'        => 'inputText',
    'postfix'     => '
      <div class="info">
        Hat karakteres hexadecimális kód, például: f1f1f1
      </div>
    ',
    'validation'  => array(
      array(
        'type' => 'string',
        'minimum'  => 6,
        'maximum'  => 6,
        'required' => false,
        'help'     => 'A link színét hat karakteres hexadecimális kódként várjuk!',
      ),
    ),
  ),
  
  'fullnames' => array(
    'displayname' => 'Feltöltő teljes nevének kiírása?',
    'type'        => 'inputRadio',
    'values'      => $l->getLov('yesno'),
    'value'       => 0,
  ),
  
  'issubscriber' => array(
    'displayname' => 'Előfizető?',
    'type'        => 'inputRadio',
    'values'      => $l->getLov('yesno'),
    'value'       => 0,
  ),

  'isplayerlogolinkenabled' => array(
    'displayname' => 'A lejátszóban megjelenő logo linkelése?',
    'type'        => 'inputRadio',
    'values'      => $l->getLov('yesno'),
    'value'       => 1,
  ),
  
  'isvcrenabled' => array(
    'displayname' => 'VCR funkcionalitás?',
    'type'        => 'inputRadio',
    'values'      => $l->getLov('yesno'),
    'value'       => 0,
  ),
  
  'issecurestreamingenabled' => array(
    'displayname' => 'Biztonságos streamelés?',
    'type'        => 'inputRadio',
    'values'      => $l->getLov('yesno'),
    'value'       => 0,
  ),
  
  'islivestreamingenabled' => array(
    'displayname' => 'Élő közvetítés?',
    'type'        => 'inputRadio',
    'values'      => $l->getLov('yesno'),
    'value'       => 0,
  ),
  
  'issessionvalidationenabled' => array(
    'displayname' => 'Felhasználók IP címének és böngésző azonosítójának ellenőrzése?',
    'type'        => 'inputRadio',
    'values'      => $l->getLov('yesno'),
    'value'       => 0,
  ),
  
  'registrationtype' => array(
    'displayname' => 'Regisztráció típusa',
    'type'        => 'select',
    'values'      => $l->getLov('registrationtype'),
  ),
  
  'elearningcoursecriteria' => array(
    'displayname' => 'Kurzusokban a felvételek ennyi százaléket muszáj végignézni mielőtt a következő felvételre engedjük.',
    'type'        => 'inputText',
    'value'       => '90',
    'validation'  => array(
      array(
        'type'     => 'number',
        'real'     => 0,
        'minimum'  => 1,
        'maximum'  => 100,
        'required' => true,
      ),
    ),
  ),

  'iselearningcoursesessionbound' => array(
    'displayname' => 'A kurzus felvétel viszzaállítása csak az adott munkamenetben?',
    'postfix'     =>
      '<br/><div class="info">Az adott felvétel utolsó pozicióját' .
        ' csak akkor állítjuk vissza ha a felhasználó ugyanabban a munkamenetben' .
        ' van (nem zárta be a böngészőt, nem volt távol túl sokáig az oldalt, nem' .
        ' lépett ki, stb...)<br/>' .
        'Alapértelmezve pedig mindig visszaállítjuk a poziciót.' .
      '</div>',
    'type'        => 'inputRadio',
    'values'      => $l->getLov('yesno'),
    'value'       => 0,
  ),

  'disabled' => array(
    'displayname' => 'Kitiltva?',
    'type'        => 'inputRadio',
    'values'      => $l->getLov('yesno'),
    'value'       => 0,
  ),
  
);

$listconfig = Array(
  
  'treeid'             => 'o.id',
  'treestart'          => '0',
  'treeparent'         => 'o.parentid',
  'treestartinclusive' => true,
  
  'type'      => 'tree',
  'table'     => '
    organizations AS o
    LEFT JOIN strings AS sname
      ON ( sname.translationof = o.name_stringid AND sname.language = "hu" )
    LEFT JOIN strings AS sshort
      ON ( sshort.translationof = o.nameshort_stringid AND sshort.language = "hu" )
  ',
  'order'     => Array( 'o.id DESC' ),
  'modify'    => 'o.id',
  
  'fields' => Array(
    
    Array(
      'field'       => 'o.id',
      'displayname' => 'ID',
    ),
    
    Array(
      'field'       => 'domain',
      'displayname' => 'Domain',
    ),
    
    Array(
      'field'       => 'sname.value',
      'displayname' => 'Eredeti név',
    ),
    
    Array(
      'field'       => 'sshort.value',
      'displayname' => 'Rövid név',
    ),
    
    Array(
      'field'       => 'issubscriber',
      'displayname' => $config['issubscriber']['displayname'],
      'lov'         => $l->getLov('yes')
    ),
    
    Array(
      'field'       => 'isvcrenabled',
      'displayname' => $config['isvcrenabled']['displayname'],
      'lov'         => $l->getLov('yes')
    ),
    
    Array(
      'field'       => 'issecurestreamingenabled',
      'displayname' => $config['issecurestreamingenabled']['displayname'],
      'lov'         => $l->getLov('yes')
    ),
    
  ),
  
);
