<div class="title">
  <h1>{$channel.title|escape:html}</h1>
  {if $member.id or $channel.subtitle}
    <h2>
      {if $channel|@userHasAccess}
        <div class="actions">
          <a href="{$language}/live/modify/{$channel.id}">{#modify#}</a> |
          <a href="{$language}/live/delete/{$channel.id}" class="confirm">{#live__live_delete#}</a> |
          <a href="{$language}/live/managefeeds/{$channel.id}">{#live__managefeeds#}</a>
        </div>
      {/if}
      {if $channel.subtitle}{$channel.subtitle|escape:html}{else}&nbsp;{/if}
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
