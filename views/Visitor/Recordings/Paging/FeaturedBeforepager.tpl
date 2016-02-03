<div id="pagetitle">
  <h1>{$title|escape:html}</h1>
</div>
<div class="channelgradient"></div>
<br/>

{capture assign=url}{$language}/recordings/featured/_TYPE_?order=%order%&amp;start={$smarty.get.start|escape:uri}&amp;perpage={$smarty.get.perpage|escape:uri}{/capture}

{if $type == 'mostviewed' or $type == 'highestrated'}
  {assign var=url value=$url|replace:_TYPE_:$type}
  {include file=Visitor/_sort.tpl url=$url}
{/if}
