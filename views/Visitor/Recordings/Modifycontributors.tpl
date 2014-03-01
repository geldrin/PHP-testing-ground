{include file="Visitor/_header.tpl"}

{include file=Visitor/Recordings/ModifyTimeline.tpl}

{capture assign="listitemhtml"}
  <div class="wrap contributor">
    <img src="__IMGSRC__"/>
    <span class="name">__NAME__</span>
    <div class="clear"></div>
  </div>
{/capture}

<div id="contributors"{if empty( $contributors )} style="display: none;"{/if} data-listitemhtml="{$listitemhtml|trim|jsonescape:false:true}">
  <h2>{#recordings__contributors_title#}</h2>
  <ul>
    {include file=Visitor/Recordings/Contributors.tpl recordingid=$recordingid contributors=$contributors}
  </ul>
</div>

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