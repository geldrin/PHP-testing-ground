{if $smarty.foreach.paging.first}
  <a href="{$language}/categories/create">{l module=categories key=create}</a><br/>
{/if}
{$item.name} - <a href="{$language}/categories/modify/{$item.id}">{l module=categories key=modify}</a><br/>
