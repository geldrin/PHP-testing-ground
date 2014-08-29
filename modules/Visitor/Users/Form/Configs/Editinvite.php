<?php
include('Invite.php');

$config['action']['value'] = 'submiteditinvite';
unset(
  $config['email']['validation'][0]['anddepend'],
  $config['usertype'],
  $config['invitefile'],
  $config['encoding'],
  $config['delimeter']
);

if ( $this->invitationModel->row['status'] != 'invited' )
  unset(
    $config['fs_permission'],
    $config['permissions[]'],
    $config['fs_group'],
    $config['departments[]'],
    $config['groups[]']
  );


if ( $this->invitationModel->row['recordingid'] ) {

  $id              = $this->invitationModel->row['recordingid'];
  $recordingsModel = $this->controller->modelIDCheck(
    'recordings',
    $id
  );
  
  $title = $recordingsModel->row['title'];
  if ( strlen( trim( $recordingsModel->row['subtitle'] ) ) )
    $title .= '<br/>' . $recordingsModel->row['subtitle'];

  $config['recordingid']['rowlayout'] = $this->insertIDAndTitle(
    $config['recordingid']['rowlayout'],
    $id,
    $title
  );

}

if ( $this->invitationModel->row['channelid'] ) {

  $id           = $this->invitationModel->row['channelid'];
  $channelModel = $this->controller->modelIDCheck(
    'channels',
    $id
  );
  
  $title = $channelModel->row['title'];
  if ( strlen( trim( $channelModel->row['subtitle'] ) ) )
    $title .= '<br/>' . $channelModel->row['subtitle'];

  $config['channelid']['rowlayout'] = $this->insertIDAndTitle(
    $config['channelid']['rowlayout'],
    $id,
    $title
  );

}

if ( $this->invitationModel->row['livefeedid'] ) {

  include_once( $this->bootstrap->config['templatepath'] . 'Plugins/modifier.shortdate.php');
  $id            = $this->invitationModel->row['livefeedid'];
  $livefeedModel = $this->controller->modelIDCheck(
    'livefeeds',
    $id
  );
  $channelModel = $this->controller->modelIDCheck(
    'channels',
    $livefeedModel->row['channelid']
  );

  $title = $livefeedModel->row['name'];
  if ( $channelModel->row['starttimestamp'] )
    $title .= '<br/>' . smarty_modifier_shortdate(
      '%Y. %B %e',
      $channelModel->row['starttimestamp'],
      $channelModel->row['endtimestamp']
    );

  $config['livefeedid']['rowlayout'] = $this->insertIDAndTitle(
    $config['livefeedid']['rowlayout'],
    $id,
    $title
  );

}

if ( $this->invitationModel->row['templateid'] ) {

  $userModel = $this->bootstrap->getModel('users');
  $template  = $userModel->getTemplate(
    $this->invitationModel->row['templateid'],
    $this->controller->organization['id']
  );

  if ( empty( $template ) )
    throw new \Exception("No template found for invitation!");

  $config['templateprefix']['value']  =
    $template['prefix'] ?: $l('users', 'templateprefix_default')
  ;

  $config['templatepostfix']['value'] =
    $template['postfix'] ?: $l('users', 'templatepostfix_default')
  ;

}
