{if $smarty.foreach.paging.first and $member and $member->isadmin}
  <a href="{$language}/categories/create">{l module=categories key=create}</a><br/>
{/if}
{$item.name} -
{if $member and $member->isadmin}<a href="{$language}/categories/modify/{$item.id}">{l module=categories key=modify}</a> -{/if}
<a href="{$language}/categories/details/{$item.id}">{l module=categories key=details}</a><br/>
