{assign var=prev value=""}
{capture assign=bcrumb}
  {foreach from=$breadcrumb item=item name=breadcrumb}
    {if !$smarty.foreach.breadcrumb.last}{assign var=prev value=$item}{/if}
    <span class="arrow"></span>
    <a href="{$language}/{$module}/details/{$item.id},{$item.name|filenameize}">{$item.name|escape:html}</a>
  {/foreach}
{/capture}

<div id="categorytitle">
  <h1>
    <a href="{$language}/{$module}">{#categories__categories_title#}</a>
    {$bcrumb|trim}
    <a class="back" href="{if $prev}{$language}/{$module}/details/{$prev.id},{$prev.name|filenameize}{else}{$language}/{$module}{/if}">{#sitewide_back#|lower}</a>
  </h1>
  <div class="clear"></div>
</div>
<div class="channelgradient"></div><br/>

{include file=Visitor/Categories/_categorylist.tpl}

{capture assign=url}{$language}/{$module}/details/{$category.id},{$category.name|filenameize}?order=%order%{/capture}
{include file=Visitor/_sort.tpl url=$url}
