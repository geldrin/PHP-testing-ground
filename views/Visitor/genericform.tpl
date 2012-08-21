{include file="Visitor/_header.tpl"}

{if $insertbefore}
  {include file=$insertbefore}
{/if}

<div class="form {$formclass|default:"halfbox left"}">
{$form}
</div>

{if !empty( $help ) and strpos( $helpclass, 'hidden' ) === false}
<div class="help {$helpclass|default:"halfbox right"}">
  <h1 class="title">{#help#}</h1>
  {$help.body}
</div>
{/if}
{include file="Visitor/_footer.tpl"}