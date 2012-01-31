<?php

$config = Array(
  
  'action' => Array(
    'type'  => 'inputHidden',
    'value' => 'submituploadrecording'
  ),
  
  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('recordings', 'upload_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('recordings', 'upload_subtitle') . '</span>',
  ),
  
  'recordingid' => array(
    'type'  => 'inputHidden',
    'value' => @$_REQUEST['recordingid'],
  ),
  
  'videolanguage' => array(
    'type'        => 'inputText',
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
          'wmv', 'avi', 'mov', 'flv', 'mp4', 'asf', 'mp3', 'flac',
          'ogg', 'wav', 'wma', 'mpg', 'mpeg', 'ogm', 'f4v', 'm4v',
        ),
        'required'   => true,
      ),
    ),
  ),
  
  'tos' => array(
    'displayname' => $l('', 'recordingstos'),
    'type'        => 'inputCheckbox',
    'postfix'     =>
      '<a href="' . \Springboard\Language::get() . '/contents/recordingstos' .
      '" id="termsofservice" target="_blank">' . $l('', 'recordingstospostfix') . '</a>'
    ,
    'validation'  => array(
      array(
        'type' => 'required',
        'help' => $l('', 'recordingstoshelp'),
      )
    ),
  ),
  
);
