<?php

include('Create.php');

$config['action'] = Array(
  'type'  => 'inputHidden',
  'value' => 'submitmodify'
);

$config['fs1'] = array(
  'type'   => 'fieldset',
  'legend' => $l('contributors', 'modify_title'),
  'prefix' => '<span class="legendsubtitle">' . $l('contributors', 'modify_subtitle') . '</span>',
);

$config['crid'] = array(
  'type'     => 'inputHidden',
  'value'    => $this->application->getNumericParameter('crid'),
  'readonly' => true,
);
