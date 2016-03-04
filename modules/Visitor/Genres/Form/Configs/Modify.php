<?php
include('Create.php');
$config['action']['value'] = 'submitmodify';

$config['id']              = Array(
  'type'  => 'inputHidden',
  'value' => $this->application->getNumericParameter('id'),
);
