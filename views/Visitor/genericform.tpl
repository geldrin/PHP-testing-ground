{include file="Visitor/_header.tpl"}

<div class="form {$formclass|default:"halfbox left"}">
{$form}
</div>

{if !empty( $help ) and strpos( $helpclass, 'hidden' ) === false}
<div class="help {$helpclass|default:"halfbox right"}">
  <h1 class="title">{l key=help}</h1>
  {$help.body}
</div>
{/if}
{include file="Visitor/_footer.tpl"}