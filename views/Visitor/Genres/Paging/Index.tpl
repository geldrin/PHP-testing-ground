{if $smarty.foreach.paging.first and $member->isadmin}
  <a href="{$language}/genres/create">{l module=genres key=create}</a><br/>
{/if}
{$item.name} -
{if $member->isadmin}<a href="{$language}/genres/modify/{$item.id}">{l module=genres key=modify}</a> - {/if}
<a href="{$language}/genres/details/{$item.id}">{l module=genres key=details}</a><br/>
{if !empty( $item.children )}
<div style="padding-left: 20px">
  {foreach from=$item.children item=item}
    {$item.name} -
    {if $member->isadmin}<a href="{$language}/genres/modify/{$item.id}">{l module=genres key=modify}</a> - {/if}
    <a href="{$language}/genres/details/{$item.id}">{l module=genres key=details}</a><br/>
  {/foreach}
</div>
{/if}
