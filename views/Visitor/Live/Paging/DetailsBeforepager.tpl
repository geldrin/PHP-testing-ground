
<div class="events">
  <div class="treeview">
    {if !empty( $feeds )}
      <div class="eventfeeds">
        <h2>{#live__bystreams#}</h2>
        {foreach from=$feeds item=feed}
          <div class="feed">
            <a href="{$language}/live/view/{$feed.id},{$feed.name|filenameize}">{$feed.name|mb_wordwrap:30|escape:html}</a>
          </div>
        {/foreach}
      </div>
      <div class="clear">&nbsp;</div>
    {/if}
    
    {if !empty( $channeltree[0].children ) or $channel|@userHasAccess}
      <div class="eventfeeds">
        <h2>{#live__bychannels#}</h2>
      </div>
      {foreach from=$channeltree item=item}
        {if !empty( $item.children )}
          <div class="children">
            {foreach from=$item.children item=child}
              {include file="Visitor/Live/Paging/DetailsChildren.tpl" child=$child}
            {/foreach}
          </div>
        {/if}
        <div class="clear">&nbsp;</div>{*}csak egy elem van a channeltree elso szintjen{/*}
      {/foreach}
    {/if}
    
    </div>
  <div class="recordview live">