<?php

$config = array(

  'action' => array(
    'type'  => 'inputHidden',
    'value' => 'submitmodify'
  ),
  
  'parent' => array(
    'type'  => 'inputHidden',
    'value' => '',
  ),

  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('channels', 'modify_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('channels', 'modify_subtitle') . '</span>',
  ),
  
  'title' => array(
    'displayname' => $l('channels', 'nameoriginal'),
    'type'        => 'inputText',
    'validation'  => array(
      array( 'type' => 'required' ),
      array(
        'type' => 'string',
        'minimum' => 4,
        'maximum' => 512,
      ),
    ),
  ),
  
  'subtitle' => array(
    'displayname' => $l('channels', 'subtitleoriginal'),
    'type'        => 'inputText',
    'validation'  => array(
      array(
        'type'     => 'string',
        'minimum'  => 4,
        'maximum'  => 512,
        'required' => false,
      ),
    ),
  ),
  
  'description' => array(
    'displayname' => $l('channels', 'descriptionoriginal'),
    'type'        => 'textarea',
    'validation'  => array(
      array(
        'type' => 'string',
        'minimum'  => 4,
        'required' => false,
      ),
    ),
  ),
  
  'channeltypeid' => array(
    'displayname' => $l('channels', 'channeltype'),
    'type'        => 'selectDynamic',
    'sql'         => "
      SELECT ct.id, s.value
      FROM channel_types AS ct, strings AS s
      WHERE
        s.translationof = ct.name_stringid AND
        s.language = '" . \Springboard\Language::get() . "' AND
        ct.isfavorite = 0 AND
        %s
      ORDER BY ct.weight
    ",
    'treeid'      => 'id',
    'treestart'   => '0',
    'treeparent'  => 'ct.parentid',
    'validation'  => array(
      array( 'type' => 'required' ),
      array(
        'type'     => 'number',
        'minimum'  => 1,
        'help'     => $l('channels', 'channeltypehelp'),
      ),
    ),
  ),
  
  'ispublic' => array(
    'displayname' => $l('channels', 'ispublic'),
    'type'        => 'inputCheckbox',
    'onvalue'     => 1,
    'offvalue'    => 0,
    'value'       => 1,
    'validation'  => array(
    ),
  ),
  
  'indexphotofilename' => Array(
    'type' => 'inputradio',
    'displayname' => $l('recordings', 'modifyindexphoto_select'),
    'itemlayout' => '<div class="changeindexphotoitem">%radio% %label%</div>',
    'validation' => Array(
      Array( 'type' => 'required' )
    )
  ),
  
);

$recordings = $this->channelModel->getRecordingsIndexphotos();

foreach ( $recordings as $recording ) {

  $config['indexphotofilename']['values'][ $recording['indexphotofilename'] ] =
    '<img ' .
    'title="' .
    htmlspecialchars( $recording['title'], ENT_QUOTES, $this->application->config['charset'] ) . '" ' .
    'src="' . $this->application->config['staticuri'] . 'files/' . $recording['indexphotofilename'] . '" />';
  ;
  
}

if ( !count( @$config['indexphotofilename']['values'] ) )
  unset( $config['indexphotofilename'] );

if ( $this->channelModel->row['parentid'] ) {
  
  $parent = $this->controller->modelIDCheck('channels', $this->channelModel->row['parentid'] );
  if ( !$parent->row['ispublic'] ) {
    
    $config['ispublic']['html']    = 'disabled="disabled"';
    $config['ispublic']['postfix'] = $l('channels', 'ispublic_disabled');
    
  }
  
}
