{include file="Visitor/_header.tpl"}
<script>
var analyticsdata = {$analyticsdata|@jsonescape};
</script>

<div class="heading">
  <h1>{$channel.title|escape:html} - {#live__analytics_titlepostfix#}</h1>
  <h2><a href="{$language}/live/managefeeds/{$channel.id}">{#live__managefeeds#}</a></h2>
</div>
<br/>

<div id="analyticscontainer">
  <div id="livestatistics"></div>
  <div id="statisticslegend"></div>

  <div class="form">
    {$form}
  </div>

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