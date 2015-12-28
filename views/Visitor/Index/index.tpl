{include file="Visitor/_header.tpl" module="index"}

{if !empty( $recordings )}
  <div id="indexcontainer">
    <div class="indexleft">
      <ul>
        {assign var=item value=$recordings[0]}
        {include file="Visitor/Index/recordinglistitem.tpl"}
      </ul>
    </div>
    
    <div class="indexright">
      <ul>
      {*} skip the first recording as we have already printed it above {/*}
      {section name=rightbox start=1 loop=$recordings}
        {assign var=item value=$recordings[rightbox]}
        {include file="Visitor/Index/recordinglistitem.tpl"}
      {/section}
      </ul>
    </div>
    <div class="clear"></div>
  </div>
{/if}

<div class="clear"></div>
<div class="accordion active">
  <h2><a href="#">{#index__mostviewed#}</a></h2>
  <ul>
    {foreach from=$mostviewed item=item}
      {include file="Visitor/Index/recordinglistitem.tpl"}
    {/foreach}
  </ul>
</div>
<div class="clear"></div>

<div class="accordion">
  <h2><a href="#">{#index__newest#}</a></h2>
  <ul>
    {foreach from=$newest item=item}
      {include file="Visitor/Index/recordinglistitem.tpl"}
    {/foreach}
  </ul>
</div>
<div class="clear"></div>

<div class="accordion">
  <h2><a href="#">{#index__featured#}</a></h2>
  <ul>
    {foreach from=$featured item=item}
      {include file="Visitor/Index/recordinglistitem.tpl"}
    {/foreach}
  </ul>
</div>
<div class="clear"></div>
{include file="Visitor/_footer.tpl"}
