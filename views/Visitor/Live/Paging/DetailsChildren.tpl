<h2>
  <a href="{$language}/live/details/{$child.id},{$child.name|filenameize}"{if $child.id == $channel.id} class="active"{/if}>{$child.name|escape:html}</a>
</h2>
{if $child.subtitle}
  <h3>{$child.subtitle|escape:html}</h3>
{/if}

{if !empty( $child.children )}
<div class="children">
  {foreach from=$child.children item=child}
    {include file="Visitor/Live/Paging/DetailsChildren.tpl" child=$child}
  {/foreach}
</div>
{/if}
