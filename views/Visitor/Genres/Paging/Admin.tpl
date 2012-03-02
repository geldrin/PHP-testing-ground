<li class="listingitem">
  <div class="row">
    <h3><a href="{$language}/genres/details/{$item.id},{$item.name|filenameize}">{$item.name|escape:html}</a></h3>
    <ul class="actions">
      <li><a href="{$language}/genres/modify/{$item.id}">{l module=genres key=modify}</a></li>
      {if empty( $item.children )}<li><a href="{$language}/genres/delete/{$item.id}" class="confirm">{l module=genres key=delete}</a></li>{/if}
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
