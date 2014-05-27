<?php

return array(
  
  'languages' => array(
    'hu' => 'hungarian',
    'en' => 'english',
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
    1 => '<font color="green"><b>yes</b></font>',
  ),
  
  'yesno' => array(
    1 => 'yes',
    0 => 'no',
  ),
  
  'noyes' => array(
    0 => 'no',
    1 => 'yes',
  ),
  
  'registrationtype' => array(
    'open' => 'open',
    'closed' => 'closed',
    'restricted' => 'restricted',
  ),
  
  'accesstype' => array(
    'public'        => 'public (recommended)',
    'registrations' => 'requires registration',
    'departmentsorgroups' => 'group of departments or my own groups',
  ),
  
  'permissions' => array(
    'isadmin'       => 'Administrator',
    'iseditor'      => 'Editor',
    'isclientadmin' => 'Client administrator',
    'isuploader'    => 'Uploader',
    'isliveadmin'   => 'Live administrator',
  ),
  
  'recordingstatus' => array(
    'uploading'             => 'Uploading',
    'uploaded'              => 'Uploaded',
    'onstorage'             => 'Converted',
    'copyingtoconverter'    => 'Converting',
    'markedfordeletion'     => 'Marked for deletion',
    'deleted'               => 'Deleted',
    'unavailable'           => 'Converting',
    'reconvert'             => 'Converting',
    'init'                  => 'Converting',
    'reconvert'             => 'Converting',
    'copyingfromfrontend'   => 'Converting',
    'copiedfromfrontend'    => 'Converting',
    'copyingtostorage'      => 'Converting',
    'converting'            => 'Converting',
    'converting1thumbnails' => 'Converting',
    'converting2audio'      => 'Converting',
    'converting3video'      => 'Converting',
    'failed'                => 'Conversion failed',
  ),
  
  'moderationtype' => array(
    'postmoderation' => 'Post moderation',
    'premoderation'  => 'Explicit moderation',
    'nochat'         => 'Chat disabled',
  ),
  
  'feedtype' => array(
    'live' => 'Live recording',
    'vcr'  => 'Record videoconference',
  ),
  
  'encryption' => array(
    0 => 'Unencrypted',
    1 => 'Encrypted',
  ),
  
  'streamstatus' => array(
    'start'         => 'Recording initiation...',
    'starting'      => 'Recording initiation...',
    'recording'     => 'Recording in progress...',
    'disconnect'    => 'Recording termination...',
    'disconnecting' => 'Recording termination...',
    'upload'        => 'Recording processing...',
    'ready'         => 'Recording processing...',
    ''              => '',
  ),
  
  'quality' => array(
    0 => 'Normal',
    1 => 'HD',
  ),
  
  'live_compatibility' => array(
    'isdesktopcompatible' => 'Desktop',
    'isandroidcompatible' => 'Android',
    'isioscompatible'     => 'iOS',
  ),
  
  'isintrooutro' => array(
    0 => 'recording',
    1 => 'intro/outro',
  ),
  
  'search_wholeword' => array(
    0 => 'any word',
    1 => 'whole word',
  ),
  
  'invite_contenttype' => array(
    'nocontent'   => 'Invitation without specifying content',
    'recordingid' => 'Recording',
    'livefeedid'  => 'Live broadcast',
    'channelid'   => 'Channel',
  ),

  'invite_usertype' => array(
    'single'   => 'Single user',
    'multiple' => 'Multiple users',
  ),

  'invite_status' => array(
    'invited'    => 'Invited',
    'existing'   => 'Existing user',
    'registered' => 'Registered',
    'deleted'    => 'Deleted',
  ),

  'invite_templates' => array(
    '' => '--- New template ---',
  ),
);
