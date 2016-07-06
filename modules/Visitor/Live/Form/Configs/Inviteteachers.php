<?php
$lang = \Springboard\Language::get();
$currentURL =
  $lang . '/live/inviteteachers/' .
  $this->feedModel->id . '?forward=' .
  rawurlencode( $this->application->getParameter('forward') )
;

$regenURL =
  $lang . '/live/regeneratepin/' .
  $this->feedModel->id . '?forward=' . rawurlencode( $currentURL )
;

$searchURL = $lang . '/live/searchuser/' . $this->feedModel->id;

$pinText = '
  <div id="pin">
    <span>' . $this->pin . '</span>
    <a href="' . $regenURL . '" class="confirm">' .
      $l('live', 'teacher_regenpin') .
    '</a>
  </div>
';

$users = $this->getUsers();
if ( !$this->invModel )
  $emails = '';
else
  $emails = $this->invModel->row['emails'];

$config = array(

  'action' => array(
    'type'  => 'inputHidden',
    'value' => 'submitinviteteachers'
  ),

  'id' => array(
    'type'  => 'inputHidden',
    'value' => $this->application->getNumericParameter('id'),
  ),

  'frominviteid' => array(
    'type'  => 'inputHidden',
    'value' => $this->application->getNumericParameter('frominviteid'),
  ),

  'pin' => array(
    'type'        => 'text',
    'displayname' => $l('live', 'teacher_pin'),
    'value'       => $pinText,
  ),

  'userids[]' => array(
    'displayname' => $l('live', 'teacher_user'),
    'type'        => 'select',
    'html'        => 'data-searchurl="' . $searchURL . '" multiple="multiple"',
    'values'      => $users,
    'value'       => array_keys( $users ),
    'validation'  => array(
    ),
  ),

  'emails' => array(
    'displayname' => $l('live', 'teacher_email'),
    'type'        => 'textarea',
    'value'       => $emails,
    'validation'  => array(
    ),
  ),

);
