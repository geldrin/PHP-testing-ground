<li class="listingitem">
  <div class="row">
    <h3><a href="{$language}/groups/recordings/{$item.id},{$item.name|filenameize}">{$item.name|escape:html}</a></h3>
    {if $member.admin or $member.isclientadmin or $member.iseditor or $member.id == $item.userid}
      <ul class="actions">
        <li><a href="{$language}/groups/details/{$item.id},{$item.name|filenameize}">{#groups__details#}</a></li>
        <li><a href="{$language}/groups/modify/{$item.id}">{#groups__modify#}</a></li>
        <li><a href="{$language}/groups/delete/{$item.id}" class="confirm">{#groups__delete#}</a></li>
      </ul>
    {/if}
  </div>
</li>
