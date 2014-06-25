<?php

$config = array(
  
  'action' => array(
    'type'     => 'inputHidden',
    'value'    => 'submitnewcomment',
    'readonly' => true,
  ),
  
  'replyto' => array(
    'type'  => 'inputHidden',
    'value' => $this->application->getNumericParameter('replyto'),
  ),
  
  'recordingid' => array(
    'type'  => 'inputHidden',
    'value' => $this->application->getNumericParameter('id'),
  ),
  
  'text' => array(
    'displayname' => $l('recordings', 'yourcomment'),
    'type'        => 'textarea',
    'rowlayout'   => '
      <tr %errorstyle%>
        <td class="labelcolumn">
          <label for="%id%">%displayname%</label>
        </td>
      </tr>
      <tr>
        <td class="elementcolumn">%prefix%%element%%postfix%%errordiv%</td>
      </tr>
    ',
    'validation'  => array(
      array(
        'type' => 'string',
        'minimum' => 3,
        'maximum' => 1000,
      ),
    ),
  ),
  
);
