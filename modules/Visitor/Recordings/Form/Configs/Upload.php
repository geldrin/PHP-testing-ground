<?php

$config = Array(

  'action' => Array(
    'type'  => 'inputHidden',
    'value' => 'submitupload'
  ),

  'channelid' => Array(
    'type'  => 'inputHidden',
    'value' => $this->application->getNumericParameter('channelid'),
  ),

  'fs1' => array(
    'type'   => 'fieldset',
    'prefix' => '<span class="legendsubtitle">' . $l('recordings', 'upload_subtitle') . '</span>',
  ),

  'videolanguage' => array(
    'type'        => 'select',
    'displayname' => $l('recordings', 'language'),
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

  'advanced' => array(
    'type'   => 'text',
    'rowlayout' => '
      </tr>
      <tr>
        <td colspan="2" id="advancedrow">
          <span class="label"></span>
          <div class="element">
            <a href="#" >%element%</a>
          </div>
        </td>
      </tr>
    ',
    'value' => $l('recordings', 'upload_advanced'),
  ),

  'encodinggroupid' => array(
    'type'        => 'selectDynamic',
    'displayname' => $l('recordings', 'upload_encodinggroupid'),
    'html'        => 'class="advanceditem"',
    'sql'         => "
      SELECT id, name
      FROM encoding_groups
      WHERE
        islegacy = '0' AND
        disabled = '0'
      ORDER BY `default` DESC, name
    ",
    'value' => $this->controller->organization['defaultencodingprofilegroupid'],
    'rowlayout' => '
      <tr class="hidden">
        <td class="labelcolumn">
          <label for="%id%">%displayname%</label>
        </td>
        <td class="elementcolumn">%prefix%%element%%postfix%%errordiv%</td>
      </tr>
    '
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
      <td colspan="2" id="uploadrow" class="formrow">
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
        <a href="#" id="uploadbrowse" class="submitbutton">' . $l('recordings', 'addfiles') . '</a>
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

if (
     \Model\Userroles::userHasPrivilege(
       null,
       'recordings_createintrooutro',
       'or',
       'iseditor', 'isadmin', 'isclientadmin'
     )
   ) {

  $introoutro = array(
    'isintrooutro' => array(
      'displayname' => $l('recordings', 'isintrooutro'),
      'type'        => 'inputRadio',
      'values'      => $l->getLov('isintrooutro'),
      'value'       => 0,
    )
  );

  $config = \Springboard\Tools::insertAfterKey( $config, $introoutro, 'tos' );

} else {

  $config['isintrooutro'] = array(
    'type'     => 'inputHidden',
    'value'    => 0,
    'readonly' => true,
  );

}

include( $this->bootstrap->config['modulepath'] . 'Visitor/Recordings/Availableuploads.php' );
