<!doctype html>
<html lang="{$language}">
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
  <link rel="shortcut icon" href="{$STATIC_URI}images/favicons/vsq_favicon_inverted.ico">
	<link rel="icon" sizes="16x16 32x32 64x64" href="{$STATIC_URI}images/favicons/vsq_favicon_inverted.ico">
	<link rel="icon" type="image/png" sizes="196x196" href="{$STATIC_URI}images/favicons/vsq_favicon_inverted-192.png">
	<link rel="icon" type="image/png" sizes="160x160" href="{$STATIC_URI}images/favicons/vsq_favicon_inverted-160.png">
	<link rel="icon" type="image/png" sizes="96x96" href="{$STATIC_URI}images/favicons/vsq_favicon_inverted-96.png">
	<link rel="icon" type="image/png" sizes="64x64" href="{$STATIC_URI}images/favicons/vsq_favicon_inverted-64.png">
	<link rel="icon" type="image/png" sizes="32x32" href="{$STATIC_URI}images/favicons/vsq_favicon_inverted-32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="{$STATIC_URI}images/favicons/vsq_favicon_inverted-16.png">
	<link rel="apple-touch-icon" href="{$STATIC_URI}images/favicons/vsq_favicon_inverted-57.png">
	<link rel="apple-touch-icon" sizes="114x114" href="{$STATIC_URI}images/favicons/vsq_favicon_inverted-114.png">
	<link rel="apple-touch-icon" sizes="72x72" href="{$STATIC_URI}images/favicons/vsq_favicon_inverted-72.png">
	<link rel="apple-touch-icon" sizes="144x144" href="{$STATIC_URI}images/favicons/vsq_favicon_inverted-144.png">
	<link rel="apple-touch-icon" sizes="60x60" href="{$STATIC_URI}images/favicons/vsq_favicon_inverted-60.png">
	<link rel="apple-touch-icon" sizes="120x120" href="{$STATIC_URI}images/favicons/vsq_favicon_inverted-120.png">
	<link rel="apple-touch-icon" sizes="76x76" href="{$STATIC_URI}images/favicons/vsq_favicon_inverted-76.png">
	<link rel="apple-touch-icon" sizes="152x152" href="{$STATIC_URI}images/favicons/vsq_favicon_inverted-152.png">
	<link rel="apple-touch-icon" sizes="180x180" href="{$STATIC_URI}images/favicons/vsq_favicon_inverted-180.png">
	<meta name="msapplication-TileColor" content="#FFFFFF">
	<meta name="msapplication-TileImage" content="{$STATIC_URI}images/favicons/vsq_favicon_inverted-144.png">
	<meta name="msapplication-config" content="{$STATIC_URI}browserconfig.xml">
  {csscombine}
  <link rel="StyleSheet" type="text/css" href="{$STATIC_URI}css/custom-theme/jquery-ui-1.9.2.custom.min.css" media="screen"/>
  {if $needfancybox}
    <link rel="StyleSheet" type="text/css" href="{$STATIC_URI}js/fancybox/jquery.fancybox-1.3.4.css" media="screen"/>
  {/if}
  {if $needselect2}
    <link rel="StyleSheet" type="text/css" href="{$STATIC_URI}js/select2/select2.min.css" media="screen"/>
  {/if}
  {if $playerconfig}
    <link rel="StyleSheet" type="text/css" href="{$BASE_URI}js/flowplayer/skin/minimalist.css" media="screen"/>
    <link rel="StyleSheet" type="text/css" href="{$BASE_URI}js/player/app.css" media="screen"/>
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
  {* stacktrace generalo minimal lib *}
  <script type="text/javascript" src="{$BASE_URI}js/TraceKit/tracekit.js"></script>
  {* systemjs dependency, promise-ok miatt *}
  <script type="text/javascript" src="{$STATIC_URI}js/bluebird.min.js"></script>
  {* egy dependency manager a typescript appoknak, nem typescript specifikus *}
  <script type="text/javascript" src="{$STATIC_URI}js/system.js"></script>
  <script type="text/javascript" src="{$STATIC_URI}js/jquery.min.js"></script>
  {* debug *}
  <script type="text/javascript" src="{$BASE_URI}js/debug/app.js"></script>
  <script type="text/javascript" src="{$STATIC_URI}js/jquery-ui-1.9.2.custom.min.js"></script>
  <script type="text/javascript" src="{$STATIC_URI}js/swfobject.full.js"></script>
  <script type="text/javascript" src="{$STATIC_URI}js/moment-with-langs.min.js"></script>
  <script type="text/javascript" src="{$bootstrap->scheme}{$bootstrap->config.baseuri}{$language}/contents/language"></script>
  {if $needfancybox}
    <script type="text/javascript" src="{$STATIC_URI}js/fancybox/jquery.fancybox-1.3.4.pack.js"></script>
  {/if}
  {if $needselect2}
    <script type="text/javascript" src="{$STATIC_URI}js/select2/select2.full.min.js"></script>
    <script type="text/javascript" src="{$STATIC_URI}js/select2/i18n/{$language}.js"></script>
  {/if}
  {if $needhistory}
    <script type="text/javascript" src="{$STATIC_URI}js/jquery.history.js"></script>
  {/if}
  {if $needanalytics}
    <script type="text/javascript" src="{$STATIC_URI}js/analytics/dygraph-combined.js"></script>
  {/if}
  {if $needprogressbar}
    <script type="text/javascript" src="{$STATIC_URI}js/progressbar.min.js"></script>
  {/if}
  {if $playerconfig}
  <script type="text/javascript" src="{$BASE_URI}js/flowplayer/flowplayer.min.js"></script>
  <script type="text/javascript" src="{$BASE_URI}js/flowplayer/hls.min.js"></script>
  <script type="text/javascript" src="{$BASE_URI}js/player/app.js"></script>
  {/if}
  <script type="text/javascript" src="{$STATIC_URI}js/tools{$VERSION}.js"></script>
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
  {* debug init *}
  SystemJS.import('debug/app');
  {if $playerconfig}

  var flashconfig = {$playerconfig.flashplayer.config|@jsonescape:true};
  var playerconfig = {$playerconfig|@unset:"flashplayer.config"|@jsonescape};
  SystemJS.import('player/app');
  {/if}
  </script>
  <link rel="alternate" type="application/rss+xml" title="{#rss_news#|sprintf:$organization.name|escape:html}" href="{$language}/organizations/newsrss" />
</head>
<body>
{if $bootstrap->config.warnobsoletebrowser and $browser.obsolete}
  <a class="openinlayer" target="_blank" href="{$BASE_URI}{$language}/tools/updateyourbrowser" id="browserAlert">{#sitewide_updateyourbrowser#}</a>
{/if}
<div id="headerbg"></div>
<div id="pagecontainer">
  <div id="wrap">
    <div id="header">
      {include file=$layoutheader}
      <div class="clear"></div>
    </div>

    {if $sessionmessage and !$skipsessionmessage}
      {include file="Visitor/_message.tpl" message=$sessionmessage}
    {/if}

    <div id="body">
