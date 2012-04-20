<?php

$config = Array(
  
  'action' => Array(
    'type'  => 'inputHidden',
    'value' => 'submituploadcontent'
  ),
  
  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('recordings', 'uploadcontent_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('recordings', 'uploadcontent_subtitle') . '</span>',
  ),
  
  'id' => array(
    'type'  => 'inputHidden',
    'value' => $this->application->getNumericParameter('id'),
  ),
  
  'videolanguage' => array(
    'type'        => 'select',
    'displayname' => $l('recordings', 'language'),
  ),
  
  'isinterlaced' => array(
    'type'        => 'inputRadio',
    'displayname' => $l('recordings', 'isinterlaced'),
    'value'       => '0',
    'values'      => array(
      '0' => $l('recordings', 'isinterlaced_normal'),
      '1' => $l('recordings', 'isinterlaced_interlaced'),
    ),
  ),
  
  'customhtml' => array(
    'type' => 'text',
    'rowlayout' => '
      <tr id="uploadrow">
        <td class="elementcolumn" colspan="2">
          %element%
        </td
      </tr>
    ',
    'value' => '
      <div id="videobrowsecontainer">
        <span id="videobrowse">' . $l('recordings', 'uploadnoflash') . '</span>
      </div>
      <div id="videouploadprogress" style="display:none;">
        <div class="progresswrap">
          <div class="progressname"></div>
          <div class="progressspeed"></div>
          <div class="clear"></div>
          <div class="progressbar"></div>
          <div class="progressstatus"></div>
          <div class="progresstime"></div>
        </div>
      </div>
    ',
  ),
  
  'file' => Array(
    'type'       => 'inputFile',
    'display'    => false,
    'validation' => Array(
      Array(
        'type'       => 'file', 
        'extensions' => Array(
          'wmv', 'avi', 'mov', 'flv', 'mp4', 'asf', 'mp3', 'flac',
          'ogg', 'wav', 'wma', 'mpg', 'mpeg', 'ogm', 'f4v', 'm4v',
        ),
        'required'   => false,
      ),
    ),
  ),
  
);
