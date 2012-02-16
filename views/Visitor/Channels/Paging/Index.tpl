{if $smarty.foreach.paging.first}
  <a href="{$language}/channels/create">{l module=channels key=create}</a><br/>
{/if}
{$item.title} - <a href="{$language}/channels/modify/{$item.id}">{l module=channels key=modify}</a> -
<a href="{$language}/channels/details/{$item.id}">{l module=channels key=details}</a><br/>
