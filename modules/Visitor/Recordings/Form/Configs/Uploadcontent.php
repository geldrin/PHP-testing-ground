<?php

$config = Array(
  
  'action' => Array(
    'type'  => 'inputHidden',
    'value' => 'submituploadcontent'
  ),

  'id' => array(
    'type'  => 'inputHidden',
    'value' => $this->application->getNumericParameter('id'),
  ),
  
  'iscontent' => array(
    'type'        => 'inputHidden',
    'value'       => '1',
  ),
  
  'tos' => array(
    'displayname' => $l('', 'recordingstos'),
    'type'        => 'inputCheckbox',
    'itemlayout'  => $this->checkboxitemlayout,
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
  
  'file' => Array(
    'type'       => 'inputFile',
    'validation' => Array(
      Array(
        'type'       => 'file', 
        'extensions' => $this->bootstrap->config['allowedextensions'],
        'required'   => true,
        'help'       => $l('recordings', 'file_help'),
      ),
    ),
  ),
  
  'customhtml' => array(
    'type' => 'text',
    'rowlayout' => '
      <td colspan="2" id="uploadrow" class="formrow" data-multiplefiles="0">
        <span class="label"></span>
        <div class="element">
          %element%
        </div>
      </td>
    ',
    'value' => '
      <div id="draganddropavailable"><span class="icon-info"></span>' . $l('recordings', 'draganddropavailable') . '</div>
      <div id="bigfilewarning"><span class="icon-alert"></span>' . $l('recordings', 'bigfilewarning') . '</div>
      <div id="uploadbrowsecontainer">
        <a href="#" id="uploadtoggle" class="submitbutton right" data-stopupload="' . $l('recordings', 'stopupload') . '">' . $l('recordings', 'startupload') . '</a>
        <a href="#" id="uploadbrowse" class="submitbutton">' . $l('recordings', 'addfile') . '</a>
      </div>
      <div id="uploadprogress">
        <div class="progresswrap green hover" style="display: none;">
          <div class="uploadactions">
            <div class="uploadremove"></div>
          </div>
          <div class="progressname"></div>
          <div class="progressspeed"></div>
          <div class="clear"></div>
          <div class="progressbar"></div>
          <div class="progressstatus"><img src="' . $this->controller->toSmarty['STATIC_URI'] . 'images/spinner.gif"/>' . $l('contents', 'upload_uploading') . '</div>
          <div class="progresstime" title="' . $l('recordings', 'estimatedtime') . '"></div>
        </div>
    ',
  ),
  
);

include( $this->bootstrap->config['modulepath'] . 'Visitor/Recordings/Availableuploads.php' );
