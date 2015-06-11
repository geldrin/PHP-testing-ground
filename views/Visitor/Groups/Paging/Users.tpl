<li class="listingitem">
  <div class="row">
    <h3>{$item.email|default:$item.externalid|escape:html} - {$item|@nickformat|escape:html}</h3>
    <ul class="actions">
      {if $member.isadmin or $member.isclientadmin}
        <li><a href="{$language}/users/info/{$item.id}?forward={$FULL_URI|escape:url}">{#users__info#}</a></li>
        <li><a href="{$language}/users/edit/{$item.id}?forward={$FULL_URI|escape:url}">{#modify#}</a></li>
      {/if}
      {if $group.source != 'directory'}
        <li><a href="{$language}/groups/deleteuser/{$group.id}?userid={$item.id}" class="confirm">{#groups__users_delete#}</a></li>
      {/if}
    </ul>
  </div>
</li>
