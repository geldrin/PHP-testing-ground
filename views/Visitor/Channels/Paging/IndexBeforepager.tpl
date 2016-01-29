<div id="pagetitle">
  <h1>{#channels__index_title#}</h1>
</div>
<div class="channelgradient"></div>
<br/>

{capture assign=url}{$language}/{$module}?order=%s{/capture}
{include file=Visitor/_sort.tpl url=$url}
