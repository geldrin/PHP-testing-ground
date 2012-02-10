{if $smarty.foreach.paging.first}
  <a href="{$language}/users/invite">{l module=users key=invite}</a><br/>
{/if}
{$item.nickname} - {$item.email} {if $item.id != $member->id and !$item.disabled}<a href="{$language}/users/disable/{$item.id}">{l module=users key=disable}</a>{/if}<br/>
