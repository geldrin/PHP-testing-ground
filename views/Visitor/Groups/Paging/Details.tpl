<li class="listingitem">
  <div class="row">
    <h3>{$item|@nickformat|escape:html}</h3>
    <ul class="actions">
      <li><a href="{$language}/groups/deleteuser/{$group.id}?userid={$item.id}" class="confirm">{#groups__delete#}</a></li>
    </ul>
  </div>
</li>
