{include file="Visitor/_header.tpl"}
<div class="heading">
  <h1>{$channel.title|escape:html} - {#live__streams_titlepostfix#}</h1>
  <h2><a href="{$language}/live/details/{$channel.id},{$channel.title|filenameize|escape:html}">{#live__backtoevent#}</a></h2>
</div>
<br/>
<table id="feeds">
  <tr>
    <th class="left">
      {#live__feed#}
    </th>
    <th>
      {#live__streams#}
    </th>
  </tr>
  {foreach from=$feeds item=feed}
  <tr>
    <td class="feed">
      <a href="{$language}/live/view/{$feed.id},{$feed.name|filenameize}" class="left"><b>{$feed.name|escape:html}</b></a>
      <a href="{$language}/live/view/{$feed.id},{$feed.name|filenameize}" class="livefeed" title="{if $feed.status == 'live'}{#live__feedislive#}{else}{#live__feedistesting#}{/if}">{if $feed.status == 'live'}{#live__feedislive#}{else}{#live__feedistesting#}{/if}</a>
      <div class="clear"></div>
      {if !$feed.status}
        <a href="{$language}/live/modifyfeed/{$feed.id}">{#live__live_edit#}</a> |
        <a href="{$language}/live/deletefeed/{$feed.id}" class="confirm" question="{#sitewide_areyousure#|escape:html}">{#live__live_delete#}</a>
      {/if}
   </td>
    <td>
      <table class="stream">
      {foreach from=$feed.streams item=stream}
        <tr>
          <td class="streamname">
            <a href="{$language}/live/view/{$feed.id},{$stream.id},{$feed.name|filenameize}"><b>{$stream.name|escape:html}</b></a>
          </td>
          <td class="streamactions">
            <span class="nobr">
              {if $feed.feedtype == 'vcr'}
                {if !preg_match('^error.*', $stream.status )}
                  {if !$stream.status}
                    <a href="{$language}/live/togglestream/{$stream.id}?start=1">{#live__startrecord#}</a>
                  {elseif $stream.status == 'recording'}
                    <a href="{$language}/live/togglestream/{$stream.id}?start=0">{#live__stoprecord#}</a>
                  {/if}
                {else}
                  {#live__streamerror#|sprintf:$stream.status}
                {/if}
              {/if}
              <a href="{$language}/live/modifystream/{$stream.id}">{#live__live_edit#}</a> |
              <a href="{$language}/live/deletestream/{$stream.id}" class="confirm" question="{#sitewide_areyousure#|escape:html}">{#live__live_delete#}</a>
            </span>
          </td>
          <td>&nbsp;</td>
        </tr>
      {foreachelse}
        <tr>
          <td rowspan="1">
            {#live__nostream#}
          </td>
        </tr>
      {/foreach}
        <tr>
          <td rowspan="3">
            <a href="{$language}/live/createstream/{$feed.id}"><b>+</b> {#live__addstream#}</a>
          </td>
        </tr>
      </table>
    </td>
  </tr>
  {foreachelse}
  <tr>
    <td colspan="2">{#live__nofeeds#}</td>
  </tr>
  {/foreach}
  <tr>
    <td colspan="2" id="addfeed">
      <a href="{$language}/live/createfeed/{$channel.id}"><b>+</b> {#live__addfeed#}</a>
    </td>
  </tr>
</table>
<br/>

{if !empty( $help )}
<div class="help fullwidth">
  <h1 class="title">{$help.title}</h1>
  {$help.body}
</div>
{/if}

{include file="Visitor/_footer.tpl"}