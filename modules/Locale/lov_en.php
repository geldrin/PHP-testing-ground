<?php

return array(
  
  'languages' => array(
    'hu' => 'hungarian',
    'en' => 'english',
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
    'departments'   => 'group of departments',
    'groups'        => 'own defined group',
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
  
  'aspectratios' => array(
    '4:3'  => '4:3',
    '5:4'  => '5:4',
    '16:9' => '16:9',
  ),
  
  'numberofstreams' => array(
    '1' => 'Use one stream',
    '2' => 'Use two streams at the same time (for broadcasting the presenter and slides simultaneously)',
  ),
  
  'streamtypes' => array(  
    'normal'        => 'Normal',
    'mobile'        => 'Mobile',
    'normal/mobile' => 'Normal/mobile',
  ),
  
  'moderationtype' => array(
    'postmoderation' => 'After-the-fact moderation',
    'premoderation'  => 'Moderated before appearing',
    'nochat'         => 'Chat disabled',
  ),
  
  'feedtype' => array(
    'live' => 'Live recording',
    'vcr'  => 'Record video conference',
  ),
  
  'encryption' => array(
    0 => 'No encryption',
    1 => 'Encrypt (resource intensive)',
  ),
  
  'streamstatus' => array(
    'start'         => 'Recording starting...',
    'starting'      => 'Recording starting...',
    'recording'     => 'Recording in progress...',
    'disconnect'    => 'Recording stopping...',
    'disconnecting' => 'Recording stopping...',
    'upload'        => 'Recording processing...',
    'ready'         => 'Recording processing...',
  ),
  
);
