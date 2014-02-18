<?php
$config = array(
  
  'action' => array(
    'type'  => 'inputHidden',
    'value' => 'togglefeatured'
  ),
  
  'recordingid' => array(
    'type' => 'inputHidden',
  ),
  
  'term' => array(
    'type'        => 'inputText',
    'displayname' => $l('recordings', 'searchrecording'),
    'rowlayout'   => '
      <tr>
        <td class="labelcolumn"><label for="%id%">%displayname%</label></td>
        <td class="elementcolumn">%prefix%%element%%postfix%%errordiv%</td>
      </tr>
    ',
  ),
  
);
