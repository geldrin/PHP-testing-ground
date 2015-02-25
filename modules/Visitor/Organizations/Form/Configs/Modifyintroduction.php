<?php
$tinymceconfig = $l->getLov('tinymcevisitor') + array(
  'content_css' =>
    $this->controller->toSmarty['STATIC_URI'] .
    'css/style_tinymce_content' . $this->bootstrap->config['version'] . '.css,' .
    '/contents/layoutwysywygcss?' . $this->bootstrap->config['version']
  ,
);

$config = Array(
  
  'action' => Array(
    'type'  => 'inputHidden',
    'value' => 'submitmodifyintroduction'
  ),
  
  'forward' => Array(
    'type'  => 'inputHidden',
    'value' => $this->application->getParameter('forward'),
  ),
  
  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('organizations', 'modifyintroduction_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('organizations', 'modifyintroduction_subtitle') . '</span>',
  ),
  
  'introduction_stringid' => Array(
    'displayname' => $l('organizations', 'introduction'),
    'type'        => 'tinyMCEMultiLanguage2',
    'languages'   => $this->controller->organization['languages'],
    'jspath'      => $this->controller->toSmarty['BASE_URI'] . 'js/tiny_mce/tiny_mce.js',
    'width'       => 305,
    'height'      => 500,
    'config'      => $tinymceconfig,
    'validation'  => Array(
    )
  ),
  
);
