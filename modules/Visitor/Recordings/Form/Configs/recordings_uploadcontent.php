<?php

$config = Array(
  
  'target' => Array(
    'type'  => 'inputHidden',
    'value' => 'submituploadcontent'
  ),
  
  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => l('recordings', 'uploadcontent_title'),
    'prefix' => '<span class="legendsubtitle">' . l('recordings', 'uploadcontent_subtitle') . '</span>',
  ),
  
  'elementid' => array(
    'type'  => 'inputHidden',
    'value' => @$_REQUEST['elementid'],
  ),
  
  'isinterlaced' => array(
    'type'        => 'inputRadio',
    'displayname' => l('recordings', 'isinterlaced'),
    'value'       => '0',
    'values'      => array(
      '0' => l('recordings', 'isinterlaced_normal'),
      '1' => l('recordings', 'isinterlaced_interlaced'),
    ),
  ),
  
  'customhtml' => array(
    'type' => 'text',
    'value' => '
      <div id="videouploadprogress" style="display:none;">
        <div class="progresswrap">
          <div class="progressname">
          </div>
          <div class="progressspeed">
          </div>
          <div class="clear"></div>
          <div class="progressbar">
          </div>
          <div class="progressstatus">
          </div>
          <div class="progresstime">
          </div>
        </div>
      </div>
      <div id="videobrowsecontainer">
        <span id="videobrowse">
        </span>
      </div>',
  ),
  
  'file' => Array(
    'type'       => 'inputFile',
    'validation' => Array(
      Array(
        'type'       => 'file', 
        'extensions' => Array(
          'wmv', 'avi', 'mov', 'flv', 'mp4', 'asf', 'mpg', 'mpeg', 'ogm',
          'f4v', 'm4v',
        ),
        'required'   => true,
      ),
    ),
  ),
  
);

?>