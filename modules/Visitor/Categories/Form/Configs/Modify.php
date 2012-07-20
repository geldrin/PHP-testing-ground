<?php

include('Create.php');

$config['action'] = Array(
  'type'  => 'inputHidden',
  'value' => 'submitmodify'
);

$config['fs1'] = array(
  'type'   => 'fieldset',
  'legend' => $l('categories', 'modify_title'),
  'prefix' => '<span class="legendsubtitle">' . $l('categories', 'modify_subtitle') . '</span>',
);
