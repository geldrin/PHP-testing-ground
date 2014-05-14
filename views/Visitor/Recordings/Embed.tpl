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
  body {
    padding: 0;
    margin: 0;
    font-family: "Arial", "sans-serif";
    line-height: 18px;
    font-size: 13px;
  }
  
  #qualitychooser a {
    outline: 0;
    width: {/literal}{$width-20}{literal}px;
    text-align: center;
    display: inline-block;
    color: #7F8890;
    font-weight: bold;
    text-shadow: 1px 1px #1f272b;
    -webkit-border-radius: 6px;
    -moz-border-radius: 6px;
    border-radius: 6px;
    background: #232B30;
    -pie-background: linear-gradient(top, #3D4850 3%, #313d45 4%, #232B30 100%);
    background: -webkit-gradient(linear, left top, left bottom, color-stop(3%,#3D4850), color-stop(4%,#313d45), color-stop(100%,#232B30));
    background: -moz-linear-gradient(top, #3D4850 3%, #313d45 4%, #232B30 100%);
    background: linear-gradient(top, #3D4850 3%, #313d45 4%, #232B30 100%);
    box-shadow: 1px 1px 1px rgba(0,0,0,0.2);
    -moz-box-shadow: 1px 1px 1px rgba(0,0,0,0.2);
    -webkit-box-shadow: 1px 1px 1px rgba(0,0,0,0.2);
    text-decoration: none;
    padding: 3px 10px;
    margin: 3px 0;
  }
  {/literal}
  </style>

</head>
<body>
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
{if $browser.mobile and $needauth}
  {include file=Visitor/mobile_logintoview.tpl indexphoto=$recording|@indexphoto width=$width height=$height}
{elseif $browser.mobile}
  {if $recording.mobilevideoreshq}
    {assign var=height value=$height-30}
  {/if}
  {if $browser.mobiledevice == 'iphone'}
    <div id="mobileplayercontainer">
      <video x-webkit-airplay="allow" controls="controls" alt="{$recording.title|escape:html}" width="{$width}" height="{$height}" poster="{$recording|@indexphoto}" src="{$mobilehttpurl}">
        <a href="{$mobilehttpurl}"><img src="{$recording|@indexphoto}" width="280" height="190"/></a>
      </video>
    </div>
  {else}
    <div id="mobileplayercontainer">
      <a href="{if $recording.mediatype == 'audio'}{$audiofileurl}{else}{$mobilertspurl}{/if}"><img src="{$recording|@indexphoto}" width="{$width}" height="{$height}"/></a>
    </div>
  {/if}
  {if $recording.mobilevideoreshq}
    <div id="qualitychooser">
      <a href="{$FULL_URI}{if $FULL_URI|strpos:'?'}&{else}?{/if}quality={if $mobilehq}lq{else}hq{/if}">{if $mobilehq}{#recordings__lowquality#}{else}{#recordings__highquality#}{/if}</a>
    </div>
  {/if}

{else}

  <script type="text/javascript">
  (function() {ldelim}

  {include_php file="file:`$smarty.const.BASE_PATH`httpdocs_static/js/swfobject.full.js"}

  var params = {ldelim}
    quality: "high",
    bgcolor: "#050505",
    allowscriptaccess: "sameDomain",
    allowfullscreen: "true",
    wmode: 'direct'
  {rdelim};
  var handleFlashLoad = function(e) {ldelim}
    if (e.success)
      return;

    var elem = document.getElementById('{$containerid}');
    if (!elem)
      return;

    if (typeof(elem.textContent) != 'undefined')
      elem.textContent = '{#contents__flashloaderror#}';
    else
      elem.innerText = '{#contents__flashloaderror#}';
  {rdelim};

  document.write('<div id="{$containerid}"></div>');
  swfobject.embedSWF('flash/VSQEmbedPlayer.swf?v={$VERSION}', '{$containerid}', '{$width}', '{$height}', '11.1.0', 'flash/swfobject/expressInstall.swf', {$flashdata|@jsonescape:true}, params, null, handleFlashLoad );

  {rdelim})();
  </script>

{/if}
</center>
</body>
