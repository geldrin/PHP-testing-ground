<li class="listingitem">
  <div class="row">
    <h3>{$item.email} - {$item|@nickformat|escape:html}</h3>
    <ul class="actions">
      <li><a href="{$language}/users/edit/{$item.id}?forward={$FULL_URI|escape:url}">{#modify#}</a></li>
      {if $item.id != $member.id and !$item.disabled}
        <li><a href="{$language}/users/disable/{$item.id}" class="confirm">{#users__disable#}</a></li>
      {elseif $item.disabled}
        <li>{#users__disabled#}</li>
      {/if}
    </ul>
  </div>
</li>
