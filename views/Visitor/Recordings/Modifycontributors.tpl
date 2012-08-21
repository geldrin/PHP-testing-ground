{include file="Visitor/_header.tpl"}

{include file=Visitor/Recordings/ModifyTimeline.tpl}

<div class="form leftdoublebox">
{$form}
</div>

{if !empty( $help ) and strpos( $helpclass, 'hidden' ) === false}
<div class="help small right">
  <h1 class="title">{#help#}</h1>
  {$help.body}
</div>
{/if}
{include file="Visitor/_footer.tpl"}