{if $smarty.request.chromeless or $nolayout}
  {include file="Visitor/_header_nolayout.tpl" bodyclass=$bodyclass|default:liveiframe}
  {assign var=linksinnewwindow value=' target="_blank"'}
{else}
  {include file="Visitor/_header.tpl"}
{/if}

{if $insertbefore}
  {include file=$insertbefore}
{elseif $title}
<div id="pagetitle">
  <h1>{$title|escape:html}</h1>
</div>
<div class="channelgradient"></div>
<br/>
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

{if $smarty.request.chromeless or $nolayout}
  {include file="Visitor/_footer_nolayout.tpl"}
{else}
  {include file="Visitor/_footer.tpl"}
{/if}