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
