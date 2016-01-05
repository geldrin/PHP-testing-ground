{include file="Visitor/_header.tpl" module="index"}

{if !empty( $recordings )}
<div id="indexcontainer">
  <ul>
    {foreach from=$recordings item=item name=recordings}
      {include file="Visitor/minirecordinglistitem.tpl" isfirst=$smarty.foreach.recordings.first}
    {/foreach}
  </ul>
</div>
<div class="clear"></div>
{/if}

{if !empty( $mostviewed )}
<div class="accordion active">
  <h2><a href="#">{#index__mostviewed#}</a></h2>
  <ul>
    {foreach from=$mostviewed item=item}
      {include file="Visitor/minirecordinglistitem.tpl"}
    {/foreach}
  </ul>
</div>
<div class="clear"></div>
{/if}

{if !empty( $newest )}
<div class="accordion">
  <h2><a href="#">{#index__newest#}</a></h2>
  <ul>
    {foreach from=$newest item=item}
      {include file="Visitor/minirecordinglistitem.tpl"}
    {/foreach}
  </ul>
</div>
<div class="clear"></div>
{/if}

{if !empty( $featured )}
<div class="accordion">
  <h2><a href="#">{#index__featured#}</a></h2>
  <ul>
    {foreach from=$featured item=item}
      {include file="Visitor/minirecordinglistitem.tpl"}
    {/foreach}
  </ul>
</div>
<div class="clear"></div>
{/if}

{include file="Visitor/_footer.tpl"}
