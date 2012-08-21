<?php
$organizationid = $this->controller->organization['id'];
$config = array(
  
  'action' => array(
    'type'  => 'inputHidden',
    'value' => 'submitmodifycontributors'
  ),
  
  'id' => array(
    'type'  => 'inputHidden',
    'value' => $this->application->getNumericParameter('id'),
  ),
  
  'forward' => array(
    'type'  => 'inputHidden',
    'value' => $this->application->getParameter('forward'),
  ),
  
  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('recordings', 'contributors_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('recordings', 'contributors_subtitle') . '</span>',
  ),
  
  'text' => array(
    'type'  => 'text',
    'value' => '',
  ),
  
);

// TODO contributor lista, contributor kereses es onnan link hozzaadashoz, fancyboxal
