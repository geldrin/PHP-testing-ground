<?php
$skipindexphotounset = true;
include('Create.php');
$this->bootstrap->includeTemplatePlugin('nameformat');
$config['action'] = Array(
  'type'  => 'inputHidden',
  'value' => 'submitmodify'
);

$config['fs1'] = array(
  'type'   => 'fieldset',
  'legend' => $l('contributors', 'modify_title'),
  'prefix' =>
    '<span class="legendsubtitle">' .
      smarty_modifier_nameformat( $this->contributorModel->row ) .
    '</span>'
  ,
);

$config['crid'] = array(
  'type'     => 'inputHidden',
  'value'    => $this->application->getNumericParameter('crid'),
  'readonly' => true,
);

$config['indexphotofilename']['value'] = $this->contributorModel->getCurrentIndexPhoto();
$staticuri = $this->controller->organization['staticuri'] . 'files/';
foreach( $this->contributorModel->getIndexPhotos() as $photo ) {
  
  $config['indexphotofilename']['values'][ $photo['indexphotofilename'] ] =
    '<img src="' . $staticuri . $photo['indexphotofilename'] . '" />';
  ;
  
}

if ( empty( $config['indexphotofilename']['values'] ) )
  unset( $config['indexphotofilename'] );
