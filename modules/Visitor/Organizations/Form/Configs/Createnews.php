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
    'value' => 'submitcreatenews'
  ),
  
  'forward' => Array(
    'type'  => 'inputHidden',
    'value' => $this->application->getParameter('forward'),
  ),
  
  'title_stringid' => Array(
    'type'  => 'inputHidden',
    'value' => '0',
  ),
  
  'lead_stringid' => Array(
    'type'  => 'inputHidden',
    'value' => '0',
  ),
  
  'body_stringid' => Array(
    'type'  => 'inputHidden',
    'value' => '0',
  ),
  
  'fs1' => array(
    'type'   => 'fieldset',
    'legend' => $l('organizations', 'createnews_title'),
    'prefix' => '<span class="legendsubtitle">' . $l('organizations', 'createnews_subtitle') . '</span>',
  ),
  
  'starts' => Array(
    'displayname' => $l('organizations', 'news_starts'),
    'type'        => 'inputText',
    'html'        =>
      'class="inputtext inputbackground clearonclick datetimepicker margin" ' .
      'data-dateyearrange="' . ( date('Y') - 10 ) . ':2030"' .
      'data-dateuntil="2030-12-31" ' .
      'data-datetimefrom="' . date('Y-m-d H:i', strtotime('-1 year') ) . '"'
    ,
    'value'       => date("Y-m-d H:i"),
    'validation'  => Array(
      Array(
        'type' => 'date',
        'format' => 'YYYY-MM-DD h:m'
      ),
    ),
  ),
  
  'ends' => Array(
    'displayname' => $l('organizations', 'news_ends'),
    'type'        => 'inputText',
    'html'        =>
      'class="inputtext inputbackground clearonclick datetimepicker margin" ' .
      'data-dateyearrange="' . ( date('Y') - 10 ) . ':2030" ' .
      'data-datefrom="2003-01-01" data-dateuntil="2030-12-31" ' .
      'data-datetimefrom="' . date('Y-m-d H:i') . '"'
    ,
    'value'       => '2030-12-31 23:59',
    'validation'  => Array(
      Array(
        'type' => 'date',
        'format' => 'YYYY-MM-DD h:m'
      ),
    ),
  ),
  
  'title' => Array(
    'displayname' => $l('organizations', 'news_title'),
    'type'        => 'text',
    'rowlayout'   => '
      <tr %errorstyle%>
        <td class="labelcolumn">
          <label for="%id%">%displayname%</label>
        </td>
        <td class="elementcolumn">
    ',
  ),
);

foreach( $this->bootstrap->config['languages'] as $language ) {
  
  $config['title_' . $language ] = Array(
    'type'        => 'inputText',
    'prefix'      => $l->getlov('languages', null, $language ) . ':',
    'rowlayout'   => '%prefix%%element%%postfix%%errordiv%<br/>',
    'validation'  => Array(
    )
  );
  
}

$config['lead'] = Array(
  'displayname' => $l('organizations', 'news_lead'),
  'type'        => 'text',
  'rowlayout'   => '
      </td>
    </tr>
    <tr %errorstyle%>
      <td class="labelcolumn">
        <label for="%id%">%displayname%</label>
      </td>
      <td class="elementcolumn">
  ',
  'validation'  => Array(
  )
);

foreach( $this->bootstrap->config['languages'] as $language ) {
  
  $config['lead_' . $language ] = Array(
    'type'        => 'textarea',
    'prefix'      => $l->getlov('languages', null, $language ) . ':',
    'rowlayout'   => '%prefix%%element%%postfix%%errordiv%<br/>',
    'validation'  => Array(
    )
  );
  
}

$config['body'] = Array(
  'displayname' => $l('organizations', 'news_body'),
  'type'        => 'text',
  'rowlayout'   => '
      </td>
    </tr>
    <tr %errorstyle%>
      <td class="labelcolumn">
        <label for="%id%">%displayname%</label>
      </td>
      <td class="elementcolumn">
  ',
);

foreach( $this->bootstrap->config['languages'] as $language ) {
  
  $config['body_' . $language ] = Array(
    'rowlayout'   => '%prefix%%element%%postfix%%errordiv%<br/>',
    'prefix'      => $l->getlov('languages', null, $language ) . ':',
    'type'        => 'tinyMCE',
    'jspath'      => $this->controller->toSmarty['BASE_URI'] . 'js/tiny_mce/tiny_mce.js',
    'width'       => 450,
    'height'      => 500,
    'config'      => $tinymceconfig,
    'validation'  => Array(
    )
  );
  
}

$config['weight'] = Array(
  'displayname' => $l('organizations', 'news_weight'),
  'postfix'     => '<div class="smallinfo">' . $l('organizations', 'news_weightpostfix') . '</div>',
  'type'        => 'inputText',
  'value'       => 100,
  'rowlayout'   => '
      </td>
    </tr>
    <tr %errorstyle%>
      <td class="labelcolumn">
        <label for="%id%">%displayname%</label>
      </td>
      <td class="elementcolumn">%prefix%%element%%postfix%%errordiv%</td>
    </tr>
  ',
  'validation'  => Array(
    Array('type' => 'number', 'real' => 0 )
  ),
);

$config['disabled'] = Array(
  'displayname' => $l('organizations', 'news_disabled'),
  'type'        => 'inputRadio',
  'value'       => 0,
  'values'      => $l->getLov('noyes'),
);
