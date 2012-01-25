{include file="Admin/_header.tpl"}

<div class="p" id="largeicons">
  <div class="clear"></div>
  {foreach from=$menu item=item}
    <a href="{$item.link|escape:html}"><img src="{$item.icon|escape:html}"/>{$item.text|escape:html}</a>
  {foreachelse}
    No menu items
  {/foreach}
</div>

{include file="Admin/_footer.tpl"}