<div id="pagetitle">
  <h1>{$channel.title|escape:html|mb_wordwrap:25}</h1>
</div>
<div class="channelgradient"></div>
<br/>
<div class="title">
  {if $member.id or $channel.subtitle}
    <h2>
      {if $channel|@userHasAccess or $channel.relatedchannelid}
        <div class="actions">
          {if $channel|@userHasAccess}
            <a href="{$language}/live/modify/{$channel.id}">{#modify#}</a> |
            <a href="{$language}/live/delete/{$channel.id}" class="confirm">{#live__live_delete#}</a> |
            <a href="{$language}/live/managefeeds/{$channel.id}">{#live__managefeeds#}</a>
            {if !$channel.relatedchannelid}
              | <a href="{$language}/live/archive/?channelid={$channel.id}&amp;forward={$FULL_URI|escape:url}" class="confirm">{#live__archive#}</a>
            {else}
              |
            {/if}
          {/if}
          {if $channel.relatedchannelid}
            <a href="{$language}/channels/details/{$channel.relatedchannelid},{$channel.title|filenameize}" >{#live__view_archive#}</a>
          {/if}
        </div>
      {/if}
      {if $channel.subtitle}{$channel.subtitle|escape:html|mb_wordwrap:25}{else}&nbsp;{/if}
    </h2>
  {/if}
  {if $channel.starttimestamp}
    <div class="channeltimestamp">{#channels__timestamp#} {"%Y. %B %e"|shortdate:$channel.starttimestamp:$channel.endtimestamp}</div>
  {/if}
  {if $channel.description}
    <p>{$channel.description|escape:html|nl2br}</p>
  {/if}
</div>

<h3><b>{#live__bystreams#}</b></h3>
