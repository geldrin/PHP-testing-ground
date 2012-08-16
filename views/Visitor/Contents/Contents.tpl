{include file="Visitor/_header.tpl" module="contents"}
<div class="title">
  <h1>{$content.title}{if $missingcontent} ({$missingcontent}){/if}</h1>
</div>
<br/>

{$content.body}

{include file="Visitor/_footer.tpl"}