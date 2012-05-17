<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{$language}" xml:lang="{$language}">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta http-equiv="imagetoolbar" content="no" />
  <meta name="author" content="Dots Amazing - www.dotsamazing.com" />
  <meta name="MSSmartTagsPreventParsing" content="true" />
  <meta name="format-detection" content="telephone=no" />{*} iphone ne vegye a random szamokat telefonszamoknak {/*}
  <base href="{$BASE_URI}" /><!--[if IE]></base><![endif]-->
  <title>{#sitename#}</title>
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
  <script type="text/javascript">
    setupUploadIframe();
  </script>
</body>
</html>