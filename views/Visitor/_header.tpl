<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{$language}" xml:lang="{$language}">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta http-equiv="imagetoolbar" content="no" />
  <meta name="author" content="Dots Amazing - www.dotsamazing.com" />
  <meta name="MSSmartTagsPreventParsing" content="true" />
  <meta name="format-detection" content="telephone=no" />{*} iphone ne vegye a random szamokat telefonszamoknak {/*}
  <base href="http{if $ssl}s{/if}://{$bootstrap->config.baseuri}" /><!--[if IE]></base><![endif]-->
  <title>{if $title}{$title|escape:html|titleescape} | {/if}{l key=sitename}</title>
  <link rel="StyleSheet" type="text/css" href="{$STATIC_URI}css/style{$VERSION}.css" media="screen"/>

  <!--[if lte IE 7]>
  <link rel="StyleSheet" type="text/css" href="{$STATIC_URI}css/style_ie{$VERSION}.css" />
  <![endif]-->
  
  <!--[if lte IE 6]>
  <link rel="StyleSheet" type="text/css" href="{$STATIC_URI}css/style_ie6{$VERSION}.css" />
  <![endif]-->

  <script type="text/javascript" src="{$STATIC_URI}js/jquery-1.7.1.min{$VERSION}.js"></script>
  <script type="text/javascript" src="{$STATIC_URI}js/swfobject.full{$VERSION}.js"></script>
  <script type="text/javascript">
  var $j = jQuery.noConflict();
  var BASE_URI   = '{$BASE_URI}';
  var STATIC_URI = '{$STATIC_URI}';
  var VERSION    = '{$VERSION}';
  var language   = '{$language}';
  </script>
  <script type="text/javascript" src="{$STATIC_URI}js/tools{$VERSION}.js"></script>
  
</head>
<body>
{if $browserInfo.obsolete}
  <a class="openinlayer" target="_blank" href="{$BASE_URI}{$language}/tools/updateyourbrowser" id="browserAlert">{l key=sitewide_updateyourbrowser}</a>
{/if}

<div id="pagecontainer">
  <div id="wrap">
    <div id="header">
      <a id="logo" href="{$BASE_URI}" title="{l key=sitename escape=html}"><span></span>{l key=sitename escape=html}</a>
      <div id="headersearchlink"><a href="{$language}/search/all">{l key=search escape=html}</a></div>
    </div>

    {if $hidemenu}

      {include file="_menu.tpl"}
     
      {include file="_submenu.tpl"}

    {/if}
    
    {if $sessionmessage and !$skipsessionmessage}
      {include file="Visitor/_message.tpl" message=$sessionmessage}
    {/if}
    
    <div class="body">
