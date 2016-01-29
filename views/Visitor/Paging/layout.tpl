{include file="Visitor/_header.tpl" title=$title}

{if count( $insertbeforepager ) }

  {foreach from=$insertbeforepager item=insertitem}
    {include file=$insertitem}
  {/foreach}

{/if}

{assign var=itemcount value=$itemcount|numberformat}
<div id="itemcount">{#paging_itemcount#|sprintf:$itemcount}</div>

<div id="perpageselector">
  <div class="sort">
    <div class="item">
      {foreach from=$validperpages item=page}
        {if $page == $perpage}
          <a class="title" href="{perpageurlparam perpage=$page}">{$page} {#paging_itemsperpage#} <div class="sortarrow sortarrow-down"></div></a>
        {/if}
      {/foreach}
      <ul>
      {foreach from=$validperpages item=page}
        <li><a href="{perpageurlparam perpage=$page}">{$page} {#paging_itemsperpage#}</a></li>
      {/foreach}
      </ul>
    </div>
  </div>
</div>
<div class="clear"></div>

{foreach from=$insertbefore item=insertitem}
  {include file=$insertitem}
{/foreach}

{if count( $items )}
<ul{if $listclass} class="{$listclass}"{/if}>
  {foreach name=paging from=$items item=item}

    {include file=$template item=$item}

  {/foreach}

  {if $lastiteminclude}
    {include file=$lastiteminclude}
  {/if}
</ul>
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

{if $pager}
  {$pager}
{/if}

{if count( $insertafterpager ) }

  {foreach from=$insertafterpager item=insertitem}
    {include file=$insertitem}
  {/foreach}

{/if}

{include file="Visitor/_footer.tpl"}