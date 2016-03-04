<?php
include('Create.php');

$config['action']['value'] = 'submitmodify';

$config['indexphotofilename'] = array(
  'type'        => 'inputradio',
  'displayname' => $l('recordings', 'modifyindexphoto_select'),
  'itemlayout'  => '<div class="changeindexphotoitem">%radio% %label%</div>',
  'validation'  => array(
    Array( 'type' => 'required' )
  )
);
$config['departments[]']['valuesql'] = "
  SELECT departmentid
  FROM access
  WHERE
    channelid = " . $this->application->getNumericParameter('id') . " AND
    departmentid IS NOT NULL
";
$config['groups[]']['valuesql']        = "
  SELECT groupid
  FROM access
  WHERE
    channelid = " . $this->application->getNumericParameter('id') . " AND
    groupid IS NOT NULL
";

unset( $config['parent'] );
$recordings = $this->channelModel->getRecordingsIndexphotos();
$staticuri  = $this->controller->organization['staticuri'] . 'files/';

foreach ( $recordings as $recording ) {

  $config['indexphotofilename']['values'][ $recording['indexphotofilename'] ] =
    '<img ' .
    'title="' .
    htmlspecialchars( $recording['title'], ENT_QUOTES, $this->application->config['charset'] ) . '" ' .
    'src="' . $staticuri . $recording['indexphotofilename'] . '" />';
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

$config['accesstype']['validation'] = array(
  array(
    'type' => 'custom',
    'php'  => 'true',
    'help' => '',
    'js'   =>
      'checkAccessChanged()' .
      '? confirm(' . json_encode( $l('live', 'accesstypechange') ) . '): true'
    ,
  ),
);
