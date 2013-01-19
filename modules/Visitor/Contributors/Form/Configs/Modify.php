<?php

include('Create.php');
include_once( $this->bootstrap->config['templatepath'] . 'Plugins/modifier.nameformat.php');
$config['action'] = Array(
  'type'  => 'inputHidden',
  'value' => 'submitmodify'
);

$config['fs1'] = array(
  'type'   => 'fieldset',
  'legend' => $l('contributors', 'modify_title'),
  'prefix' =>
    '<span class="legendsubtitle">' .
      smarty_modifier_nameformat( $this->contributorModel->row ) .
    '</span>'
  ,
);

$config['crid'] = array(
  'type'     => 'inputHidden',
  'value'    => $this->application->getNumericParameter('crid'),
  'readonly' => true,
);
