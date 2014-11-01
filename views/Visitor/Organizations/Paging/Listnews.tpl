<li class="listingitem{if $smarty.foreach.paging.last} last{/if}">
  
  <h2><a href="{$language}/organizations/newsdetails/{$item.id},{$item.title|filenameize}">{$item.title|escape:html|mb_wordwrap:25}</a><span class="subtitle">{$item.starts|date_format:#smarty_dateformat_long#}</span></h2>
  <p>{$item.lead|escape:html|nl2br}</p>
  {if $canadminister}
    <a href="{$language}/organizations/modifynews/{$item.id}?forward={$FULL_URI|escape:url}">{#modify#}</a> |
  {/if}
  <a href="{$language}/organizations/newsdetails/{$item.id},{$item.title|filenameize}" class="more">{#index__more#}</a>
  
</li>
