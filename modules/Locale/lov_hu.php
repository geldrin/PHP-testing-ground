<?php

return array(
  
  'languages' => array(
    'hu' => 'magyar',
    'en' => 'angol',
  ),
  
  'mailqueueerrors' => array(
    ''          => '<b>küldésre vár</b>',
    'sent'      => '<font color="green"><b>elküldve</b></font>',
    'error'     => '<font color="red"><b>hiba a küldés során</font>',
    'cancelled' => '<font color="brown">leállítva</font>',
  ),
  
  'mailqueueerrors_plain' => array(
    ''          => 'küldésre vár',
    'sent'      => 'elküldve',
    'error'     => 'hiba a küldés során',
    'cancelled' => 'leállítva',
  ),
  
  'tinymceadmin' => array(
    'language' => \Springboard\Language::get(),
    'theme' => "advanced",
    'skin' => "custom",
    'theme_advanced_buttons1' => "code,bold,italic,underline,separator,strikethrough,justifyleft,justifycenter,justifyright,justifyfull,blockquote,bullist,numlist,outdent,indent,undo,redo,link,unlink",
    'theme_advanced_buttons2' => "formatselect,image,media,cleanup,template,|,tablecontrols",
    'theme_advanced_buttons3' => "",
    'theme_advanced_toolbar_location' => "top",
    'theme_advanced_toolbar_align' => "left",
    'theme_advanced_statusbar_location' => "bottom",
    'plugins' => "tabfocus,paste,safari,template,advimage,media,table",
    'paste_auto_cleanup_on_paste' => true,
    'tab_focus' => ":prev,:next",
    'verify_html' => false, // alapbol csak nehany html dolgot enged at, ez kikapcsolja
    'button_tile_map' => true, // nehany doctypeon nem jelennek meg az ikonok, ha eztortenik akkor ezt false-ra
    'gecko_spellcheck' => true,
    'content_css' =>
      '../css/style_tinymce_content' . $this->bootstrap->config['version'] . '.css,' .
      '/contents/layoutwysywygcss?' . $this->bootstrap->config['version']
    ,
    //'relative_urls' => false, // atirja az urleket relativera (ha in-site link), akar jo otlet is lehet
    'entity_encoding' => 'raw',
  ),
  
  'tinymcevisitor' => array(
    'language' => \Springboard\Language::get(),
    'theme' => "advanced",
    'theme_advanced_buttons1' => "bold,italic,separator,link,unlink,separator,bullist,outdent,indent,separator,undo,redo",
    'theme_advanced_buttons2' => "",
    'theme_advanced_buttons3' => "",
    'theme_advanced_toolbar_location' => "top",
    'theme_advanced_toolbar_align' => "left",
    'theme_advanced_statusbar_location' => "none",
    'plugins' => "tabfocus,paste,safari",
    'paste_auto_cleanup_on_paste' => true,
    'tab_focus' => ":prev,:next",
    'verify_html' => true, // szurjuk a htmlt
    'button_tile_map' => true, // nehany doctypeon nem jelennek meg az ikonok, ha eztortenik akkor ezt false-ra
    'gecko_spellcheck' => true,
    //content_css url-t form configbol
    'relative_urls' => false, // atirja az urleket relativera (ha in-site link), akar jo otlet is lehet
    'entity_encoding' => 'raw',
    'extended_valid_elements' => 'b/strong,i/em', // strongot <b>re, em-et <i>re
  ),
  
  'headerlanguages' => array(
    'hu' => 'HU',
    'en' => 'EN',
  ),
  
  'title' => array(
    'Dr.'        => 'Dr.',
    'BSc.'       => 'BSc.',
    'MSc.'       => 'MSc.',
    'PhD.'       => 'PhD.',
    'Prof.'      => 'Prof.',
    'Prof. Emer' => 'Prof. Emer',
    'Sir'        => 'Sir',
    'DLA'        => 'DLA',
  ),
  
  'yes' => array(
    0 => '',
    1 => '<font color="green"><b>igen</b></font>',
  ),
  
  'yesno' => array(
    1 => 'igen',
    0 => 'nem',
  ),
  
  'noyes' => array(
    0 => 'nem',
    1 => 'igen',
  ),
  
  'registrationtype' => array(
    'open' => 'open',
    'closed' => 'closed',
    'restricted' => 'restricted',
  ),
  
  'accesstype' => array(
    'public'        => 'publikus (ajánlott)',
    'registrations' => 'regisztráció szükséges',
    'departmentsorgroups' => 'szervezeti egységek vagy saját csoportom számára',
  ),
  
  'permissions' => array(
    'iseditor'      => 'Szerkesztő',
    'isnewseditor'  => 'Hír szerkesztő',
    'isclientadmin' => 'Kliens adminisztrátor',
    'isuploader'    => 'Feltöltő',
    'ismoderateduploader' => 'Moderált feltöltő',
    'isliveadmin'   => 'Live adminisztrátor',
  ),
  
  'recordingstatus' => array(
    'uploading'             => 'Feltöltés alatt',
    'uploaded'              => 'Feltöltve',
    'onstorage'             => 'Konvertálás kész',
    'copyingtoconverter'    => 'Feldolgozásra vár',
    'markedfordeletion'     => 'Törölve',
    'deleted'               => 'Törölve',
    'unavailable'           => 'Feldolgozás alatt',
    'init'                  => 'Feldolgozás alatt',
    'reconvert'             => 'Feldolgozás alatt',
    'copyingfromfrontend'   => 'Feldolgozás alatt',
    'copiedfromfrontend'    => 'Feldolgozás alatt',
    'copyingtostorage'      => 'Feldolgozás alatt',
    'converting'            => 'Feldolgozás alatt',
    'converting1thumbnails' => 'Feldolgozás alatt',
    'converting2audio'      => 'Feldolgozás alatt',
    'converting3video'      => 'Feldolgozás alatt',
    'failed'                => 'Konvertálás sikertelen',
  ),
  
  'moderationtype' => array(
    'postmoderation' => 'Utólagos moderálás',
    'premoderation'  => 'Megjelenés előtt moderált hozzászólások',
    'nochat'         => 'Hozzászólások letiltva',
  ),

  'chatmoderation' => array(
    -1 => 'moderálásra vár',
    0  => 'engedélyezve',
    1  => 'cenzúrázva',
  ),
  
  'feedtype' => array(
    'live' => 'Élő felvétel',
    'vcr'  => 'Videókonferencia felvétele',
  ),
  
  'encryption' => array(
    0 => 'Nincs titkosítás',
    1 => 'Titkosított streaming',
  ),
  
  'streamstatus' => array(
    'start'         => 'Felvétel indítása folyamatban...',
    'starting'      => 'Felvétel indítása folyamatban...',
    'recording'     => 'Felvétel folyamatban...',
    'disconnect'    => 'Felvétel befejezése...',
    'disconnecting' => 'Felvétel befejezése...',
    'upload'        => 'Felvétel feldolgozása folyamatban...',
    'ready'         => 'Felvétel feldolgozása folyamatban...',
    ''              => '',
  ),
  
  'live_compatibility' => array(
    'isdesktopcompatible' => 'Desktop',
    'isandroidcompatible' => 'Android',
    'isioscompatible'     => 'iOS',
  ),
  
  'isintrooutro' => array(
    0 => 'felvétel',
    1 => 'intro/outro',
  ),
  
  'search_wholeword' => array(
    0 => 'bármelyik szóra',
    1 => 'pontos kifejezésre',
  ),
  
  'invite_contenttype' => array(
    'nocontent'   => 'Nem konkrét tartalomra küldök meghívót',
    'recordingid' => 'Felvétel',
    'livefeedid'  => 'Élő közvetítés',
    'channelid'   => 'Csatorna',
  ),

  'invite_usertype' => array(
    'single'   => 'Egy felhasználó meghívása',
    'multiple' => 'Csoportos meghívás',
  ),

  'invite_status' => array(
    'invited'    => 'Meghívva',
    'existing'   => 'Létező felhasználó',
    'registered' => 'Regisztrált',
    'deleted'    => 'Törölve',
  ),

  'invite_templates' => array(
    '' => '--- Új sablon ---',
  ),

  'live_analytics_datapoints' => array(
    '0' => $l('live', 'stats_numberofdesktop'),
    '1' => $l('live', 'stats_numberofandroid'),
    '2' => $l('live', 'stats_numberofiphone'),
    '3' => $l('live', 'stats_numberofipad'),
    '4' => $l('live', 'stats_sum'),
  ),

  'live_analytics_resolutions' => array(
    '300'   => $l('live', 'resolution_5min'),
    '3600'  => $l('live', 'resolution_hourly'),
    '86400' => $l('live', 'resolution_daily'),
  ),

  'recordings_approvalstatus_full' => array(
    'draft'    => $l('recordings', 'approvalstatus_draft'),
    'pending'  => $l('recordings', 'approvalstatus_pending'),
    'approved' => $l('recordings', 'approvalstatus_approved'),
  ),

  'recordings_approvalstatus_min' => array(
    'draft'    => $l('recordings', 'approvalstatus_draft'),
    'pending'  => $l('recordings', 'approvalstatus_pending'),
  ),

  'recordings_approvalstatus_default' => array(
    'draft'    => $l('recordings', 'approvalstatus_draft'),
    'approved' => $l('recordings', 'approvalstatus_approved'),
  ),

  'groups_source' => array(
    ''          => $l('groups', 'source_default'),
    'directory' => $l('groups', 'source_directory'),
  ),

  'recordings_featurepriority' => array(
    0  => $l('recordings', 'featurepriority_none'),
    1  => $l('recordings', 'featurepriority_normal'),
    20 => $l('recordings', 'featurepriority_hight'),
    30 => $l('recordings', 'featurepriority_maximum'),
  ),

  'statistics_type' => array(
    'recordings' => $l('analytics', 'statistis_type_recordings'),
    'live'       => $l('analytics', 'statistis_type_live'),
  ),

  'users_invite_externalsend' => array(
    'local'    => $l('users', 'invite_externalsend_local'),
    'external' => $l('users', 'invite_externalsend_external'),
  ),

  // adminon jelenik meg
  'organizations_displaynametype' => array(
    'shownickname' => 'Becenév mutatása',
    'showfullname' => 'Teljes név mutatása',
  ),
);
