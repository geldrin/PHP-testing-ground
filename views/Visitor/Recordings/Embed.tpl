<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{$language}" xml:lang="{$language}">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta http-equiv="imagetoolbar" content="no" />
  <meta name="author" content="Dots Amazing - www.dotsamazing.com" />
  <meta name="MSSmartTagsPreventParsing" content="true" />
  <base href="{$BASE_URI}" /><!--[if IE]></base><![endif]-->
  <title>{if $title}{$title|escape:html|titleescape} | {/if}{#sitename#}</title>

  <style type="text/css">
  {literal}
  body { padding: 0; margin: 0; }
  {/literal}
  </style>

</head>
<body>

<script type="text/javascript">
(function() {ldelim}
  
{include_php file="file:`$smarty.const.BASE_PATH`httpdocs_static/js/swfobject.full.js"}

var params = {ldelim}
  quality: "high",
  bgcolor: "#050505",
  allowscriptaccess: "sameDomain",
  allowfullscreen: "true",
  wmode: 'opaque'
{rdelim};

document.write('<div id="{$containerid}"></div>');
swfobject.embedSWF('flash/TCSharedPlayer{$VERSION}.swf', '{$containerid}', '{$width}', '{$height}', '11.1.0', 'flash/swfobject/expressInstall.swf', {$flashdata|@jsonescape:true}, params );

{rdelim})();
</script>

</body>
