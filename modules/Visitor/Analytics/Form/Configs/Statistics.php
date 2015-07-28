<?php

include_once( $this->bootstrap->config['libpath'] . 'clonefish/constants.php');
$language = \Springboard\Language::get();

$config = array(
  
  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('analytics', 'statistics_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('analytics', 'statistics_subtitle') . '</span>',
  ),
  
  'action' => array(
    'type'  => 'inputHidden',
    'value' => 'submitstatistics',
  ),

  'forward' => array(
    'type'  => 'inputHidden',
    'value' => ( $this->application->getParameter('forward') ?: '' )
  ),

  'datefrom' => array(
    'displayname' => $l('analytics', 'datefrom') . '<span class="required">*</span>',
    'type'        => 'inputText',
    'html'        => 'class="inputtext inputbackground clearonclick datetimepicker margin"',
    'value'       => date('Y-m-01 00:00'),
    'validation'  => array(
      array(
        'type'     => 'date',
        'required' => true,
        'format'   => 'YYYY-MM-DD hh:mm',
      ),
    ),
  ),

  'dateto' => array(
    'displayname' => $l('analytics', 'dateto') . '<span class="required">*</span>',
    'type'        => 'inputText',
    'html'        => 'class="inputtext inputbackground clearonclick datetimepicker margin"',
    'value'       => date('Y-m-t 23:59'),
    'validation'  => array(
      array(
        'type'     => 'date',
        'required' => true,
        'format'   => 'YYYY-MM-DD hh:mm',
      ),
    ),
  ),

  'email' => array(
    'displayname' => $l('analytics', 'accreditedrecordings_email'),
    'type'        => 'inputText',
    'validation'  => array(
      array(
        'type'     => 'string',
        'required' => false,
        'regexp'   => CF_EMAIL,
        'help'     => $l('users', 'emailhelp')
      ),
    ),
  ),

  'completed' => array(
    'displayname' => $l('analytics', 'accreditedrecordings_completed'),
    'type'        => 'inputCheckbox',
    'itemlayout'  => $this->checkboxitemlayout,
    'onvalue'     => 1,
    'offvalue'    => 0,
    'value'       => 1,
    'validation'  => array(
    ),
  ),
  
);
