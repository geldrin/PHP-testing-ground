<li class="listingitem">
  <div class="row">
    <h3>{$item|@nickformat|escape:html}</h3>
    <ul class="actions">
      {if $member.isadmin or $member.isclientadmin}
        <li><a href="{$language}/users/edit/{$item.id}?forward={$FULL_URI|escape:url}">{#modify#}</a></li>
      {/if}
      <li><a href="{$language}/groups/deleteuser/{$group.id}?userid={$item.id}" class="confirm">{#groups__delete#}</a></li>
    </ul>
  </div>
</li>
