<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{$language}" xml:lang="{$language}">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta http-equiv="imagetoolbar" content="no" />
  <meta name="author" content="Dots Amazing - www.dotsamazing.com" />
  <meta name="MSSmartTagsPreventParsing" content="true" />
  <meta name="format-detection" content="telephone=no" />{*} iphone ne vegye a random szamokat telefonszamoknak {/*}
  <base href="{$BASE_URI}" /><!--[if IE]></base><![endif]-->
  <title>{if $title}{$title|escape:html|titleescape} | {/if}{#sitename#}</title>
  {csscombine}
  <link rel="StyleSheet" type="text/css" href="{$STATIC_URI}css/custom-theme/jquery-ui-1.9.2.custom.min.css" media="screen"/>
  {if $needfancybox}
    <link rel="StyleSheet" type="text/css" href="{$STATIC_URI}js/fancybox/jquery.fancybox-1.3.4.css" media="screen"/>
  {/if}
  <link rel="StyleSheet" type="text/css" href="{$STATIC_URI}css/style{$VERSION}.css" media="screen"/>
  {if $browser.mobile}
    <meta name="viewport" content="width=device-width, maximum-scale=1.0"/>
    <link rel="StyleSheet" type="text/css" href="{$STATIC_URI}css/style_mobile{$VERSION}.css" media="screen"/>
  {/if}
  {/csscombine}
  
  <!--[if lte IE 8]>
  <link rel="StyleSheet" type="text/css" href="{$STATIC_URI}css/style_ie{$VERSION}.css" />
  <![endif]-->
  <!--[if lte IE 7]>
  <link rel="StyleSheet" type="text/css" href="{$STATIC_URI}css/style_ie7{$VERSION}.css" />
  <![endif]-->
  <!--[if lte IE 6]>
  <link rel="StyleSheet" type="text/css" href="{$STATIC_URI}css/style_ie6{$VERSION}.css" />
  <![endif]-->
  {jscombine}
  <script type="text/javascript" src="{$STATIC_URI}js/jquery.min.js"></script>
  <script type="text/javascript" src="{$STATIC_URI}js/jquery-ui-1.9.2.custom.min.js"></script>
  <script type="text/javascript" src="{$STATIC_URI}js/swfobject.full.js"></script>
  <script type="text/javascript" src="{$bootstrap->scheme}{$bootstrap->config.baseuri}{$language}/contents/language"></script>
  {if $needfancybox}
    <script type="text/javascript" src="{$STATIC_URI}js/fancybox/jquery.fancybox-1.3.4.pack.js"></script>
  {/if}
  {/jscombine}
  <script type="text/javascript">
  var BASE_URI   = '{$BASE_URI}';
  var STATIC_URI = '{$STATIC_URI}';
  var VERSION    = '{$VERSION}';
  var language   = '{$language}';
  var BROWSER    = {ldelim}
    mobile: {if $browser.mobile}true{else}false{/if},
    tablet: {if $browser.tablet}true{else}false{/if},
    obsolete: {if $browser.obsolete}true{else}false{/if} 
  {rdelim};
  </script>

</head>
<body class="nolayout {$bodyclass}">

<div id="pagecontainer">
  <div id="wrap">
    <div id="body">
