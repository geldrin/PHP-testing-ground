<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{$language}" xml:lang="{$language}">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta http-equiv="imagetoolbar" content="no" />
  <meta name="author" content="Dots Amazing - www.dotsamazing.com" />
  <meta name="MSSmartTagsPreventParsing" content="true" />
  <base href="{$BASE_URI}" /><!--[if IE]></base><![endif]-->
  <title>{if $title}{$title|escape:html|titleescape} | {/if}{#sitename#}</title>
  <link rel="StyleSheet" type="text/css" href="{$BASE_URI}js/flowplayer/skin/minimalist.css" media="screen"/>
  <link rel="StyleSheet" type="text/css" href="{$BASE_URI}js/player/app.css?{$VERSION}" media="screen"/>
  <link rel="StyleSheet" type="text/css" href="{$STATIC_URI}css/embed.css?{$VERSION}" media="screen"/>
  <script type="text/javascript" src="{$BASE_URI}js/TraceKit/tracekit.js"></script>
  <script type="text/javascript" src="{$STATIC_URI}js/bluebird.min.js"></script>
  <script type="text/javascript" src="{$STATIC_URI}js/system.js"></script>
  <script type="text/javascript" src="{$STATIC_URI}js/jquery.min.js"></script>
  <script type="text/javascript" src="{$BASE_URI}js/flowplayer/flowplayer.min.js"></script>
  <script type="text/javascript" src="{$BASE_URI}js/flowplayer/hls.min.js"></script>
  <script type="text/javascript" src="{$bootstrap->scheme}{$bootstrap->config.baseuri}{$language}/contents/language"></script>
  <script type="text/javascript" src="{$BASE_URI}js/debug/app.js"></script>
  <script type="text/javascript" src="{$BASE_URI}js/player/app.js"></script>
  <script type="text/javascript">
  var BASE_URI   = '{$BASE_URI}';
  {* debug init *}
  SystemJS.import('debug/app');
  var flashconfig = {$playerconfig.flashplayer.config|@jsonescape:true};
  var playerconfig = {$playerconfig|@unset:"flashplayer.config"|@jsonescape};
  SystemJS.import('player/app');
  </script>
</head>
<body style="">
{if $bootstrap->production and $bootstrap->config.loadgoogleanalytics}
{literal}
<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-34892054-1']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
{/literal}
{/if}
<center>
  <div id="player" style="width: {$playerconfig.width}px; height: {$playerconfig.height}px;">
    <div id="{$playerconfig.containerid}">
      <img src="{$playerconfig.thumbnail|escape:html}"/>
    </div>
  </div>
</center>
</body>
