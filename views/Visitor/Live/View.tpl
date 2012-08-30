{if $smarty.request.chromeless}
  {include file="Visitor/_header_nolayout.tpl" title=$rootchannel.title islive=true bodyclass=liveiframe}
  {assign var=linksinnewwindow value=' target="_blank"'}
{else}
  {include file="Visitor/_header.tpl" title=$rootchannel.title islive=true}
{/if}

<div class="title">
  {if count( $streams ) > 1}
    <div id="streams">
      <span>{#live__recordings_streams#}:</span>
      <div>
        {foreach from=$streams item=stream name=streams}
          {if $currentstream.id == $stream.id}
            <b title="{$stream.name|escape:html}">{$stream.name|mb_truncate:30|escape:html}</b>
          {else}
            <a title="{$stream.name|escape:html}" href="{$language}/live/view/{$feed.id},{$stream.id},{$feed.name|filenameize}">{$stream.name|mb_truncate:30|escape:html}</a>
          {/if}
          {if !$smarty.foreach.streams.last} | {/if}
        {/foreach}
      </div>
    </div>
  {/if}
  <h1>{$channel.title|escape:html}</h1>
  {if $channel.subtitle|stringempty}<h2>{$channel.subtitle|escape:html}</h2>{/if}
  {*}
    {if $rootchannel.ordinalnumber}{#live__ordinalnumber#}: {$rootchannel.ordinalnumber|escape:html}<br/>{/if}
    {$rootchannel.channeltype}{if $rootchannel.starttimestamp}, {"%Y. %B %e"|shortdate:$rootchannel.starttimestamp:$rootchannel.endtimestamp}{/if}
    {if $rootchannel.url}<br/><a href="{$rootchannel.url|escape:html}">{$rootchannel.url|truncate:50|escape:html}</a>{/if}
    {if $rootchannel.description}<br/><p>{$rootchannel.description|escape:html}</p>{/if}
  {/*}
</div>

{if !$browser.mobile and ( $currentstream.feedtype == 'normal' or $currentstream.feedtype == 'normal/mobile' )}
  <script type="text/javascript">
    swfobject.embedSWF('flash/TCPlayer.swf?v={$VERSION}', 'playercontainer', '950', '530', '11.1.0', 'flash/swfobject/expressInstall.swf', {$flashdata|@jsonescape:true}, flashdefaults.params );
  </script>
  <div id="playercontainer">{#recordings__noflash#}</div>
{elseif $browser.mobile and ( $currentstream.feedtype == 'mobile' or $currentstream.feedtype == 'normal/mobile' )}
  <center>
    {if $browser.mobiledevice == 'iphone'}
      <div id="mobileplayercontainer">
        <video x-webkit-airplay="allow" controls="controls" alt="{$channel.title|escape:html}" width="192" height="144" poster="{$STATIC_URI}images/videothumb_player_placeholder.png" src="{$livehttpurl}">
          <a href="{$livehttpurl}"><img src="{$STATIC_URI}images/videothumb_player_placeholder.png" width="220" height="130"/></a>
        </video>
      </div>
    {else}
      <div id="mobileplayercontainer">
        <a href="{$livertspurl}"><img src="{$STATIC_URI}images/videothumb_player_placeholder.png" width="220" height="130"/></a>
      </div>
    {/if}
  </center>
{/if}

<div class="clear"></div><br/>

<script type="text/javascript">
 var chatpollurl  = '{$language}/live/getchat/{$feed.id}';
 var chatpolltime = {$chatpolltime};
</script>

<div id="chat">
  <div class="title">
    {#live__chat_title#}
  </div>
  
  <div id="chatcontainer" data-lastmodified="{$lastmodified}">
    {$chat}
  </div>
  {if $member.id}
    <form enctype="multipart/form-data" id="live_createchat" name="live_createchat" action="{$language}/live/createchat/{$feed.id}" method="post">
      <input type="hidden" id="action" name="action" value="submitcreatechat"/>
      <label for="text">{#live__chat_message#}:</label>
      <input type="text" name="text" id="text" value=""/>
      <input type="submit" value="{#live__chat_submit#}"/>
    </form>
  {/if}
  <br/>
</div>

{if $smarty.request.chromeless}
  {include file="Visitor/_footer_nolayout.tpl"}
{else}
  {include file="Visitor/_footer.tpl"}
{/if}