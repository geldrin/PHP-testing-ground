{if $chromeless}
  {include file="Visitor/_header_nolayout.tpl" title=$rootchannel.title islive=true bodyclass=liveiframe}
  {assign var=linksinnewwindow value=' target="_blank"'}
{else}
  {include file="Visitor/_header.tpl" title=$rootchannel.title islive=true pagebgclass=fullheight}
{/if}

<div class="title recording">
  <h1>{$channel.title|escape:html}</h1>
  {if count( $streams ) > 1}
    <div id="quality">
      {#live__quality#}:
      {foreach from=$streams item=stream name=streams}
        {if $currentstream.id == $stream.id}
          <b title="{$stream.name|escape:html}">{$stream.name|mb_truncate:30|escape:html}</b>
        {else}
          <a title="{$stream.name|escape:html}" href="{$language}/live/view/{$feed.id},{$stream.id},{$feed.name|filenameize}{$urlparams}">{$stream.name|mb_truncate:30|escape:html}</a>
        {/if}
        {if !$smarty.foreach.streams.last} | {/if}
      {/foreach}
    </div>
  {/if}
  {if $channel.subtitle|stringempty}<h2>{$channel.subtitle|escape:html}</h2>{/if}
</div>
<div class="clear"></div>
{if $chromeless}
<center>
{/if}
<div id="player">
{if $streamtype == 'desktop'}
  <script type="text/javascript">
    swfobject.embedSWF('flash/VSQPlayer.swf?v={$VERSION}', 'playercontainer', '{$playerwidth}', '{$playerheight}', '11.1.0', 'flash/swfobject/expressInstall.swf', {$flashdata|@jsonescape:true}, flashdefaults.params );
  </script>
  <div id="playercontainer" style="width: {$playerwidth}px; height: {$playerheight}px">{#recordings__noflash#}</div>
{elseif $needauth}
  {include file=Visitor/mobile_logintoview.tpl}
{else}
  <center>
    {if $streamtype == 'ios'}
      <div id="mobileplayercontainer">
        <video x-webkit-airplay="allow" controls="controls" alt="{$channel.title|escape:html}" width="192" height="144" poster="{$STATIC_URI}images/live_player_placeholder_small.png" src="{$livehttpurl}">
          <a href="{$livehttpurl}"><img src="{$STATIC_URI}images/live_player_placeholder_small.png" width="220" height="130"/></a>
        </video>
      </div>
    {elseif $streamtype == 'android'}
      <div id="mobileplayercontainer">
        <a href="{$livertspurl}"><img src="{$STATIC_URI}images/live_player_placeholder_small.png" width="220" height="130"/></a>
      </div>
    {/if}
  </center>
{/if}
</div>
{if $chromeless}
</center>
{/if}
{if $displaychat and ( $streamtype == 'desktop' or !$needauth )}
  
  <div class="clear"></div><br/>
  
  <script type="text/javascript">
    var chatpollurl  = '{$language}/live/getchat/{$feed.id}';
    var chatpolltime = {$chatpolltime};
    var chatloginurl = '{$language}/live/refreshchatinput/{$feed.id}';
  </script>
  
  <div id="chat">
    <div class="title">
      {#live__chat_title#}
    </div>
    
    <div id="chatcontainer" data-lastmodified="{$lastmodified}">
      {$chat}
    </div>
    <div id="chatinputcontainer">
      {include file=Visitor/Live/Chatinput.tpl}
    </div>
    <br/>
  </div>
{/if}

{if $smarty.request.chromeless}
  {include file="Visitor/_footer_nolayout.tpl"}
{else}
  {include file="Visitor/_footer.tpl"}
{/if}