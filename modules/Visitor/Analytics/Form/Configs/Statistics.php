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

  'type' => array(
    'displayname' => $l('analytics', 'statistics_type'),
    'type'        => 'inputRadio',
    'values'      => $l->getLov('statistics_type'),
    'value'       => 'recordings',
    'divide'      => 1,
    'divider'     => '<br/>',
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

  'dateuntil' => array(
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

  'searchrecordings[]' => array(
    'type'        => 'select',
    'displayname' => $l('analytics', 'searchrecordings'),
    'html'        => 'multiple="multiple" data-searchurl="' . $language . '/analytics/searchrecordings"',
    'rowlayout'   => '
      <tr>
        <td class="labelcolumn" colspan="2"><label for="%id%">%displayname%</label></td>
      </tr>
      <tr>
        <td class="elementcolumn" colspan="2">%prefix%%element%%postfix%%errordiv%</td>
      </tr>
    ',
  ),
  
  'searchlive[]' => array(
    'type'        => 'select',
    'displayname' => $l('analytics', 'searchlive'),
    'html'        => 'multiple="multiple" data-searchurl="' . $language . '/analytics/searchlive"',
    'rowlayout'   => '
      <tr>
        <td class="labelcolumn" colspan="2"><label for="%id%">%displayname%</label></td>
      </tr>
      <tr>
        <td class="elementcolumn" colspan="2">%prefix%%element%%postfix%%errordiv%</td>
      </tr>
    ',
  ),
  
  'searchgroups[]' => array(
    'type'        => 'select',
    'displayname' => $l('analytics', 'searchgroups'),
    'html'        => 'multiple="multiple" data-searchurl="' . $language . '/analytics/searchgroups"',
    'rowlayout'   => '
      <tr>
        <td class="labelcolumn" colspan="2"><label for="%id%">%displayname%</label></td>
      </tr>
      <tr>
        <td class="elementcolumn" colspan="2">%prefix%%element%%postfix%%errordiv%</td>
      </tr>
    ',
  ),
  
  'searchusers[]' => array(
    'type'        => 'select',
    'displayname' => $l('analytics', 'searchusers'),
    'html'        => 'multiple="multiple" data-searchurl="' . $language . '/analytics/searchusers"',
    'rowlayout'   => '
      <tr>
        <td class="labelcolumn" colspan="2"><label for="%id%">%displayname%</label></td>
      </tr>
      <tr>
        <td class="elementcolumn" colspan="2">%prefix%%element%%postfix%%errordiv%</td>
      </tr>
    ',
  ),
  
);
