{include file="Visitor/_header.tpl" title=$recording.title|cat:" - "|cat:#recordings__analytics_titlepostfix#}
<script>
var analyticsdata = {$analyticsdata|@jsonescape};
</script>

<div id="pagetitle">
  <h1>
    {$recording.title|escape:html|mb_wordwrap:25} - {#recordings__analytics_titlepostfix#}
    <a class="back" href="{$back|escape:html}">{#sitewide_back#|lower}</a>
  </h1>
  <div class="clear"></div>
</div>
<div class="channelgradient"></div><br/>

<a href="{$language}/recordings/analyticsexport/{$recording.id}">{#recordings__analyticsexport#}</a><br/>

<br/>

<div id="analyticscontainer">
  <div id="recordingstatistics"></div>
  <div id="statisticslegend"></div>

  <div class="clear"></div><br/>

  <div id="zoomer"></div>
</div>

<div class="clear"></div>
<br/>

{if !empty( $help )}
<div class="help fullwidth">
  <h1 class="title">{$help.title}</h1>
  {$help.body}
</div>
{/if}

{include file="Visitor/_footer.tpl"}