{if $smarty.foreach.paging.first}
  <a href="{$language}/genres/create">{#genres__create#}</a><br/>
{/if}
{$item.name} - <a href="{$language}/genres/modify/{$item.id}">{#genres__modify#}</a><br/>
