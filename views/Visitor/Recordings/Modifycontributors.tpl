{include file="Visitor/_header.tpl"}

{include file=Visitor/Recordings/ModifyTimeline.tpl}


<div id="contributors"{if empty( $contributors )} style="display: none;"{/if}>
  <h2>{#recordings__contributors_title#}</h2>
  <ul>
    {include file=Visitor/Recordings/Contributors.tpl recordingid=$recordingid contributors=$contributors}
  </ul>
</div>

<div class="form leftdoublebox">
{$form}
</div>

<div id="autocomplete-listitem" style="display: none;">
  <div class="wrap">
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