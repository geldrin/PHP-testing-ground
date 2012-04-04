{include file="Visitor/_header.tpl" module="organizations"}

<div class="title">
  <h1>{if $language == 'hu'}{$news.titlehungarian|escape:html}{else}{$news.titleenglish|escape:html}{/if}</h1>
</div>
<br/>

{if $language == 'hu'}
  {$news.bodyhungarian}
{else}
  {$news.bodyenglish}
{/if}

{include file="Visitor/_footer.tpl"}