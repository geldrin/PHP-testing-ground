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
</div>

{assign var=type value=$currentstream.feedtype|ucfirst}
{assign var=embedfile value="Visitor/Live/Embeds/$type.tpl"}
{capture assign=main}
  {if $currentstream.feedtype == 'flash'}
    <script type="text/javascript">
      swfobject.embedSWF('flash/TCPlayer{$VERSION}.swf', 'playercontainer', '950', '530', '11.1.0', 'flash/swfobject/expressInstall.swf', {$flashdata|@jsonescape:true}, flashdefaults.params );
    </script>
    <div id="playercontainer">{#recordings__noflash#}</div>
  {else}
    {include file=$embedfile aspectratio=$currentstream.aspectratio url=$currentstream.streamurl keycode=$currentstream.keycode htmlid="stream" external=$feed.isexternal }
  {/if}
{/capture}

{capture assign=content}
  {if $feed.numberofstreams == 2 and $currentstream.feedtype != 'flash'}
    {include file=$embedfile aspectratio=$currentstream.contentaspectratio url=$currentstream.contentstreamurl keycode=$currentstream.contentkeycode htmlid="contentstream" external=$feed.isexternal }
  {/if}
{/capture}

{if $feed.slideonright}
  {$main}
  {$content}
{else}
  {$content}
  {$main}
{/if}

<div class="clear"></div><br/>

{if $smarty.request.chromeless}
  {include file="Visitor/_footer_nolayout.tpl"}
{else}
  {include file="Visitor/_footer.tpl"}
{/if}