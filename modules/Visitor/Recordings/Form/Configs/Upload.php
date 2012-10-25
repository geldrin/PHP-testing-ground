<?php

$config = Array(
  
  'action' => Array(
    'type'  => 'inputHidden',
    'value' => 'submitupload'
  ),
  
  // APC uploadprogress-hez a suffix
  'uploadid' => Array(
    'type'  => 'inputHidden',
    'value' => session_id() . mt_rand(),
  ),
  
  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('recordings', 'upload_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('recordings', 'upload_subtitle') . '</span>',
  ),
  
  'videolanguage' => array(
    'type'        => 'select',
    'displayname' => $l('recordings', 'language'),
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
  
  'file' => Array(
    'type'        => 'inputFile',
    'displayname' => $l('recordings', 'file'),
    'validation'  => Array(
      Array(
        'type'       => 'file',
        'required'   => true,
        'help'       => $l('recordings', 'file_help'),
        'extensions' => Array(
          'wmv', 'avi', 'mov', 'flv', 'mp4', 'asf', 'mp3', 'flac',
          'ogg', 'wav', 'wma', 'mpg', 'mpeg', 'ogm', 'f4v', 'm4v',
        ),
      ),
    ),
  ),
  
  'customhtml' => array(
    'type' => 'text',
    'rowlayout' => '
      <tr id="uploadrow" style="display:none;">
        <td class="elementcolumn" colspan="2">
          <iframe id="uploadframe" name="uploadframe" frameborder="0" border="0" src="" scrolling="no" scrollbar="no" width="0" height="0"></iframe>
          %element%
        </td
      </tr>
    ',
    'value' => '
      <div id="videouploadprogress">
        <div class="progresswrap green">
          <div class="progressname ellipsize"></div>
          <div class="progressspeed"></div>
          <div class="clear"></div>
          <div class="progressbar"></div>
          <div class="progressstatus">' .
            $l('', 'swfupload_uploading') .
            ' <img src="' . $this->controller->toSmarty['STATIC_URI'] . 'images/spinner.gif"/>' .
          '</div>
          <div class="progresstime"></div>
        </div>
      </div>
    ',
  ),
  
);
