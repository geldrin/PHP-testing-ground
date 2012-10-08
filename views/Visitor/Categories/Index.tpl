{include file="Visitor/_header.tpl" title=#categories__categories_title#}
<div class="heading categoryheading">
  <h1>{#categories__categories_title#}</h1>
  <h2>{#categories__categories_subtitle#}</h2>
</div>

<ul class="categorylist">
{foreach from=$categories item=category name=category}
  
  <li{if $smarty.foreach.category.last} class="last"{/if}>
    <div class="categoryname">
      <h2><a href="{$language}/categories/details/{$category.id},{$category.name|filenameize}" title="{$category.name|escape:html}">{$category.namehyphenated|default:$category.name|escape:html}</a></h2>
      <div class="numberofrecordings">({$category.numberofrecordings|default:0})</div>
      <a href="{$language}/categories/details/{$category.id},{$category.name|filenameize}" class="categorypic"><img src="{$STATIC_URI}images/categories/114/{$category.iconfilename}" /></a>
    </div>
    <ul class="subcategorylist">
      {foreach from=$category.children item=subcategory}
      
        <li>
          <h3><a href="{$language}/categories/details/{$subcategory.id},{$subcategory.name|filenameize}" title="{$subcategory.name|escape:html}"><span class="subcatname">{$subcategory.namehyphenated|default:$subcategory.name|escape:html}</span> <span>({$subcategory.numberofrecordings})</span></a></h3>
        </li>
      
      {/foreach}
    </ul>
  </li>
{/foreach}
</ul>
{include file="Visitor/_footer.tpl"}