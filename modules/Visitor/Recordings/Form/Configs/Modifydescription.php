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
    'type'        => 'inputText',
    'html'        =>
      'class="inputtext inputbackground clearonclick datetimepicker margin"'
    ,
    'validation'  => array(
      array(
        'type'     => 'date',
        'required' => true,
        'format'   => 'YYYY-MM-DD hh:mm',
        'maximum'  => time(),
        'help'     => $l('recordings', 'recordedtimestamp_help'),
      ),
    ),
  ),

  'description' => array(
    'displayname' => $l('recordings', 'description'),
    'type'        => 'textarea',
    'validation'  => array(
      array(
        'type' => 'string',
        'minimum'  => 4,
        'required' => false,
      ),
    ),
  ),

  'copyright' => array(
    'displayname' => $l('recordings', 'copyright'),
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
