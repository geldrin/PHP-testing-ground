{include file="Admin/_header.tpl"}

<div class="p" id="largeicons">
  <div class="clear"></div>
  {foreach from=$menu item=item}
    <a href="{$item.link|escape:html}"><img src="{$item.icon|escape:html}"/>{$item.text|escape:html}</a>
  {foreachelse}
    No menu items
  {/foreach}
</div>

<div class="clear"></div><br/>

<div class="p" id="maintenance">
  <h2>{#admin__maintenance#}</h2>

  {foreach from=$maintenance key=type item=enabled}
    {$type}: {if $enabled}{#enabled#}{else}{#disabled#}{/if} - <a href="index/togglemaintenance?type={$type}&amp;status={if $enabled}off{else}on{/if}">{if $enabled}{#disable#}{else}{#enable#}{/if}</a><br/>
  {/foreach}
</div>

{include file="Admin/_footer.tpl"}