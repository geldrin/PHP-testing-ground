<?php
$config = array(

  'action' => array(
    'type'  => 'inputHidden',
    'value' => 'submitmodifybasics'
  ),

  'id' => array(
    'type'  => 'inputHidden',
    'value' => $this->application->getNumericParameter('id'),
  ),
  
  'forward' => array(
    'type'  => 'inputHidden',
    'value' => $this->application->getParameter('forward'),
  ),
  
  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('recordings', 'basics_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('recordings', 'basics_subtitle') . '</span>',
  ),
  
  'languageid' => array(
    'type'        => 'select',
    'displayname' => $l('recordings', 'language'),
    'values'      => $this->bootstrap->getModel('languages')->getAssoc('id', 'name'),
  ),
  
  'title' => array(
    'displayname' => $l('recordings', 'title'),
    'type'        => 'inputText',
    'validation'  => array(
      array(
        'type' => 'string',
        'minimum'  => 4,
        'maximum'  => 512,
      ),
    ),
  ),
  
  'subtitle' => array(
    'displayname' => $l('recordings', 'subtitle'),
    'type'        => 'inputText',
    'validation'  => array(
      array(
        'type' => 'string',
        'minimum'  => 4,
        'required' => false,
      ),
    ),
  ),
  
  'slideonright' => array(
    'displayname' => $l('recordings', 'slideonright'),
    'type'        => 'inputRadio',
    'value'       => 1,
    'itemlayout'  => '%radio% %label%<br />',
    'values'      => array(
      0 => $l('recordings', 'slideleft'),
      1 => $l('recordings', 'slideright'),
    ),
  ),
  
  'indexphotofilename' => Array(
    'type' => 'inputradio',
    'displayname' => $l('recordings', 'modifyindexphoto_select'),
    'rowlayout'   => '
      <tr>
        <td class="elementcolumn" colspan="2">
          %displayname%<br/><br/>
          %element%
        </td
      </tr>
    ',
    'itemlayout' => '<div class="changeindexphotoitem">%radio% %label%</div>',
    'validation' => Array(
      Array( 'type' => 'required' )
    )
  ),
  
);

if ( !$this->recordingsModel->row['isintrooutro'] ) {
  
  $haveindexphotos = false;
  $staticuri       = $this->controller->organization['staticuri'] . 'files/';
  
  for ( $i = 1; $i <= $this->recordingsModel->row['numberofindexphotos']; $i++ ) {
    
    $haveindexphotos = true;
    $filename = preg_replace(
      '/_\d+\.jpg$/',
      '_' . $i . '.jpg',
      $this->recordingsModel->row['indexphotofilename']
    );
    
    $config['indexphotofilename']['values'][ $filename ] = 
      '<img src="' . $staticuri . $filename . '" />';
    ;
    
  }
  
  if ( !$haveindexphotos )
    unset( $config['indexphotofilename'] );
  
  if ( $this->recordingsModel->getIntroOutroCount( $this->controller->organization['id'] ) ) {
    
    $recordings =
      $this->recordingsModel->getIntroOutroAssoc( $this->controller->organization['id'] )
    ;
    
    $introoutro = array(
      'introrecordingid' => array(
        'displayname' => $l('recordings', 'introrecordingid'),
        'type'        => 'select',
        'values'      => array('' => $l('recordings', 'nointro') ) + $recordings,
      ),
      'outrorecordingid' => array(
        'displayname' => $l('recordings', 'outrorecordingid'),
        'type'        => 'select',
        'values'      => array('' => $l('recordings', 'nooutro') ) + $recordings,
      ),
    );
    
    $config = \Springboard\Tools::insertAfterKey( $config, $introoutro, 'slideonright' );
    
  }
  
} else
  unset(
    $config['languageid'],
    $config['subtitle'],
    $config['slideonright'],
    $config['indexphotofilename']
  );