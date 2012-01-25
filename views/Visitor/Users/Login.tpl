{include file="Visitor/_header.tpl"}
{box class="box_left"}
  {$form}
{/box}

{if !empty( $help )}
<div class="help right">
  <h1 class="title">{#help#}</h1>
  {$help.body}
</div>
{/if}

{include file="Visitor/_footer.tpl"}