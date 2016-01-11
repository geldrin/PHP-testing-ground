<div class="heading categories">
  {*}<a href="{$language}/categories">{#categories__categories_title#}</a> &raquo; {$currentcategory|@orteliusbreadcrumb}<br />{/*}
  <h1>{$category.name|escape:html|mb_wordwrap:25}</h1>
  {*}<a href="{$language}/search/advanced?ortelius={$category.id}&amp;showform=1">{#categories__searchincategory#|sprintf:$category.name}</a><br />{/*}
</div>
{capture assign=url}{$language}/{$module}/details/{$category.id},{$category.name|filenameize}?order=%order%{/capture}
{include file=Visitor/_sort.tpl url=$url}
