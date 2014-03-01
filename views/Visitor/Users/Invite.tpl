{if $smarty.request.chromeless or $nolayout}
  {include file="Visitor/_header_nolayout.tpl" bodyclass=$bodyclass|default:liveiframe}
  {assign var=linksinnewwindow value=' target="_blank"'}
{else}
  {include file="Visitor/_header.tpl"}
{/if}

{if $insertbefore}
  {include file=$insertbefore}
{/if}

{capture assign="listitemhtml"}
  <div class="wrap contributor">
    <img src="__IMGSRC__"/>
    <span class="name">__NAME__</span>
    <div class="clear"></div>
  </div>
{/capture}

<div id="usersinvitewrap" class="form {$formclass|default:"halfbox left"}" data-listitemhtml="{$listitemhtml|trim|jsonescape:false:true}">
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