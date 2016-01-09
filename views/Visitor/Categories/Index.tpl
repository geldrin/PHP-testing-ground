{include file="Visitor/_header.tpl" title=#categories__categories_title#}
<div id="categoryheading">
  <h1>{#categories__categories_title#}</h1>
</div>
<div class="channelgradient"></div>
<br/><br/>

<ul class="categorylist">
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
      <img src="{$imgbase}categories/114/{$category.iconfilename}" />
      <h3>{$category.namehyphenated|default:$category.name|escape:html}</h3>
      {assign var=count value=$category.numberofrecordings|default:0|numberformat}
      <div class="numberofrecordings">{#categories__numberofrecordings#|sprintf:$count}</div>
    </a>
  </li>
{/foreach}
</ul>
{include file="Visitor/_footer.tpl"}