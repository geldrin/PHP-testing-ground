
<div class="sort">
  <div class="item">
    <a class="title" href="{$url|replace:'%order%':$orders.activeKey}">{$orders.activeLabel}</a>
    <ul>
      {foreach from=$orders.items item=item}
        <li><a href="{$url|replace:'%order%':$item.sortkey}">{$item.label}</a></li>
      {/foreach}
    </ul>
  </div>
</div>
