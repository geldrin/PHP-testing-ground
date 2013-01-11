<li class="listingitem{if $smarty.foreach.paging.last} last{/if}">
  <h2><a href="{$language}/live/details/{$item.id},{$item.title|filenameize}">{$item.title|escape:html}</a> ({"%Y. %B %e"|shortdate:$item.starttimestamp:$item.endtimestamp})</h2>
  {if $item.subtitle}<h3>{$item.subtitle|escape:html}</h3>{/if}
  {if $item.description}<p>{$item.description|mb_truncate:400|escape:html}</p>{/if}
</li>
