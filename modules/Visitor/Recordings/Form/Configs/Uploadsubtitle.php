<?php

$config = Array(
  
  'action' => Array(
    'type'  => 'inputHidden',
    'value' => 'submituploadsubtitle'
  ),
  
  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('recordings', 'uploadsubtitle_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('recordings', 'uploadsubtitle_subtitle') . '</span>',
  ),

  'recordingid' => Array(
    'type'       => 'inputHidden',
    'value'      => $this->application->getNumericParameter('id'),
    'validation' => Array(
      Array( 'type' => 'number', 'minimum' => 1 )
    ),
  ),
  
  'languageid' => array(
    'type'        => 'select',
    'displayname' => $l('recordings', 'subtitlelanguage'),
    'values'      => $this->bootstrap->getModel('languages')->getAssoc('id', 'name'),
  ),
  
  'subtitle' => Array(
    'type'        => 'inputFile',
    'displayname' => $l('recordings', 'subtitle_file'),
    'binaryvalue' => true,
    'validation'  => Array(
      Array(
        'type'       => 'file',
        'extensions' => Array( 'srt' ),
        'required'   => true,
        'help'       => $l('recordings', 'subtitlehelp'),
      ),
      Array(
        'type' => 'subtitle',
        'help' => $l('recordings', 'subtitlehelp'),
      ),
    ),
  ),
  
  'isdefault' => array(
    'type'        => 'inputRadio',
    'displayname' => $l('recordings', 'subtitle_isdefault'),
    'values'      => $l->getLov('noyes'),
    'value'       => 0,
  ),
  
);
