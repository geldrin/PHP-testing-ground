<li class="listingitem{if $smarty.foreach.paging.last} last{/if}">
  {if $language == 'hu'}
    {assign var=title value=$item.titlehungarian|default:$item.titleenglish}
    {assign var=lead value=$item.leadhungarian|default:$item.leadenglish}
  {else}
    {assign var=title value=$item.titleenglish|default:$item.titlehungarian}
    {assign var=lead value=$item.leadenglish|default:$item.leadhungarian}
  {/if}
  <h2><a href="{$language}/organizations/newsdetails/{$item.id},{$title|filenameize}">{$title|escape:html}</a><span class="subtitle">{$item.starts|date_format:#smarty_dateformat_long#}</span></h2>
  <p>{$lead|escape:html|nl2br}</p>
  {if $canadminister}
    <a href="{$language}/organizations/modifynews/{$item.id}?forward={$FULL_URI|escape:url}">{#modify#}</a> |
  {/if}
  <a href="{$language}/organizations/newsdetails/{$item.id},{$title|filenameize}" class="more">{#index__more#}</a>
  
</li>
