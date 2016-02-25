
<div class="form search">
  {$form}
</div>

{if !empty( $items )}
{capture assign=url}{$searchurl|escape:html}&amp;order=%order%{/capture}
{include file=Visitor/_sort.tpl url=$url}
{/if}
