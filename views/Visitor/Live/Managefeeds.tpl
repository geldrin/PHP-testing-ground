{include file="Visitor/_header.tpl"}
<div class="heading">
  <h1>{$channel.title|escape:html|mb_wordwrap:25} - {#live__streams_titlepostfix#}</h1>
  <h2><a href="{$language}/live/details/{$channel.id},{$channel.title|filenameize|escape:html}">{#live__backtoevent#}</a></h2>
</div>
<br/>
<table id="feeds">
  <tr>
    <th></th>
    <th>{#live__feed#}</th>
    <th>{#live__streams#}</th>
  </tr>
  {foreach from=$feeds item=feed}
  {assign var=currentviewers value=$feed.currentviewers|numberformat}
    {if $feed.issecurestreamingforced}
      {assign var=ingressurl value=$bootstrap->config.wowza.secliveingressurl3}
    {else}
      {assign var=ingressurl value=$bootstrap->config.wowza.liveingressurl}
    {/if}
  <tr>
    <td class="livefeedrow">
      <a href="{$language}/live/view/{$feed.id},{$feed.name|filenameize}" class="livefeed" title="{if $feed.status == 'live'}{#live__feedislive#}{else}{#live__feedistesting#}{/if}"></a>
    </td>
    <td class="feed">
      <a href="{$language}/live/view/{$feed.id},{$feed.name|filenameize}" class="left"><b>{$feed.name|mb_wordwrap:30|escape:html}</b></a>
      <span class="currentviewers" data-pollurl="{$language}/live/viewers?livefeedid={$feed.id}" data-template="{#live__currentviewers#|escape:html}">{#live__currentviewers#|sprintf:$currentviewers}</span>
      <br/>
      <a href="{$language}/live/analytics/{$channel.id}?feedids[]={$feed.id}">{#live__analytics#}</a>
      {if $feed.feedtype != 'vcr' or $feed.candelete}
        | <a href="{$language}/live/modifyfeed/{$feed.id}">{#live__live_edit#}</a>
        | <a href="{$language}/live/deletefeed/{$feed.id}" class="confirm" question="{#sitewide_areyousure#|escape:html}">{#live__live_delete#}</a>
      {/if}
      {if !empty( $feed.streams )}
        | <a href="#" class="liveembed">{#live__embed#}</a>
      {/if}
      | <a href="{$language}/live/chatadmin/{$feed.id}">{#live__chatadmin#}</a>
      | <a href="{$language}/live/chatexport/{$feed.id}">{#live__chatexport#}</a>
   </td>
    <td class="streamcolumn">
      <table class="stream">
      {foreach from=$feed.streams item=stream}
        <tr>
          <td class="streamname">
            {if $feed.feedtype != 'vcr'}
              <a href="{$language}/live/view/{$feed.id},{$stream.id},{$feed.name|filenameize}"><b>{$stream.name|escape:html}</b></a>
            {/if}
          </td>
          <td class="streamquality">{$stream.quality|escape:html}</td>
          <td class="streamcompatibility nobr">
            {if $stream.isdesktopcompatible}<img src="{$STATIC_URI}images/icons/desktop.png" title="Desktop" alt="Desktop"/>{/if}
            {if $stream.isioscompatible}<img src="{$STATIC_URI}images/icons/ios.png" title="iOS" alt="iOS"/>{/if}
            {if $stream.isandroidcompatible}<img src="{$STATIC_URI}images/icons/android.png" title="Android" alt="Android"/>{/if}
          </td>
          <td class="streamactions{if $feed.feedtype == 'vcr'} needpoll" id="stream{$stream.id}" data-streamid="{$stream.id}" data-streamstatus="{$stream.status|escape:html}{/if}">
            <span class="nobr">
              {if $feed.feedtype == 'vcr'}
                {include file=Visitor/Live/Managefeeds_streamaction.tpl stream=$stream}
              {else}
                <a href="#" class="streambroadcastlink">{#live__streambroadcastlink#}</a> |
                <a href="{$language}/live/modifystream/{$stream.id}">{#live__live_edit#}</a> |
                <a href="{$language}/live/deletestream/{$stream.id}" class="confirm" question="{#sitewide_areyousure#|escape:html}">{#live__live_delete#}</a>
              {/if}
            </span>
          </td>
        </tr>
        <tr class="streambroadcastwrap form">
          <td colspan="4" class="elementcolumn">
            <div class="broadcastlink">
              <label for="broadcastlink-{$stream.id}">{#live__streambroadcastlink#}:</label>
              <input id="broadcastlink-{$stream.id}" type="text" value="{$ingressurl|escape:html}{$stream.keycode|escape:html}"/>
            </div>
            {if $stream.contentkeycode}
              <div class="broadcastlink">
                <label for="broadcastlink-{$stream.id}-2">{#live__secondarystreambroadcastlink#}:</label>
                <input id="broadcastlink-{$stream.id}-2" type="text" value="{$ingressurl|escape:html}{$stream.contentkeycode|escape:html}"/>
              </div>
            {/if}
          </td>
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
  {if !empty( $feed.streams )}
  <tr class="liveembedrow">
    <td colspan="3">
      <div class="liveembedwrap" data-embedurl="{$BASE_URI}live/view/{$feed.id},{$feed.name|filenameize}?chromeless=true">
        <span class="label">{#live__embed_info#}</span>
        {if $feed.moderationtype != 'nochat'}
        <div class="option">
          <label for="chat" class="label">{#live__chat#}</label>
          <input type="radio" class="chat" name="chat_{$feed.id}" id="chat_no_{$feed.id}" value="0"/>
          <label for="chat_no_{$feed.id}">{#live__chat_no#}</label>
          <input type="radio" class="chat" name="chat_{$feed.id}" id="chat_yes_{$feed.id}" checked="checked" value="1"/>
          <label for="chat_yes_{$feed.id}">{#live__chat_yes#}</label>
        </div>
        {/if}
        <div class="option">
          <label for="fullplayer" class="label">{#live__fullplayer#}:</label><br/>
          <input type="radio" class="fullplayer" name="fullplayer_{$feed.id}" id="fullplayer_yes_{$feed.id}" value="1" checked="checked"/>
          <label for="fullplayer_yes_{$feed.id}">{#live__fullplayer_yes#}</label><br/>
          <input type="radio" class="fullplayer" name="fullplayer_{$feed.id}" id="fullplayer_no_{$feed.id}" value="0"/>
          <label for="fullplayer_no_{$feed.id}">{#live__fullplayer_no#}</label>
        </div>
        {capture assign=liveembed}
          <iframe width="950" height="{if $feed.moderationtype == 'nochat'}530{else}860{/if}" src="{$BASE_URI}live/view/{$feed.id},{$feed.name|filenameize}?chromeless=true{if $feed.moderationtype == 'nochat'}&chat=false{/if}" frameborder="0" allowfullscreen="allowfullscreen"></iframe>
        {/capture}
        <textarea onclick="this.select();">{$liveembed|trim|escape:html}</textarea>
      </div>
    </td>
  </tr>
  {/if}
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