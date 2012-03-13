<li class="listingitem">
  <div class="row">
    <h3><a href="{$language}/categories/details/{$item.id},{$item.name|filenameize}">{$item.name|escape:html}</a></h3>
    <ul class="actions">
      <li><a href="{$language}/categories/modify/{$item.id}">{#categories__modify#}</a></li>
      {if empty( $item.children )}<li><a href="{$language}/categories/delete/{$item.id}" class="confirm">{#categories__delete#}</a></li>{/if}
    </ul>
  </div>
  {if !empty( $item.children )}
    <ul class="treeadminlist children">
      {foreach from=$item.children item=childitem}
        {include file=Visitor/Categories/Paging/Admin.tpl item=$childitem}
      {/foreach}
    </ul>
  {/if}
</li>
