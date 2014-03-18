<li class="listingitem">
  <div class="row">
    <h3><a href="{$language}/channels/details/{$item.id},{$item.title|filenameize}">{$item.title|escape:html}</a></h3>
    <ul class="actions">
      <li><a href="{$language}/channels/create?parent={$item.id}">{#channels__createchild#}</a></li>
      <li><a href="{$language}/channels/modify/{$item.id}">{#channels__modify#}</a></li>
      {if empty( $item.children )}<li><a href="{$language}/channels/delete/{$item.id}" class="confirm">{#channels__delete#}</a></li>{/if}
    </ul>
    {if $item.subtitle}<div class="subtitle">{$item.subtitle|escape:html}</span>{/if}
  </div>
  {if !empty( $item.children )}
    <ul class="treeadminlist children">
      {foreach from=$item.children item=childitem}
        {include file=Visitor/Channels/Paging/Mychannels.tpl item=$childitem}
      {/foreach}
    </ul>
  {/if}
</li>
