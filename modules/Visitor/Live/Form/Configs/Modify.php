<?php
include('Create.php');

$config['action']['value'] = 'submitmodify';
$config['fs1']['legend']   = $l('live', 'modify_title');
$config['fs1']['prefix']   = '<span class="legendsubtitle"></span>';

$config['indexphotofilename'] = array(
  'type'        => 'inputradio',
  'displayname' => $l('recordings', 'modifyindexphoto_select'),
  'itemlayout'  => '<div class="changeindexphotoitem">%radio% %label%</div>',
  'validation'  => array(
    Array( 'type' => 'required' )
  )
);
$config['organizations[]']['valuesql'] = "
  SELECT organizationid
  FROM access
  WHERE channelid = " . $this->application->getNumericParameter('id')
;
$config['groups[]']['valuesql']        = "
  SELECT groupid
  FROM access
  WHERE channelid = " . $this->application->getNumericParameter('id')
;

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
