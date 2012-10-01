<h2>
  <a href="{$language}/channels/details/{$child.id},{$child.title|filenameize}"{if $child.id == $channel.id} class="active"{/if}>{$child.title|escape:html}</a>&nbsp;({$child.numberofrecordings})
</h2>
{if !empty( $child.children )}
<div class="children">
  {foreach from=$child.children item=child}
    {include file="Visitor/Channels/Paging/DetailsChildren.tpl" child=$child}
  {/foreach}
</div>
{/if}
