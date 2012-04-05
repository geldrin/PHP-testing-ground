<?php
$tinymceconfig = $l->getLov('tinymcevisitor') + array(
  'content_css' =>
    $this->controller->toSmarty['STATIC_URI'] .
    'css/style_tinymce_content' . $this->bootstrap->config['version'] . '.css'
  ,
);

$config = Array(
  
  'action' => Array(
    'type'  => 'inputHidden',
    'value' => 'submitmodifynews'
  ),
  
  'id' => Array(
    'type'  => 'inputHidden',
    'value' => $this->application->getNumericParameter('id'),
  ),
  
  'forward' => Array(
    'type'  => 'inputHidden',
    'value' => $this->application->getParameter('forward'),
  ),
  
  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('organizations', 'modifynews_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('organizations', 'modifynews_subtitle') . '</span>',
  ),
  
  'starts' => Array(
    'displayname' => $l('organizations', 'news_starts'),
    'type'        => 'inputText',
    'value'       => date("Y-m-d H:i:s"),
    'validation'  => Array(
      Array(
        'type' => 'date',
        'format' => 'YYYY-MM-DD h:m:s'
      ),
    ),
  ),
  
  'ends' => Array(
    'displayname' => $l('organizations', 'news_ends'),
    'type'        => 'inputText',
    'value'       => '2030-12-31 23:59:59',
    'validation'  => Array(
      Array(
        'type' => 'date',
        'format' => 'YYYY-MM-DD h:m:s'
      ),
    ),
  ),
  
  'title_stringid' => Array(
    'displayname' => $l('organizations', 'news_title'),
    'type'        => 'inputTextMultiLanguage2',
    'languages'   => $this->controller->organization['languages'],
    'validation'  => Array(
    )
  ),
  
  'lead_stringid' => Array(
    'displayname' => $l('organizations', 'news_lead'),
    'type'        => 'textareaMultiLanguage2',
    'languages'   => $this->controller->organization['languages'],
    'validation'  => Array(
    )
  ),
  
  'body_stringid' => Array(
    'displayname' => $l('organizations', 'news_body'),
    'type'        => 'tinyMCEMultiLanguage2',
    'languages'   => $this->controller->organization['languages'],
    'jspath'      => $this->controller->toSmarty['BASE_URI'] . 'js/tiny_mce/tiny_mce.js',
    'width'       => 380,
    'height'      => 150,
    'config'      => $tinymceconfig,
    'validation'  => Array(
    )
  ),
  
  'weight' => Array(
    'displayname' => $l('organizations', 'news_weight'),
    'postfix'     => '<div class="smallinfo">' . $l('organizations', 'news_weightpostfix') . '</div>',
    'type'        => 'inputText',
    'value'       => 100,
    'validation'  => Array(
      Array('type' => 'number', 'real' => 0 )
    ),
  ),
  
  'disabled' => Array(
    'displayname' => $l('organizations', 'news_disabled'),
    'type'        => 'inputRadio',
    'value'       => 0,
    'values'      => $l->getLov('noyes'),
  ),
  
);
