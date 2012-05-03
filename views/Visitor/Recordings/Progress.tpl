<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{$language}" xml:lang="{$language}">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta http-equiv="imagetoolbar" content="no" />
  <meta name="author" content="Dots Amazing - www.dotsamazing.com" />
  <meta name="MSSmartTagsPreventParsing" content="true" />
  <meta name="format-detection" content="telephone=no" />{*} iphone ne vegye a random szamokat telefonszamoknak {/*}
  <base href="{$organization|@uri:base}" /><!--[if IE]></base><![endif]-->
  <title>{#sitename#}</title>
  {jscombine}
  <script type="text/javascript" src="{$organization|@uri:static}js/jquery-1.7.1.min{$VERSION}.js"></script>
  <script type="text/javascript" src="{$organization|@uri:static}js/swfobject.full{$VERSION}.js"></script>
  <script type="text/javascript" src="{$organization|@uri:static}js/tools{$VERSION}.js"></script>
  {/jscombine}
  <script type="text/javascript">
  var BASE_URI   = '{$organization|@uri:base}';
  var STATIC_URI = '{$organization|@uri:static}';
  var VERSION    = '{$VERSION}';
  var language   = '{$language}';
  </script>
  
</head>
<body>
  <script type="text/javascript">
    setupUploadIframe();
  </script>
</body>
</html>