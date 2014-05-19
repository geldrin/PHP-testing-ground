{include file="Visitor/_header.tpl" title=#channels__orderrecordings_title#}

<div class="heading">
  <h1>{#channels__orderrecordings_title#}</h1>
  <h2><a href="{$language}/channels/details/{$channel.id},{$channel|@title:name|filenameize}" class="active">{$channel|@title:name|escape:html}</a></h2>
</div>

<div id="orderrecordings" data-channelid="{$channel.id}">

  {if !empty( $items )}
    <a class="submitbutton right" href="{$forward|escape:html}" class="orderdone">{#formdone#}</a>
    <div class="clear"></div><br/>
    <ul id="orderlist">
      {foreach from=$items item=item}
        <li id="order_{$item.channelrecordingid}">
          
          <div class="orderarrows">
            <span class="recordingmoveup">
              <a href="#" title="{#channels__recordings_moveup#}"></a>
            </span>
            <span class="recordingmovedown">
              <a href="#" title="{#channels__recordings_movedown#}"></a>
            </span>
          </div>
          
          <div class="recording">
            
            <h1>{$item.title|escape:html}</h1>
            <p class="recordingpresenters">{include file=Visitor/presenters.tpl presenters=$item.presenters skippresenterbolding=true presenterdelimiter=", "}</p>
            
          </div>
          
        </li>
      {/foreach}
    </ul>
  {else}
    {#recordings__foreachelse#}
  {/if}
  
  <div class="clear"></div><br/>
  <a class="submitbutton right" href="{$forward|escape:html}" class="orderdone">{if empty( $items )}{#sitewide_back#}{else}{#formdone#}{/if}</a>
</div>

{if !empty( $help ) and strpos( $helpclass, 'hidden' ) === false}
<div class="help {$helpclass|default:"halfbox right"}">
  <h1 class="title">{#help#}</h1>
  {$help.body}
</div>
{/if}

{include file="Visitor/_footer.tpl"}