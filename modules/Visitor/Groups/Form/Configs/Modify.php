<?php
include('Create.php');
$config['action']['value'] = 'submitmodify';
$config['fs1']['legend']   = $l('groups', 'modify_title');
$config['fs1']['prefix']   =
  '<span class="legendsubtitle">' . $l('groups', 'modify_subtitle') . '</span>'
;
$config['id']              = Array(
  'type'  => 'inputHidden',
  'value' => $this->application->getNumericParameter('id'),
);
