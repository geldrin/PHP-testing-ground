<li class="listingitem">
  <div class="row">
    <h3>{$item.name|escape:html} ({if !$item.source}{#groups__source_default#}{else}{#groups__source_directory#}{/if}, {$item.usercount|numberformat} {#groups__usercount#})</h3>
    {if $member|@userHasPrivilege:'groups_deleteanyuser':'or':'admin':'isclientadmin':'iseditor' or $member.id == $item.userid}
      <ul class="actions">
        <li><a href="{$language}/groups/users/{$item.id},{$item.name|filenameize}">{#groups__users#}</a></li>
        <li><a href="{$language}/groups/modify/{$item.id}">{#groups__modify#}</a></li>
        {if !$item.ispermanent}
        <li><a href="{$language}/groups/delete/{$item.id}" class="confirm">{#groups__delete#}</a></li>
        {/if}
      </ul>
    {/if}
  </div>
</li>
