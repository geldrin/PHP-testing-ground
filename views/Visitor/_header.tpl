<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{$language}" xml:lang="{$language}">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta http-equiv="imagetoolbar" content="no" />
  <meta name="author" content="Dots Amazing - www.dotsamazing.com" />
  <meta name="MSSmartTagsPreventParsing" content="true" />
  <meta name="format-detection" content="telephone=no" />{*} iphone ne vegye a random szamokat telefonszamoknak {/*}
  <base href="{$BASE_URI}" /><!--[if IE]></base><![endif]-->
  <title>{if $title}{$title|escape:html|titleescape} | {/if}{l key=sitename}</title>
  {csscombine}
  <link rel="StyleSheet" type="text/css" href="{$STATIC_URI}css/style{$VERSION}.css" media="screen"/>
  {/csscombine}
  
  <!--[if lte IE 8]>
  <link rel="StyleSheet" type="text/css" href="{$STATIC_URI}css/style_ie{$VERSION}.css" />
  <![endif]-->
  
  <!--[if lte IE 6]>
  <link rel="StyleSheet" type="text/css" href="{$STATIC_URI}css/style_ie6{$VERSION}.css" />
  <![endif]-->
  {jscombine}
  <script type="text/javascript" src="{$STATIC_URI}js/jquery-1.7.1.min{$VERSION}.js"></script>
  <script type="text/javascript" src="{$STATIC_URI}js/swfobject.full{$VERSION}.js"></script>
  <script type="text/javascript" src="{$STATIC_URI}js/tools{$VERSION}.js"></script>
  {/jscombine}
  <script type="text/javascript">
  var BASE_URI   = '{$BASE_URI}';
  var STATIC_URI = '{$STATIC_URI}';
  var VERSION    = '{$VERSION}';
  var language   = '{$language}';
  </script>
  
</head>
<body>
{if $browserInfo.obsolete}
  <a class="openinlayer" target="_blank" href="{$BASE_URI}{$language}/tools/updateyourbrowser" id="browserAlert">{l key=sitewide_updateyourbrowser}</a>
{/if}
<div id="headerbg"></div>
<div id="pagecontainer">
  <div id="wrap">
    <div id="header">
      <div id="headertop">
        {include file="Visitor/_login.tpl"}
        
        <div id="headerlogo">
          <a href="{$BASE_URI}" title="{l key=sitename escape=html}"><span></span>{l key=sitename escape=html}</a>
        </div>
      </div>
      <div id="headerbottom">
        <div id="headersearch" class="rightbox">
          
          <form action="{$language}/search/all" method="get">
            {l key=sitewide_search_input assign=searchinput}
            <input id="headersearchsubmit" type="image" src="{$STATIC_URI}images/header_searchimage.png"/>
            <input class="inputtext inputbackground clearonclick" type="text" name="q" data-origval="{$searchinput|escape:html}" value="{$searchinput|escape:html}"/>
            <a href="#" id="headersearcharrow"></a>
          </form>
          
          <div id="languageselector" class="inputbackground right">
            {foreach from=$bootstrap->config.languages item=item}
              {if $item == $language}
                <a href="{$FULL_URI|changelanguage:$language}" class="{$language} active">{l lov=headerlanguages key=$language}<span></span></a>
              {/if}
            {/foreach}
            
            <div id="languages">
              {foreach from=$bootstrap->config.languages item=item}
                {if $item != $language}
                  <a href="{$FULL_URI|changelanguage:$item}" class="{$item}">{l lov=headerlanguages key=$item}</a>
                {/if}
              {/foreach}
            </div>
          </div>
        </div>
        
        {include file="Visitor/_menu.tpl"}
      </div>
    </div>
    
    {if $sessionmessage and !$skipsessionmessage}
      {include file="Visitor/_message.tpl" message=$sessionmessage}
    {/if}
    
    <div id="body">
