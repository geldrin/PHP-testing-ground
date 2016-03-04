{include file="Visitor/_header.tpl" module="contents"}
<div id="pagetitle">
  <h1>{$content.title}{if $missingcontent} ({$missingcontent}){/if}</h1>
</div>
<div class="channelgradient"></div>
<br/>

{$content.body}

{include file="Visitor/_footer.tpl"}