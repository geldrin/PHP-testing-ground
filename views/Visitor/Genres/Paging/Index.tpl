{if $smarty.foreach.paging.first and $member->isadmin}
  <a href="{$language}/genres/create">{#genres__create#}</a><br/>
{/if}
{$item.name} -
{if $member->isadmin}<a href="{$language}/genres/modify/{$item.id}">{#genres__modify#}</a> - {/if}
<a href="{$language}/genres/details/{$item.id}">{#genres__details#}</a><br/>
{if !empty( $item.children )}
<div style="padding-left: 20px">
  {foreach from=$item.children item=item}
    {$item.name} -
    {if $member->isadmin}<a href="{$language}/genres/modify/{$item.id}">{#genres__modify#}</a> - {/if}
    <a href="{$language}/genres/details/{$item.id}">{#genres__details#}</a><br/>
  {/foreach}
</div>
{/if}
