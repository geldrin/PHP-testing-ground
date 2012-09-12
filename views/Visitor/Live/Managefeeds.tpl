{include file="Visitor/_header.tpl"}
<div class="heading">
  <h1>{$channel.title|escape:html} - {#live__streams_titlepostfix#}</h1>
  <h2><a href="{$language}/live/details/{$channel.id},{$channel.title|filenameize|escape:html}">{#live__backtoevent#}</a></h2>
</div>
<br/>
<table id="feeds">
  <tr>
    <th>{#live__feed#}</th>
    <th>{#live__streams#}</th>
  </tr>
  {foreach from=$feeds item=feed}
  <tr>
    <td class="feed">
      <a href="{$language}/live/view/{$feed.id},{$feed.name|filenameize}" class="left"><b>{$feed.name|escape:html}</b></a>
      <a href="{$language}/live/view/{$feed.id},{$feed.name|filenameize}" class="livefeed" title="{if $feed.status == 'live'}{#live__feedislive#}{else}{#live__feedistesting#}{/if}">{if $feed.status == 'live'}{#live__feedislive#}{else}{#live__feedistesting#}{/if}</a>
      <div class="clear"></div>
      {if $feed.feedtype != 'vcr' or ( $feed.feedtype == 'vcr' and $feed.streams[0].status == null ) }
        <a href="{$language}/live/modifyfeed/{$feed.id}">{#live__live_edit#}</a> |
        <a href="{$language}/live/deletefeed/{$feed.id}" class="confirm" question="{#sitewide_areyousure#|escape:html}">{#live__live_delete#}</a>
      {/if}
      {if !empty( $feed.streams )}
        {if $feed.feedtype != 'vcr' or ( $feed.feedtype == 'vcr' and $feed.streams[0].status == null ) }|{/if}
        <a href="#" class="liveembed" data-embedurl="{$BASE_URI}{$language}/live/view/{$feed.id},{$feed.name|filenameize}?chromeless=true">{#live__embed#}</a>
        <div class="liveembedwrap">
          <span class="label">{#live__embed_info#}</span>
          <div class="option">
            <label for="chat" class="label">{#live__chat#}:</label>
            <input type="radio" class="chat" name="chat_{$feed.id}" id="chat_no_{$feed.id}" value="0"/>
            <label for="chat_no_{$feed.id}">{#live__chat_no#}</label>
            <input type="radio" class="chat" name="chat_{$feed.id}" id="chat_yes_{$feed.id}" checked="checked" value="1"/>
            <label for="chat_yes_{$feed.id}">{#live__chat_yes#}</label>
          </div>
          <div class="option">
            <label for="fullplayer" class="label">{#live__fullplayer#}:</label><br/>
            <input type="radio" class="fullplayer" name="fullplayer_{$feed.id}" id="fullplayer_yes_{$feed.id}" value="1" checked="checked"/>
            <label for="fullplayer_yes_{$feed.id}">{#live__fullplayer_yes#}</label><br/>
            <input type="radio" class="fullplayer" name="fullplayer_{$feed.id}" id="fullplayer_no_{$feed.id}" value="0"/>
            <label for="fullplayer_no_{$feed.id}">{#live__fullplayer_no#}</label>
          </div>
          {capture assign=liveembed}
            <iframe width="950" height="980" src="{$BASE_URI}{$language}/live/view/{$feed.id},{$feed.name|filenameize}?chromeless=true" frameborder="0" allowfullscreen="allowfullscreen"></iframe>
          {/capture}
          <textarea onclick="this.select();">{$liveembed|trim|escape:html}</textarea>
        </div>
      {/if}
   </td>
    <td>
      <table class="stream">
      {foreach from=$feed.streams item=stream}
        <tr>
          {if $feed.feedtype != 'vcr'}
            <td class="streamname">
              <a href="{$language}/live/view/{$feed.id},{$stream.id},{$feed.name|filenameize}"><b>{$stream.name|escape:html}</b></a>
            </td>
          {/if}
          <td class="streamactions"{if $feed.feedtype == 'vcr'} colspan="2"{/if}>
            <span class="nobr">
              {if $feed.feedtype == 'vcr'}
                {if !preg_match('/^failed.*/', $stream.status )}
                  {if !$stream.status}
                    <a href="{$language}/live/togglestream/{$stream.id}?start=1" class="submitbutton">{#live__startrecord#}</a>
                  {elseif $stream.status == 'recording'}
                    {$l->getLov('streamstatus', $language, $stream.status)}
                    <a href="{$language}/live/togglestream/{$stream.id}?start=0" class="submitbutton">{#live__stoprecord#}</a>
                  {else}
                    {$l->getLov('streamstatus', $language, $stream.status)}
                  {/if}
                {else}
                  {#live__streamerror#|sprintf:$stream.status}
                {/if}
              {else}
                <a href="{$language}/live/modifystream/{$stream.id}">{#live__live_edit#}</a> |
                <a href="{$language}/live/deletestream/{$stream.id}" class="confirm" question="{#sitewide_areyousure#|escape:html}">{#live__live_delete#}</a>
              {/if}
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
      {if $feed.feedtype != 'vcr'}
        <tr>
          <td rowspan="3">
            <a href="{$language}/live/createstream/{$feed.id}"><b>+</b> {#live__addstream#}</a>
          </td>
        </tr>
      {/if}
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