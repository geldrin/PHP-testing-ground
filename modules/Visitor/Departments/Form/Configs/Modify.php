<?php

include('Create.php');

$config['action'] = Array(
  'type'  => 'inputHidden',
  'value' => 'submitmodify'
);

$config['fs1'] = array(
  'type'   => 'fieldset',
  'legend' => $l('departments', 'modify_title'),
  'prefix' => '<span class="legendsubtitle">' . $l('departments', 'modify_subtitle') . '</span>',
);
