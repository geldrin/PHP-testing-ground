{if $smarty.foreach.paging.first}
  <a href="{$language}/channels/create">{#channels__create#}</a><br/>
{/if}
{$item.title} - <a href="{$language}/channels/modify/{$item.id}">{#channels__modify#}</a> -
<a href="{$language}/channels/details/{$item.id}">{#channels__details#}</a><br/>
