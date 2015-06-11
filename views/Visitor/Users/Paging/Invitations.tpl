<li class="listingitem">
  {if $previoustimestamp != $item.timestamp}
    {assign var=previoustimestamp value=$item.timestamp}
    <h2>{$item.timestamp}</h2>
  {/if}
  <div class="row">
    <h3>{$item.email|escape:html}</h3>
    <div class="invite-status status-{$item.status}">{$l->getLov('invite_status', $language, $item.status)}</div>
    <ul class="actions">
      <li><a href="{$language}/users/editinvite/{$item.id}?forward={$FULL_URI|escape:url}">{#users__invitation_modify#}</a></li>
      {if $item.status == 'invited'}
        <li><a href="{$language}/users/resendinvitation/{$item.id}?forward={$FULL_URI|escape:url}" class="confirm">{#users__invitation_resend#}</a></li>
      {/if}
      {if $item.status != 'deleted'}
        <li><a href="{$language}/users/disableinvitation/{$item.id}?forward={$FULL_URI|escape:url}" class="confirm">{#users__invitation_disable#}</a></li>
      {elseif $item.status == 'deleted'}
        <li>{#users__invitation_disabled#}</li>
      {/if}
    </ul>
  </div>
</li>
