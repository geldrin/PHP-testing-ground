
<div class="form search">
  {$form}
</div>

<div class="channelgradient"></div>
<br/>

{if !empty( $items )}
{capture assign=url}{$searchurl|escape:html}&amp;order=%order%{/capture}
{include file=Visitor/_sort.tpl url=$url}
{/if}
