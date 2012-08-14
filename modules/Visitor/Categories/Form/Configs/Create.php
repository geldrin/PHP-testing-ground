<?php

$organizationid = $this->controller->organization['id'];
$config = Array(
  
  'action' => Array(
    'type'  => 'inputHidden',
    'value' => 'submitcreate'
  ),
  
  'organizationid' => Array(
    'type'     => 'inputHidden',
    'value'    => $organizationid,
    'readonly' => true,
  ),
  
  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('categories', 'create_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('categories', 'create_subtitle') . '</span>',
  ),
  
  'name_stringid' => Array(
    'displayname' => $l('categories', 'name'),
    'type'        => 'inputTextMultilanguage',
    'languages'   => $l->getLov('languages'),
  ),
  
  'parentid' => Array(
    'displayname' => $l('categories', 'parentid'),
    'type'        => 'selectDynamic',
    'values'      => Array( 0 => $l('categories', 'noparent') ),
    'sql'         => "
      SELECT 
        c.id, s.value
      FROM 
        categories c, strings s
      WHERE 
        c.name_stringid = s.translationof AND
        s.language = 'hu' AND
        c.organizationid = '" . $organizationid . "' AND
        %s
    ",
    'treeid'      => 'id',
    'treeparent'  => 'parentid',
    'treestart'   => '0',
    'value'       => $this->application->getNumericParameter('parentid'),
  ),
  
  'iconfilename' => Array(
    'type' => 'inputradio',
    'displayname' => $l('categories', 'icon'),
    'itemlayout' => '<div class="categoryiconitem">%radio% %label%</div>',
    'postfix' => '<div class="clear"></div>', // form alert miatt
    'validation' => Array(
      Array( 'type' => 'required' )
    )
  ),
  
  'weight' => Array(
    'displayname' => $l('', 'weight'),
    'type'        => 'inputText',
    'value'       => 100,
    'validation'  => Array(
      Array( 'type' => 'number' )
    )
  ),
  
);

$uri     = $this->bootstrap->staticuri . 'images/categories/';
$files   = scandir( $this->bootstrap->config['categoryiconpath'] );

foreach( $files as $filename ) {
  
  if ( preg_match( '/\.png$/i', $filename ) ) {
    
    $config['iconfilename']['values'][ $filename ] =
      '<img src="' . $uri . $filename . '" />';
    ;
    
  }
  
}
