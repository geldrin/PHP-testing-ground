{if $chromeless}
  {include file="Visitor/_header_nolayout.tpl" title=$rootchannel.title islive=true bodyclass=liveiframe}
  {assign var=linksinnewwindow value=' target="_blank"'}
{else}
  {include file="Visitor/_header.tpl" islive=true pagebgclass=fullheight}

  <div class="title recording">
    {assign var=numberofviews value=$feed.numberofviews|numberformat}
    <div id="liveviews">{#live__numberofviews#|sprintf:$numberofviews}</div>
    <h1>{$channel.title|escape:html|mb_wordwrap:25}</h1>
    {if $channel.subtitle|stringempty}<h2>{$channel.subtitle|escape:html|mb_wordwrap:25}</h2>{/if}
  </div>
  <div class="clear"></div>
{/if}
{if $chromeless}
<center>
{/if}
<div id="player">
{if $streamtype == 'desktop' and !$browser.mobile}
  <script type="text/javascript">
    swfobject.embedSWF('flash/VSQPlayer.swf?v={$VERSION}', 'playercontainer', '{$playerwidth}', '{$playerheight}', '11.1.0', 'flash/swfobject/expressInstall.swf', {$flashdata|@jsonescape:true}, flashdefaults.params, null, handleFlashLoad );
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
        <a href="{if $bootstrap->config.liveandroidhls}{$livehttpurl}{else}{$livertspurl}{/if}"><img src="{$STATIC_URI}images/live_player_placeholder_small.png" width="220" height="130"/></a>
      </div>
    {else}
      <span class="warning">{#live__no_compatible_stream#}</span>
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
    <a href="#" id="chatnewmessages" style="display: none;">{#live__chatnewmessages#}</a>
    <div class="title">
      {#live__chat_title#}
    </div>
    
    <div id="chatcontainer" data-lastmodified="{$lastmodified}" data-ownnick="{if $member.id}{$member|@nickformat|escape:html}{else}{#live__chat_anonuser#|sprintf:$anonuser.anonuserid}{/if}">
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