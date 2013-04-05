<li class="listingitem">
  <div class="row">
    <h3>{$item.name|escape:html}</h3>
    <ul class="actions">
      <li><a href="{$language}/genres/modify/{$item.id}">{#genres__modify#}</a></li>
      {if empty( $item.children )}<li><a href="{$language}/genres/delete/{$item.id}" class="confirm">{#genres__delete#}</a></li>{/if}
    </ul>
  </div>
  {if !empty( $item.children )}
    <ul class="treeadminlist children">
      {foreach from=$item.children item=childitem}
        {include file=Visitor/Genres/Paging/Admin.tpl item=$childitem}
      {/foreach}
    </ul>
  {/if}
</li>
