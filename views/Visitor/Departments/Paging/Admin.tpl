<li class="listingitem">
  <div class="row">
    <h3><a href="{$language}/departments/details/{$item.id},{$item.name|filenameize}">{$item.name|escape:html}</a></h3>
    <ul class="actions">
      <li><a href="{$language}/departments/modify/{$item.id}">{#departments__modify#}</a></li>
      {if empty( $item.children )}<li><a href="{$language}/departments/delete/{$item.id}" class="confirm">{#departments__delete#}</a></li>{/if}
    </ul>
  </div>
  {if !empty( $item.children )}
    <ul class="treeadminlist children">
      {foreach from=$item.children item=childitem}
        {include file=Visitor/Departments/Paging/Admin.tpl item=$childitem}
      {/foreach}
    </ul>
  {/if}
</li>
