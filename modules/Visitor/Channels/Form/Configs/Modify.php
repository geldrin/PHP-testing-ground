<?php
include('Create.php');

$config['action']['value'] = 'submitmodify';
$config['parent']['value'] = '';

$config['id']              = Array(
  'type'  => 'inputHidden',
  'value' => $this->application->getNumericParameter('id'),
);

$config['indexphotofilename'] = Array(
  'type'        => 'inputradio',
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
);

if ( $this->channelroot )
  $channelid = $this->channelroot['id'];
else
  $channelid = $this->application->getNumericParameter('id');

$config['departments[]']['valuesql'] = "
  SELECT departmentid
  FROM access
  WHERE
    channelid = '$channelid' AND
    departmentid IS NOT NULL
";
$config['groups[]']['valuesql'] = "
  SELECT groupid
  FROM access
  WHERE
    channelid = '$channelid' AND
    groupid IS NOT NULL
";

$recordings = $this->channelModel->getRecordingsIndexphotos();
$img        = '<img title="%s" src="' . $this->bootstrap->staticuri . 'files/%s"/>';

foreach ( $recordings as $recording ) {

  $config['indexphotofilename']['values'][ $recording['indexphotofilename'] ] = sprintf(
    $img,
    htmlspecialchars( $recording['title'], ENT_QUOTES, $this->application->config['charset'] ),
    $recording['indexphotofilename']
  );
  
}

if ( !count( @$config['indexphotofilename']['values'] ) )
  unset( $config['indexphotofilename'] );

if (
     $this->parentchannelModel and $this->parentchannelModel->id
   ) {
  
  $config['accesstype']['postfix']    = $l('channels', 'accesstype_disabled');
  $config['departments[]']['postfix'] = '';
  $config['accesstype']['html']       = 'disabled="disabled"';
  $config['departments[]']['html']    = 'disabled="disabled"';
  $config['groups[]']['html']         = 'disabled="disabled"';
  
}
