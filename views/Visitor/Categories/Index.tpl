{include file="Visitor/_header.tpl" title=#categories__categories_title#}
<div class="heading categoryheading">
  <h1>{#categories__categories_title#}</h1>
  <h2>{#categories__categories_subtitle#}</h2>
</div>

<ul class="categorylist">
{foreach from=$categories item=category name=category}
  
  <li{if $smarty.foreach.category.last} class="last"{/if}>
    <div class="categoryname">
      <h2><a href="{$language}/categories/details/{$category.id},{$category.name|filenameize}" title="{$category.name|escape:html}">{$category.name|escape:html}</a></h2>
      <a href="{$language}/categories/details/{$category.id},{$category.name|filenameize}" class="categorypic"><img src="{$organization|@uri:static}categories/1.png" width="140" height="100"/></a>
    </div>
    <ul class="subcategorylist">
      {foreach from=$category.children item=subcategory}
      
        <li>
          <h2><a href="{$language}/categories/details/{$subcategory.id},{$subcategory.name|filenameize}" title="{$subcategory.name|escape:html}"><span class="subcatname">{$subcategory.name|escape:html}</span></a></h2>
        </li>
      
      {/foreach}
    </ul>
  </li>
{/foreach}
</ul>
{include file="Visitor/_footer.tpl"}