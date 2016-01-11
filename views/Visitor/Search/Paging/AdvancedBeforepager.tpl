<div class="heading">
  <h1>{#search__advanced_title#}</h1>
</div>
<div class="form search">
  {$form}
</div>

{if !empty( $items )}
{capture assign=url}{$searchurl|escape:html}&amp;order=%order%{/capture}
{include file=Visitor/_sort.tpl url=$url}
{/if}
