<?php
include('Create.php');

$config['action']['value'] = 'submitmodify';
$config['parent']['value'] = '';
$config['fs1']['legend']   = $l('channels', 'modify_title');
$config['fs1']['prefix']   =
  '<span class="legendsubtitle">' . $l('channels', 'modify_subtitle') . '</span>'
;
$config['id']              = Array(
  'type'  => 'inputHidden',
  'value' => $this->application->getNumericParameter('id'),
);

$config['indexphotofilename'] = Array(
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
);

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
