<?php
include_once( $this->bootstrap->config['libpath'] . 'clonefish/constants.php');
$this->addfieldset = false;

$config = Array(

  'action' => Array(
    'type'  => 'inputHidden',
    'value' => 'insert'
  ),

  'id' => Array(
    'type'  => 'inputHidden',
    'value' => '0',
  ),

  'lastmodifiedtimestamp' => Array(
    'type'     => 'inputHidden',
    'value'    => date('Y-m-d H:i:s'),
    'readonly' => true,
  ),

  'basicsfs' => array(
    'legend' => 'Alapadatok',
    'type'   => 'fieldset',
    'submit' => true,
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

  'emailfs' => array(
    'legend' => 'E-mail',
    'type'   => 'fieldset',
    'submit' => true,
  ),

  'signupvalidationemailsubject_stringid' => array(
    'displayname' => 'Megerősítő e-mail téma',
    'postfix'     => '
      <div class="info">
        Regisztrációt megerősítő e-mail témája (subject). Alapértelmezett téma:<br/>
        ' . htmlentities( $l('users', 'validationemailsubject'), ENT_QUOTES, 'utf-8' ) . '
      </div>
    ',
    'type'        => 'inputTextMultiLanguage',
    'languages'   => $l->getLov('languages'),
    'validation'  => array(
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

  'subscriberfs' => array(
    'legend' => 'Előfizető aldomain beállítások',
    'type'   => 'fieldset',
    'submit' => true,
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

  'subscriberpermissionfs' => array(
    'legend' => 'Előfizetői jogosultságok',
    'type'   => 'fieldset',
    'submit' => true,
  ),

  'issubscriber' => array(
    'displayname' => 'Előfizető?',
    'type'        => 'inputRadio',
    'values'      => $l->getLov('yesno'),
    'value'       => 0,
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

  'subscriberotherfs' => array(
    'legend' => 'Egyéb előfizetői beállítások',
    'type'   => 'fieldset',
    'submit' => true,
  ),

  'registrationtype' => array(
    'displayname' => 'Regisztráció típusa',
    'type'        => 'select',
    'values'      => $l->getLov('registrationtype'),
  ),

  'isnicknamehidden' => array(
    'displayname' => 'Becenév mező elrejtése?',
    'type'        => 'inputRadio',
    'values'      => $l->getLov('yesno'),
    'value'       => 0,
  ),

  'isorganizationaffiliationrequired' => array(
    'displayname' => 'Cégnév mező kötelező?',
    'type'        => 'inputRadio',
    'values'      => $l->getLov('yesno'),
    'value'       => 0,
  ),

  'displaynametype' => array(
    'displayname' => 'Felhasználó nevének kíírása',
    'type'        => 'select',
    'values'      => $l->getLov('organizations_displaynametype'),
    'value'       => 'shownickname',
  ),

  'isrecommendationdisabled' => array(
    'displayname' => 'Flash ajánló letiltása?',
    'type'        => 'inputRadio',
    'values'      => $l->getLov('yesno'),
    'value'       => 0,
  ),

  'isanonymousratingenabled' => array(
    'displayname' => 'Felvételek anonym értékelhetők?',
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

  'issessionvalidationenabled' => array(
    'displayname' => 'Felhasználók IP címének és böngésző azonosítójának ellenőrzése?',
    'type'        => 'inputRadio',
    'values'      => $l->getLov('yesno'),
    'value'       => 0,
  ),

  'hascustomcategories' => array(
    'displayname' => 'Egyedi ikonok használata a kategóriaoldalon',
    'type'        => 'inputRadio',
    'values'      => $l->getLov('yesno'),
    'value'       => 0,
  ),

  'googleanalyticstrackingcode' => array(
    'displayname' => 'Google Analytics kód',
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

  'disabled' => array(
    'displayname' => 'Kitiltva?',
    'type'        => 'inputRadio',
    'values'      => $l->getLov('yesno'),
    'value'       => 0,
  ),

  'coursesettingsfs' => array(
    'legend' => 'Kurzusok',
    'type'   => 'fieldset',
    'submit' => true,
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

  'viewsessiontimeouthours' => array(
    'displayname' => 'A view sessionok timeoutja órákban',
    'type'        => 'inputText',
    'value'       => '5',
    'validation'  => array(
      array(
        'type'     => 'number',
        'real'     => 0,
        'minimum'  => 1,
        'maximum'  => 10000,
        'required' => true,
      ),
    ),
  ),

  'viewsessionallowedextraseconds' => array(
    'displayname' => 'Engedélyezett intervallum amiben view session jelentés jöhet az előzőhöz képest.',
    'type'        => 'inputText',
    'value'       => '360',
    'validation'  => array(
      array(
        'type'     => 'number',
        'real'     => 0,
        'minimum'  => 1,
        'maximum'  => 100000,
        'required' => true,
      ),
    ),
  ),

  'streamsettingsfs' => array(
    'legend' => 'Stream beállítások',
    'type'   => 'fieldset',
    'submit' => true,
  ),

  'ondemandhdsenabled' => array(
    'displayname' => 'On-demand HDS bekapcsolva?',
    'type'        => 'inputRadio',
    'values'      => $l->getLov('noyes'),
    'value'       => 0,
  ),

  'livehdsenabled' => array(
    'displayname' => 'Live HDS bekapcsolva?',
    'type'        => 'inputRadio',
    'values'      => $l->getLov('noyes'),
    'value'       => 0,
  ),

  'ondemandhlsenabledandroid' => array(
    'displayname' => 'Android on-demand HLS bekapcsolva?',
    'type'        => 'inputRadio',
    'values'      => $l->getLov('noyes'),
    'value'       => 0,
  ),

  'livehlsenabledandroid' => array(
    'displayname' => 'Android live HLS bekapcsolva?',
    'type'        => 'inputRadio',
    'values'      => $l->getLov('noyes'),
    'value'       => 0,
  ),

  'layoutfs' => array(
    'legend' => 'Kinézet',
    'type'   => 'fieldset',
    'submit' => true,
  ),

  'layoutcss' => array(
    'displayname' => 'CSS',
    'type'        => 'textarea',
    'html'        => 'style="width: 500px; height: 140px;"',
  ),

  'layoutwysywygcss' => array(
    'displayname' => 'WYSYWYG mező CSS',
    'type'        => 'textarea',
    'html'        => 'style="width: 500px; height: 140px;"',
  ),

  'layoutheader' => array(
    'displayname' => 'Header template',
    'type'        => 'textarea',
    'html'        => 'style="width: 500px; height: 140px;"',
  ),

  'layoutfooter' => array(
    'displayname' => 'Footer template',
    'type'        => 'textarea',
    'html'        => 'style="width: 500px; height: 140px;"',
  ),

);

foreach( array('header', 'footer') as $type ) {
  $default = file_get_contents(
    $this->bootstrap->config['templatepath'] . 'Visitor/' .
    '_layout_' . $type . '.tpl'
  );
  $key = 'layout' . $type;
  $config[ $key ]['value'] = $default;
}

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
