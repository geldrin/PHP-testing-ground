<?php
include('Createnews.php');

$config['action'] = Array(
  'type'  => 'inputHidden',
  'value' => 'submitmodifynews'
);

$config['id'] = Array(
  'type'  => 'inputHidden',
  'value' => $this->application->getNumericParameter('id'),
);

$config['fs1'] = array(
  'type'   => 'fieldset',
  'legend' => $l('organizations', 'modifynews_title'),
  'prefix' => '<span class="legendsubtitle">' . $l('organizations', 'modifynews_subtitle') . '</span>',
);
