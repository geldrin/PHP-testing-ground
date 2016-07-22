<li class="listingitem">
  <div class="row">
    <h3>{$item.email|default:$item.externalid|escape:html} - {$item|@nickformat|escape:html}</h3>
    <ul class="actions">
      {if $member|@userHasPrivilege:'users_edit':'or':'isadmin':'isclientadmin'}
        <li><a href="{$language}/users/info/{$item.id}?forward={$FULL_URI|escape:url}">{#users__info#}</a></li>
        <li><a href="{$language}/users/edit/{$item.id}?forward={$FULL_URI|escape:url}">{#modify#}</a></li>
      {/if}
      <li><a href="{$language}/departments/deleteuser/{$department.id}?userid={$item.id}" class="confirm">{#departments__users_delete#}</a></li>
    </ul>
  </div>
</li>
