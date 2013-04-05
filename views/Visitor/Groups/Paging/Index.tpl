<li class="listingitem">
  <div class="row">
    <h3><a href="{$language}/groups/details/{$item.id},{$item.name|filenameize}">{$item.name|escape:html}</a></h3>
    <ul class="actions">
      <li><a href="{$language}/groups/modify/{$item.id}">{#groups__modify#}</a></li>
      {if empty( $item.children )}<li><a href="{$language}/groups/delete/{$item.id}" class="confirm">{#groups__delete#}</a></li>{/if}
    </ul>
  </div>
</li>
