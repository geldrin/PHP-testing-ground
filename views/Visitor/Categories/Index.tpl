{include file="Visitor/_header.tpl" title=#categories__categories_title#}
<div class="heading categoryheading">
  <h1>{#categories__categories_title#}</h1>
  <h2>{#categories__categories_subtitle#}</h2>
</div>

<ul class="categorylist">
{foreach from=$categories item=category name=category}
  
  <li{if $smarty.foreach.category.last} class="last"{/if}>
    <div class="categoryname">
      <a href="{$language}/categories/details/{$category.id},{$category.name|filenameize}" title="{$category.name|escape:html}"><h2>{$category.name|escape:html}</h2></a>
      <div class="numberofrecordings">({$category.numberofrecordings|default:0})</div>
      
      <a href="{$language}/categories/details/{$category.id},{$category.name|filenameize}" class="categorypic"><img src="{$organization|@uri:static}categories/{$category.id}.png" width="140" height="100"/></a>
    </div>
    <ul class="subcategorylist">
      {foreach from=$category.children item=subcategory}
      {if $subcategory.numberofrecordings}
        <li>
          <a href="{$language}/categories/details/{$subcategory.id},{$subcategory.name|filenameize}" title="{$subcategory.name|escape:html}"><h2><span class="subcatname">{$subcategory.name|escape:html}</span> <span>({$subcategory.numberofrecordings})</span></h2></a>
        </li>
      {/if}
      {/foreach}
    </ul>
  </li>
{/foreach}
</ul>
{include file="Visitor/_footer.tpl"}