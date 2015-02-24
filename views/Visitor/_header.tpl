<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{$language}" xml:lang="{$language}">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta http-equiv="imagetoolbar" content="no" />
  <meta name="X-Developer" content="Dots Amazing - www.dotsamazing.com" />
  <meta name="MSSmartTagsPreventParsing" content="true" />
  <meta name="format-detection" content="telephone=no" />{*} iphone ne vegye a random szamokat telefonszamoknak {/*}
  <base href="{$BASE_URI}" /><!--[if IE]></base><![endif]-->
  <title>{if $title}{$title|strip_tags|escape:html|titleescape} | {/if}{#sitename#}</title>
  {include file="Visitor/_opengraph.tpl"}
  {if $metadescription}{include file="Visitor/Recordings/Metadescription.tpl"}{/if}
  <link rel="icon" type="image/png" href="{$STATIC_URI}images/favicon.png"/>
  {csscombine}
  <link rel="StyleSheet" type="text/css" href="{$STATIC_URI}css/custom-theme/jquery-ui-1.9.2.custom.min.css" media="screen"/>
  {if $needfancybox}
    <link rel="StyleSheet" type="text/css" href="{$STATIC_URI}js/fancybox/jquery.fancybox-1.3.4.css" media="screen"/>
  {/if}
  <link rel="StyleSheet" type="text/css" href="{$STATIC_URI}css/style.css?{$VERSION}" media="screen"/>
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
  <link rel="StyleSheet" type="text/css" href="{$BASE_URI}contents/layoutcss?{$VERSION}" media="screen"/>

  {jscombine}
  <script type="text/javascript" src="{$STATIC_URI}js/jquery.min.js"></script>
  <script type="text/javascript" src="{$STATIC_URI}js/jquery-ui-1.9.2.custom.min.js"></script>
  <script type="text/javascript" src="{$STATIC_URI}js/swfobject.full.js"></script>
  <script type="text/javascript" src="{$STATIC_URI}js/moment-with-langs.min.js"></script>
  <script type="text/javascript" src="{$bootstrap->scheme}{$bootstrap->config.baseuri}{$language}/contents/language"></script>
  {if $needfancybox}
    <script type="text/javascript" src="{$STATIC_URI}js/fancybox/jquery.fancybox-1.3.4.pack.js"></script>
  {/if}
  {if $needhistory}
    <script type="text/javascript" src="{$STATIC_URI}js/jquery.history.js"></script>
  {/if}
  {if $needanalytics}
    <script type="text/javascript" src="{$STATIC_URI}js/analytics/dygraph-combined.js"></script>
  {/if}
  <script type="text/javascript" src="{$STATIC_URI}js/tools{$VERSION}.js"></script>
  {/jscombine}
  <script type="text/javascript">
  var BASE_URI   = '{$BASE_URI}';
  var STATIC_URI = '{$STATIC_URI}';
  var VERSION    = '{$VERSION}';
  var language   = '{$language}';
  {if $needping and $member.id and $member.issingleloginenforced}
  var needping   = true;
  var pingsecs   = {$bootstrap->config.sessionpingseconds};
  {else}
  var needping   = false;
  {/if}
  var BROWSER    = {ldelim}
    mobile: {if $browser.mobile}true{else}false{/if},
    tablet: {if $browser.tablet}true{else}false{/if},
    obsolete: {if $browser.obsolete}true{else}false{/if} 
  {rdelim};
  </script>
  <link rel="alternate" type="application/rss+xml" title="{#rss_news#|sprintf:$organization.name|escape:html}" href="{$language}/organizations/newsrss" />
</head>
<body>
{if $bootstrap->config.warnobsoletebrowser and $browser.obsolete}
  <a class="openinlayer" target="_blank" href="{$BASE_URI}{$language}/tools/updateyourbrowser" id="browserAlert">{#sitewide_updateyourbrowser#}</a>
{/if}
<div id="headerbg"></div>
{if $pagebgclass}
  <div id="pagebg" class="{$pagebgclass}"></div>
{/if}
<div id="pagecontainer">
  <div id="wrap">
    <div id="header">
      <div id="headertop">
        {eval var=$layoutheader}
      </div>
      <div class="clear"></div>
    </div>
    
    {if $sessionmessage and !$skipsessionmessage}
      {include file="Visitor/_message.tpl" message=$sessionmessage}
    {/if}
    
    <div id="body">
