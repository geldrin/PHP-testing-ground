{include file="Visitor/_header.tpl"}
{box class=$boxclass|default:"box_left"}
{$form}
{/box}
{if !empty( $help ) and strpos( $helpclass, 'hidden' ) === false}
<div class="{$helpclass|default:"help right"}">
  <h1 class="title">{#help#}</h1>
  {$help.body}
</div>
{/if}
{include file="Visitor/_footer.tpl"}