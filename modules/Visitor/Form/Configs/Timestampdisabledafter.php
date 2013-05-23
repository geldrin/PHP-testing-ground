<?php
if ( !isset( $user ) )
  $user = $this->bootstrap->getSession('user');

$config['needtimestampdisabledafter'] = array(
  'displayname' => $l('users', 'needtimestampdisabledafter'),
  'type'        => 'inputRadio',
  'value'       => '0',
  'values'      => $l->getLov('yesno'),
);

$config['timestampdisabledafter'] = array(
  'displayname' => $l('users', 'timestampdisabledafter'),
  'type'        => 'inputText',
  'value'       => date('Y-m-d 23:59', strtotime('+1 month')),
  'html'        =>
    'class="inputtext inputbackground clearonclick datetimepicker margin"' .
    'data-dateyearrange="' . date('Y') . ':' . ( date('Y') + 10 ) . '"' .
    'data-datefrom="' . date('Y-m-d') . '"'
  ,
  'postfix'     => '
    <br/><a href="#" class="presettime" data-date="' . date('Y-m-d 23:59', strtotime('+1 months') ) . '">' . $l('users', 'disable_month') . '</a> |
    <a href="#" class="presettime" data-date="' . date('Y-m-d 23:59', strtotime('+3 months') ) . '">' . $l('users', 'disable_quarteryear') . '</a> |
    <a href="#" class="presettime" data-date="' . date('Y-m-d 23:59', strtotime('+6 months') ) . '">' . $l('users', 'disable_halfyear') . '</a> |
    <a href="#" class="presettime" data-date="' . date('Y-m-d 23:59', strtotime('+1 year') ) . '">' . $l('users', 'disable_year') . '</a>
  ',
  'validation'  => array(
    array(
      'type'     => 'date',
      'required' => false,
      'format'   => 'YYYY-MM-DD hh:mm',
      'minimum'  => time(),
      'help'     => $l('users', 'timestampdisabledafter_help'),
    ),
  ),
);
