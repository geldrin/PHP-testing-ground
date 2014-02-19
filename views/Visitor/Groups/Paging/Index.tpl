<li class="listingitem">
  <div class="row">
    <h3>{$item.name|escape:html} ({$item.usercount|numberformat} {#groups__usercount#})</h3>
    {if $member.admin or $member.isclientadmin or $member.iseditor or $member.id == $item.userid}
      <ul class="actions">
        <li><a href="{$language}/groups/users/{$item.id},{$item.name|filenameize}">{#groups__users#}</a></li>
        <li><a href="{$language}/groups/modify/{$item.id}">{#groups__modify#}</a></li>
        <li><a href="{$language}/groups/delete/{$item.id}" class="confirm">{#groups__delete#}</a></li>
      </ul>
    {/if}
  </div>
</li>
