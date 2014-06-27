<?php

$config = array(

  'action' => array(
    'type'  => 'inputHidden',
    'value' => 'submitanalytics'
  ),

  'feedids' => array(
    'type'        => 'inputCheckboxDynamic',
    'displayname' => $l('live', 'analytics_feedids'),
    'sql'         => "
      SELECT id, name
      FROM livefeeds
      WHERE channelid = '" . $this->channelModel->id . "'
    ",
    'value'       => $this->application->getParameter('feedids', array() ),
  ),

);
