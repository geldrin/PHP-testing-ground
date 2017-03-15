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
    width: {/literal}{$playerwidth-20}{literal}px;
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
<body style="overflow: hidden;">
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
  {include file=Visitor/mobile_logintoview.tpl indexphoto=$recording|@indexphoto width=$playerwidth height=$playerheight}
{elseif $browser.mobile}
  {if $recording.mobilevideoreshq}
    {assign var=height value=$playerheight-30}
  {/if}
  {if $browser.mobiledevice == 'iphone'}
    <div id="mobileplayercontainer">
      <video x-webkit-airplay="allow" controls="controls" alt="{$recording.title|escape:html}" width="{$playerwidth}" height="{$playerheight}" poster="{$recording|@indexphoto}" src="{$mobilehttpurl}">
        <a href="{$mobilehttpurl}"><img src="{$recording|@indexphoto}" width="280" height="190"/></a>
      </video>
    </div>
  {else}
    {if $recording.mediatype == 'audio'}
      {assign var=mobileurl value=$audiofileurl}
    {elseif $organization.ondemandhlsenabledandroid}
      {assign var=mobileurl value=$mobilehttpurl}
    {else}
      {assign var=mobileurl value=$mobilertspurl}
    {/if}
    <div id="mobileplayercontainer">
      <a href="{$mobileurl}"><img src="{$recording|@indexphoto}" width="{$playerwidth}" height="{$playerheight}"/></a>
    </div>
  {/if}
  {if count( $mobileversions ) > 1}
    <div id="qualitychooser">
      <ul>
        {foreach from=$mobileversions item=version}
          <li><a href="{$language}/recordings/details/{$recording.id},{$recording.title|filenameize}?quality={$version|escape:url}">{$version|escape:html}</a></li>
        {/foreach}
      </ul>
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

    var elem = document.getElementById('{$playercontainerid}');
    if (!elem)
      return;

    if (typeof(elem.textContent) != 'undefined')
      elem.textContent = '{#contents__flashloaderror#}';
    else
      elem.innerText = '{#contents__flashloaderror#}';
  {rdelim};

  document.write('<div id="{$playercontainerid}"></div>');
  swfobject.embedSWF('flash/VSQ{$flashplayertype}Player.swf?v={$VERSION}', '{$playercontainerid}', '980', '{$playerheight}', '11.1.0', 'flash/swfobject/expressInstall.swf', {$playerconfig.flashplayer.config|@jsonescape:true}, params, null, handleFlashLoad );

  {rdelim})();
  </script>

{/if}
</center>
</body>