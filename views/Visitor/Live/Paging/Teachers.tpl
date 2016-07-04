<li class="listingitem">
  <div class="row">
    <h3>{$item.email|escape:html}</h3>
    <ul class="actions">
      <li><a href="{$language}/live/deleteteacher/{$feed.id}?liveteacherid={$item.id}" class="confirm">{#live__teacher_delete#}</a></li>
    </ul>
  </div>
</li>
