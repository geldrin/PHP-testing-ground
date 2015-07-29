{include file="Visitor/_header.tpl" needselect2=true}

{capture assign="recordingshtml"}
  <div class="wrap contributor">
    <img src="__IMGSRC__"/>
    <span class="title">__TITLE__</span>
    <span class="subtitle">__SUBTITLE__</span>
    <div class="clear"></div>
  </div>
{/capture}

{capture assign="livehtml"}
  <div class="wrap contributor">
    <img src="__IMGSRC__"/>
    <span class="name">__NAME__</span>
    <div class="clear"></div>
  </div>
{/capture}

{capture assign="contributorhtml"}
  <div class="wrap contributor">
    <img src="__IMGSRC__"/>
    <span class="name">__NAME__</span>
    <div class="clear"></div>
  </div>
{/capture}

{capture assign="usershtml"}
  <div class="wrap contributor">
    <img src="__IMGSRC__"/>
    <span class="name">__NAME__</span>
    <div class="clear"></div>
  </div>
{/capture}

<div id="statisticsform" class="form leftdoublebox" data-recordingstpl="{$recordingshtml|trim|jsonescape:false:true}" data-livetpl="{$livehtml|trim|jsonescape:false:true}" data-contributortpl="{$contributorhtml|trim|jsonescape:false:true}" data-userstpl="{$usershtml|trim|jsonescape:false:true}">
{$form}
</div>

{if !empty( $help ) and strpos( $helpclass, 'hidden' ) === false}
<div class="help small right">
  <h1 class="title">{#help#}</h1>
  {$help.body}
</div>
{/if}
{include file="Visitor/_footer.tpl"}