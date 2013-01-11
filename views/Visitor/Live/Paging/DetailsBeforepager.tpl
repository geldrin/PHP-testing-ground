<div class="title">
  <h1>{$channel.title|escape:html} ({"%Y. %B %e"|shortdate:$channel.starttimestamp:$channel.endtimestamp})</h1>
  {if $member.id or $channel.subtitle}
    <h2>
      {if $channel|@userHasAccess}<div class="actions"><a href="{$language}/live/modify/{$channel.id}">{#modify#}</a> | <a href="{$language}/live/managefeeds/{$channel.id}">{#live__managefeeds#}</a></div>{/if}
      {if $channel.subtitle}{$channel.subtitle|escape:html}{else}&nbsp;{/if}
    </h2>
  {/if}
</div>

<div class="events">
  <div class="treeview">
    {if !empty( $feeds )}
      <div class="eventfeeds">
        <h2>{#live__bystreams#}</h2>
        {foreach from=$feeds item=feed}
          <div class="feed">
            {if $streamingactive}
              <a href="{$language}/live/view/{$feed.id},{$feed.name|filenameize}" class="livefeed" title="{#live__feedislive#}">{#live__feedislive#}</a>
            {/if}
            <h3><a href="{$language}/live/view/{$feed.id},{$feed.name|filenameize}">{$feed.name|mb_wordwrap:30|escape:html}</a></h3>
          </div>
        {/foreach}
      </div>
      <div class="clear">&nbsp;</div>
    {/if}
    
    {if !empty( $channeltree[0].children ) or $channel|@userHasAccess}
      <div class="eventfeeds">
        <h2>{#live__bychannels#}</h2>
      </div>
      <div class="channeltree">
        {foreach from=$channeltree item=item}
          {include file="Visitor/Live/Paging/DetailsChildren.tpl" child=$item}
          <div class="clear">&nbsp;</div>
        {/foreach}
      </div>
    {/if}
    
  </div>
  
  <div class="channelrecordings">