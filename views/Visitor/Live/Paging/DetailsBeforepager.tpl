<div id="channelheader">
  <div class="channelimage">
    <img src="{$channel|@indexphoto:player}"/>
  </div>
  <div class="channelinfowrap">
    <h1>{$channel.title|escape:html|mb_wordwrap:50}</h1>
    {if $channel.subtitle}
      <h2>{$channel.subtitle|escape:html|mb_wordwrap:50}</h2>
    {/if}
    <div class="channeltype">{$channel.channeltype|escape:html}</div>
    {if $channel.starttimestamp or $channel.location}
    <div class="channelinfo">
      {if $channel.starttimestamp}
        {"%Y. %B %e"|shortdate:$channel.starttimestamp:$channel.endtimestamp}
      {/if}
      {if $channel.location}
        {if $channel.starttimestamp},{/if}
        {$channel.location|escape:html}
      {/if}
    </div>
    {/if}
  </div>
</div>

{if $channel|@userHasAccess or $channel.relatedchannelid}
  <div id="channeldescription">
    {if $channel|@userHasAccess or $channel.relatedchannelid}
      <div class="actions">
        {if $channel|@userHasAccess}
          <a href="{$language}/live/modify/{$channel.id}" class="submitbutton">{#modify#}</a>
          <a href="{$language}/live/delete/{$channel.id}" class="confirm submitbutton">{#live__live_delete#}</a>
          <a href="{$language}/live/managefeeds/{$channel.id}" class="submitbutton">{#live__managefeeds#}</a>
          {if !$channel.relatedchannelid}
            <a href="{$language}/live/archive/?channelid={$channel.id}&amp;forward={$FULL_URI|escape:url}" class="confirm submitbutton">{#live__archive#}</a>
          {/if}
        {/if}
        {if $channel.relatedchannelid}
          <a href="{$language}/channels/details/{$channel.relatedchannelid},{$channel.title|filenameize}" class="submitbutton">{#live__view_archive#}</a>
        {/if}
      </div>
    {/if}
    <p>{$channel.description|escape:html|nl2br}</p>
  </div>
{/if}

<div class="heading categories title"></div>
<div class="channelgradient"></div>

<h3><b>{#live__bystreams#}</b></h3>
