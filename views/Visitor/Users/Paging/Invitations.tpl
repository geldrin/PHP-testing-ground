<li class="listingitem">
  <div class="row">
    <h3>{$item.email}</h3>
    <ul class="actions">
      <li><a href="{$language}/users/editinvitation/{$item.id}?forward={$FULL_URI|escape:url}">{#modify#}</a></li>
      <li><a href="{$language}/users/resendinvitation/{$item.id}" class="confirm">{#users__invitation_resend#}</a></li>
      {if $item.status != 'deleted'}
        <li><a href="{$language}/users/disableinvitation/{$item.id}" class="confirm">{#users__disable#}</a></li>
      {else}
        <li>{#users__disabled#}</li>
      {/if}
    </ul>
  </div>
</li>
