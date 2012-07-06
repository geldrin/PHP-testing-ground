<h3>
  <a href="{$language}/live/details/{$child.id},{$child.title|filenameize}"{if $child.id == $channel.id} class="active"{/if}>{$child.title|escape:html}</a>
</h3>
{if $child.subtitle}
  <div class="subtitle">{$child.subtitle|escape:html}</div>
{/if}

{if !empty( $child.children )}
<div class="children">
  {foreach from=$child.children item=child}
    {include file="Visitor/Live/Paging/DetailsChildren.tpl" child=$child}
  {/foreach}
</div>
{/if}
