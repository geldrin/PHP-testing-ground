<div class="heading categories">
  <h1>{#channels__index_title#}</h1>
</div>

{capture assign=url}{$language}/{$module}?order=%s{/capture}
{include file=Visitor/_sort.tpl url=$url}
