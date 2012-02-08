<?php

$config = array(

  'action' => array(
    'type'  => 'inputHidden',
    'value' => 'submitmodifydescription'
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
    'legend' => $l('recordings', 'description_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('recordings', 'description_subtitle') . '</span>',
  ),
  
  'recordedtimestamp' => array(
    'displayname' => $l('recordings', 'recordedtimestamp'),
    'type'        => 'selectDate',
    'layout'      => '%Y %M %D %h %m %s',
    'format'      => '%Y-%M-%D %h:%m:%s',
    'postfix'     => '<div class="datepicker"></div>',
    'yearfrom'    => false, // current year only
    //'yearuntil'   => false, // current year only
    'validation'  => array(
    ),
  ),
  
  'descriptionoriginal' => array(
    'displayname' => $l('recordings', 'descriptionoriginal'),
    'type'        => 'textarea',
    'validation'  => array(
      array(
        'type' => 'string',
        'minimum'  => 4,
        'required' => false,
      ),
    ),
  ),
  
  'descriptionenglish' => array(
    'displayname' => $l('recordings', 'descriptionenglish'),
    'type'        => 'textarea',
    'validation'  => array(
      array(
        'type' => 'string',
        'minimum'  => 4,
        'required' => false,
      ),
    ),
  ),
  
  'copyrightoriginal' => array(
    'displayname' => $l('recordings', 'copyrightoriginal'),
    'type'        => 'textarea',
    'validation'  => array(
      array(
        'type' => 'string',
        'minimum'  => 4,
        'required' => false,
      ),
    ),
  ),
  
  'copyrightenglish' => array(
    'displayname' => $l('recordings', 'copyrightenglish'),
    'type'        => 'textarea',
    'validation'  => array(
      array(
        'type' => 'string',
        'minimum'  => 4,
        'required' => false,
      ),
    ),
  ),
  
  'technicalnote' => array(
    'displayname' => $l('recordings', 'technicalnote'),
    'type'        => 'textarea',
    'validation'  => array(
      array(
        'type' => 'string',
        'minimum'  => 4,
        'required' => false,
      ),
    ),
  ),
  
);
