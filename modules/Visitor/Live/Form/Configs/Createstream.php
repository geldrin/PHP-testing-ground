<?php

$config = array(

  'action' => array(
    'type'  => 'inputHidden',
    'value' => 'submitcreatestream'
  ),
  
  'feed' => array(
    'type'  => 'inputHidden',
    'value' => $this->application->getNumericParameter('feed'),
  ),
  
  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('live', 'createstream_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('live', 'createstream_subtitle') . '</span>',
  ),
  
  'name' => array(
    'displayname' => $l('live', 'streamname'),
    'type'        => 'inputText',
    'validation'  => array(
      array(
        'type'     => 'string',
        'required' => true,
        'minimum'  => 2,
        'maximum'  => 512,
      ),
    ),
  ),
  
  'streamurl' => array(
    'displayname' => $l('live', 'streamurl'),
    'type'        => 'inputText',
    'value'       => $this->bootstrap->config['wowza']['liveingressurl'],
    'validation'  => array(
      array(
        'type'    => 'string',
        'minimum' => 2,
        'help'    => $l('live', 'streamurlhelp'),
        'anddepend' => Array( 
          Array(
            'js'  => '<FORM.feedtype> != "flash"',
            'php' => '<FORM.feedtype> != "flash"',
          )
        ),
      ),
    ),
  ),
  
  'keycode' => array(
    'displayname' => $l('live', 'keycode'),
    'postfix'     => '<div class="smallinfo">' . $l('live', 'keycode_postfix') . '</div>',
    'type'        => 'inputText',
    'value'       => $this->streamModel->generateUniqueKeycode(),
    'validation'  => array(
    ),
  ),
  
  'aspectratio' => array(
    'displayname' => $l('live', 'streamaspectratio'),
    'type'        => 'inputRadio',
    'values'      => $l->getLov('aspectratios'),
    'value'       => '16:9',
    'validation'  => array(
    ),
  ),
  
  'contenturl' => array(
    'displayname' => $l('live', 'contentstreamurl'),
    'type'        => 'inputText',
    'value'       => $this->bootstrap->config['wowza']['liveingressurl'],
    'validation'  => array(
      array(
        'type'    => 'string',
        'minimum' => 2,
        'help'    => $l('live', 'streamurlhelp'),
        'anddepend' => Array( 
          Array(
            'js'  => '<FORM.feedtype> != "flash"',
            'php' => '<FORM.feedtype> != "flash"',
          )
        ),
      ),
    ),
  ),
  
  'contentkeycode' => array(
    'displayname' => $l('live', 'contentkeycode'),
    'postfix'     => '<div class="smallinfo">' . $l('live', 'keycode_postfix') . '</div>',
    'type'        => 'inputText',
    'value'       => $this->streamModel->generateUniqueKeycode(),
    'validation'  => array(
    ),
  ),
  
  'contentaspectratio' => array(
    'displayname' => $l('live', 'contentaspectratio'),
    'type'        => 'inputRadio',
    'values'      => $l->getLov('aspectratios'),
    'value'       => '16:9',
    'validation'  => array(
    ),
  ),
  
  'feedtype' => array(
    'displayname' => $l('live', 'streamfeedtype'),
    'type'        => 'select',
    'values'      => $l->getLov('feedtypes'),
    'value'       => '',
    'html'        => 'data-isexternal="' . $this->feedModel->row['isexternal'] . '"',
  ),
  
);

if ( !$this->feedModel->row['isexternal'] )
  unset( $config['keycode'], $config['contentkeycode'] );

if ( $this->feedModel->row['numberofstreams'] == 1 )
  unset( $config['contenturl'], $config['contentkeycode'] );
