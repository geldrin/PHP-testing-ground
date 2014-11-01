{include file="Visitor/_header.tpl" module="organizations"}

<div class="title">
  <h1>{$news.title|escape:html|mb_wordwrap:25}</h1>
</div>

{$news.body}

{include file="Visitor/_footer.tpl"}