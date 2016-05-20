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
    'iseditor'      => 'Editor',
    'isnewseditor'  => 'News editor',
    'isclientadmin' => 'Client administrator',
    'isuploader'    => 'Uploader',
    'ismoderateduploader' => 'Moderated uploader',
    'isliveadmin'   => 'Live administrator',
  ),
  
  'recordingstatus' => array(
    'uploading'             => 'Uploading',
    'uploaded'              => 'Uploaded',
    'onstorage'             => 'Available',
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
  
  'chatmoderation' => array(
    -1 => 'awaiting moderation',
    0  => 'accepted',
    1  => 'censored',
  ),
  
  'feedtype' => array(
    'live' => 'Live recording',
    'vcr'  => 'Record videoconference',
  ),
  
  'encryption' => array(
    0 => 'Unencrypted',
    1 => 'Encrypted',
  ),
  
  'feedstatus' => array(
    'start'         => 'Recording initiation...',
    'starting'      => 'Recording initiation...',
    'recording'     => 'Recording in progress...',
    'disconnect'    => 'Recording termination...',
    'disconnecting' => 'Recording termination...',
    'upload'        => 'Recording processing...',
    'ready'         => 'Recording processing...',
    ''              => '',
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
);
