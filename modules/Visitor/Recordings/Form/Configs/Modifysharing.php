<?php

$config = array(

  'action' => array(
    'type'  => 'inputHidden',
    'value' => 'submitmodifysharing'
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
    'legend' => $l('recordings', 'sharing_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('recordings', 'sharing_subtitle') . '</span>',
  ),
  
);

include( $this->bootstrap->config['modulepath'] . 'Visitor/Form/Configs/Accesstype.php');

$config = array_merge( $config, array(
  'wanttimelimit' => array(
    'displayname' => $l('recordings', 'wanttimelimit'),
    'type'        => 'inputRadio',
    'value'       => 0,
    'values'      => $l->getLov('noyes'),
  ),
  
  'visiblefrom' => array(
    'displayname' => $l('recordings', 'visiblefrom'),
    'type'        => 'selectDate',
    'postfix'     => '<div class="datepicker"></div>',
    'format'      => '%Y-%M-%D',
    'yearfrom'    => date('Y') + 10, // current year + 10 years
    'value'       => date('Y-m-d'),
    'validation'  => array(
    ),
  ),
  
  'visibleuntil' => array(
    'displayname' => $l('recordings', 'visibleuntil'),
    'type'        => 'selectDate',
    'postfix'     => '<div class="datepicker"></div>',
    'format'      => '%Y-%M-%D',
    'yearfrom'    => date('Y') + 10, // current year + 10 years
    //'yearuntil'   => false, // current year only
    'value'       => date('Y-m-d', strtotime('+3 months')),
    'validation'  => array(
    ),
  ),
  
  'isdownloadable' => array(
    'displayname' => $l('recordings', 'isdownloadable'),
    'type'        => 'inputRadio',
    'value'       => 1,
    'values'      => $l->getLov('noyes'),
  ),
  
  'isaudiodownloadable' => array(
    'displayname' => $l('recordings', 'isaudiodownloadable'),
    'type'        => 'inputRadio',
    'value'       => 1,
    'values'      => $l->getLov('noyes'),
  ),
  
  'isembedable' => array(
    'displayname' => $l('recordings', 'isembedable'),
    'type'        => 'inputRadio',
    'value'       => 1,
    'values'      => $l->getLov('noyes'),
  ),
  
  'ispublished' => array(
    'displayname' => $l('recordings', 'ispublished'),
    'postfix'     => '<div class="smallinfo">' . $l('recordings', 'ispublished_postfix') . '</div>',
    'type'        => 'inputRadio',
    'value'       => 0,
    'values'      => array(
      $l('recordings', 'ispublished_no'),
      $l('recordings', 'ispublished_yes'),
    ),
  ),
  
));

if ( $this->controller->organization['issecurestreamingenabled'] )
  $config['issecurestreamingforced'] = array(
    'type'        => 'inputRadio',
    'displayname' => $l('live', 'issecurestreamingforced'),
    'values'      => $l->getLov('encryption'),
    'validation'  => array(
      array('type' => 'required'),
    ),
  );
else
  $config['issecurestreamingforced'] = array(
    'type'     => 'inputHidden',
    'value'    => '0',
    'readonly' => true,
  );
