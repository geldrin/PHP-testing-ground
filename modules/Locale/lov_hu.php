<?php

return array(
  
  'languages' => array(
    'hu' => 'magyar',
    'en' => 'angol',
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
    'content_css' => '../css/style_tinymce_content' . $this->bootstrap->config['version'] . '.css',
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
    'departments'   => 'szervezeti egységek egy csoportja számára',
    'groups'        => 'saját csoportom számára',
  ),
  
  'permissions' => array(
    'isadmin'       => 'Adminisztrátor',
    'iseditor'      => 'Szerkesztő',
    'isclientadmin' => 'Kliens adminisztrátor',
    'isuploader'    => 'Feltöltő',
    'isliveadmin'   => 'Live adminisztrátor',
  ),
  
  'recordingstatus' => array(
    'uploading'             => 'Feltöltés alatt',
    'uploaded'              => 'Feltöltve',
    'onstorage'             => 'Konvertálás kész',
    'copyingtoconverter'    => 'Feldolgozásra várakozás',
    'markedfordeletion'     => 'Törölve',
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
  
  'aspectratios' => array(
    '4:3'  => '4:3',
    '5:4'  => '5:4',
    '16:9' => '16:9',
  ),
  
  'hascontent' => array(
    '0' => 'egy videófolyam (stream) használata',
    '1' => 'két videófolyam (stream) egyidejű használata (pl. előadó és fóliák párhuzamos közvetítéséhez)',
  ),
  
  'moderationtype' => array(
    'postmoderation' => 'Útólagos moderálás',
    'premoderation'  => 'Megjelenés előtt moderált hozzászólások',
    'nochat'         => 'Hozzászólások letiltva',
  ),
  
  'feedtype' => array(
    'live' => 'Élő felvétel',
    'vcr'  => 'Videókonferencia felvétele',
  ),
  
  'encryption' => array(
    0 => 'Titkosítás nélküli adatfolyam',
    1 => 'Titkosítás (nagyobb erőforrásigény)',
  ),
  
  'streamstatus' => array(
    'start'         => 'Felvétel indítása folyamatban...',
    'starting'      => 'Felvétel indítása folyamatban...',
    'recording'     => 'Felvétel folyamatban...',
    'disconnect'    => 'Felvétel lebontása...',
    'disconnecting' => 'Felvétel lebontása...',
    'upload'        => 'Felvétel feldolgozása folyamatban...',
    'ready'         => 'Felvétel feldolgozása folyamatban...',
  ),
  
  'quality' => array(
    0 => 'Normál',
    1 => 'HD',
  ),
  
  'live_compatibility' => array(
    'isdesktopcompatible' => 'Desktop',
    'isandroidcompatible' => 'Android',
    'isioscompatible'     => 'iOS',
  ),
  
);
