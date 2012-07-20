<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{$language}" xml:lang="{$language}">
<head>

  <meta name="author" content="Dots Amazing - www.dotsamazing.com" />
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta http-equiv="imagetoolbar" content="no" />
  <meta name="MSSmartTagsPreventParsing" content="true" />
  <title>Springboard CMS - {$bootstrap->config.siteid}</title>
  <base href="{$bootstrap->adminuri}" /><!--[if IE]></base><![endif]-->
  
  <link rel="stylesheet" type="text/css" href="js/fancybox/jquery.fancybox-1.3.0.css" media="screen">
  <script type="text/javascript" src="js/jquery-1.5.js"></script>
  <script type="text/javascript">var $j = jQuery.noConflict();</script>
  <script type="text/javascript" src="js/fancybox/jquery.fancybox-1.3.0.js"></script>
  <link rel="stylesheet" type="text/css" href="js/tooltips/tooltips.css" media="screen"/>
  <script type="text/javascript" src="js/scriptaculous/lib/prototype.js"></script>
  <script type="text/javascript" src="js/scriptaculous/src/scriptaculous.js"></script>
  <script type="text/javascript" src="js/side-bar.js"></script>
  <script type="text/javascript" src="js/tooltips/tooltips.js"></script>
  <script type="text/javascript" src="js/tools.js"></script>
  <script type="text/javascript">
    var autoSaveFolder = "admin";
    var BASE_URI = "{$bootstrap->baseuri}";
    var language = "{$language}";
  </script>
  <link rel="StyleSheet" type="text/css" href="css/style{$VERSION}.css" media="screen"/>
</head>
<body>

{if !$hideheading}
  {include file="Admin/_heading.tpl"}
{/if}

{if !$hidenavigation}
  {include file="Admin/_navigation.tpl"}
{/if}