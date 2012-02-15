{if $smarty.foreach.paging.first}
  <a href="{$language}/genres/create">{l module=genres key=create}</a><br/>
{/if}
{$item.name} - <a href="{$language}/genres/modify/{$item.id}">{l module=genres key=modify}</a><br/>
