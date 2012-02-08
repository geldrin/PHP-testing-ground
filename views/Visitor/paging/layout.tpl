{include file="Visitor/_header.tpl" title=$title}

{if count( $insertbeforepager ) }

  {foreach from=$insertbeforepager item=insertitem}
    {include file=$insertitem}
  {/foreach}

{/if}
{if $pager}
  {$pager}
{/if}

{foreach from=$insertbefore item=insertitem}
  {include file=$insertitem}
{/foreach}

{if count( $items )}

  {foreach name=paging from=$items item=item}

    {include file=$template item=$item}

  {/foreach}

  {if $lastiteminclude}
    {include file=$lastiteminclude}
  {/if}

{else}

  {if $foreachelse}
  <div class="foreachelse">
    <p>{$foreachelse}</p>
  </div>
  {/if}

{/if}

{foreach from=$insertafter item=insertitem}
  {include file=$insertitem}
{/foreach}

{if count( $insertafterpager ) }

  {foreach from=$insertafterpager item=insertitem}
    {include file=$insertitem}
  {/foreach}

{/if}

{include file="Visitor/_footer.tpl"}