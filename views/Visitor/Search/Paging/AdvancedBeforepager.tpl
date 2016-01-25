<div id="categoryheading">
  <h1>{#search__advanced_title#}</h1>
</div>
<div class="channelgradient"></div>
<br/>

<div class="form search">
  {$form}
</div>

{if !empty( $items )}
{capture assign=url}{$searchurl|escape:html}&amp;order=%order%{/capture}
{include file=Visitor/_sort.tpl url=$url}
{/if}
