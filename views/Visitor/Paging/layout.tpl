{include file="Visitor/_header.tpl" title=$title}

{if count( $insertbeforepager ) }

  {foreach from=$insertbeforepager item=insertitem}
    {include file=$insertitem}
  {/foreach}

{/if}

{assign var=itemcount value=$itemcount|numberformat}
<div id="itemcount">{#paging_itemcount#|sprintf:$itemcount}</div>

<div id="perpageselector">
  <form id="perpageform" method="GET">
    {hiddenurlparams}
    <select id="perpage" name="perpage" onchange="this.form.submit();">
      {foreach from=$validperpages item=page}
        <option{if $page == $perpage} selected="selected"{/if} value="{$page}">{$page}</option>
      {/foreach}
    </select>
    <label for="perpage" class="perpagelabel">{#paging_itemsperpage#}</label>
  </form>
</div>

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