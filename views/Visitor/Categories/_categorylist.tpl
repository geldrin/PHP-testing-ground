{if !empty( $categories )}
  <ul class="categorylist {$extraclasses}">
  {capture assign=imgbase}
  {if $organization.hascustomcategories}
    {$STATIC_URI}files/organizations/{$organization.id}/
  {else}
    {$STATIC_URI}images/
  {/if}
  {/capture}
  {assign var=imgbase value=$imgbase|trim}
  {foreach from=$categories item=category name=category}
    <li{if $smarty.foreach.category.last} class="last"{/if}>
      <a href="{$language}/categories/details/{$category.id},{$category.name|filenameize}" title="{$category.name|escape:html}">
        <img src="{$imgbase}categories/114/{$category.iconfilename|default:'default.png'}" />
        <h3>{$category.namehyphenated|default:$category.name|escape:html}</h3>
        {assign var=count value=$category.numberofrecordings|default:0|numberformat}
        <div class="numberofrecordings">{#categories__numberofrecordings#|sprintf:$count}</div>
      </a>
    </li>
  {/foreach}
  </ul>
  <div class="clear"></div><br/>
{/if}
