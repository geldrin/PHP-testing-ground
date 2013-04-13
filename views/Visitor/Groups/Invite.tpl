{include file="Visitor/_header.tpl"}

<div class="form halfbox left">
{$form}
</div>

<div id="autocomplete-listitem" style="display: none;">
  <div class="wrap groupsinvite">
    <img src="__IMGSRC__"/>
    <span class="name">__NAME__</span>
    <div class="clear"></div>
  </div>
</div>

{if !empty( $help ) and strpos( $helpclass, 'hidden' ) === false}
<div class="help small right">
  <h1 class="title">{#help#}</h1>
  {$help.body}
</div>
{/if}
{include file="Visitor/_footer.tpl"}