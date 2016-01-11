<div id="channelheader">
  <div class="channelimage">
    <img src="{$channeltree[0]|@indexphoto}"/>
  </div>
  <div class="channelinfowrap">
    <h1>{$channeltree[0].title|escape:html|mb_wordwrap:50}</h1>
    {if $channeltree[0].subtitle}
      <h2>{$channeltree[0].subtitle|escape:html|mb_wordwrap:50}</h2>
    {/if}
    <div class="channeltype">{$channeltree[0].channeltype|escape:html}</div>
    {if $channeltree[0].starttimestamp or $channeltree[0].location}
    <div class="channelinfo">
      {if $channeltree[0].starttimestamp}
        {"%Y. %B %e"|shortdate:$channeltree[0].starttimestamp:$channeltree[0].endtimestamp}
      {/if}
      {if $channeltree[0].location}
        {if $channeltree[0].starttimestamp},{/if}
        {$channeltree[0].location|escape:html}
      {/if}
    </div>
    {/if}
  </div>
</div>

{if $channeltree[0].description}
  <div id="channeldescription">
    <p>{$channeltree[0].description|escape:html|nl2br}</p>
  </div>
{/if}

<div class="heading categories title">
  {if !$channeltree[0].subtitle and $member.id and ($member.isuploader or $member.ismoderateduploader) and $canaddrecording}
  <div class="actions">
    <a class="submitbutton" href="{$language}/recordings/upload?channelid={$channel.id}">{#channels__addrecording#}</a>
    <a class="subscribe submitbutton" href="{$language}/users/togglesubscription?channelid={$channel.id}&amp;state={if $subscribed}del{else}add{/if}&amp;forward={$FULL_URI|escape:url}">{if $subscribed}{#users__unsubscribe#}{else}{#users__subscribe#}{/if}</a>
  </div>
  {/if}
  {if $member.id and ($member.isuploader or $member.ismoderateduploader) and $canaddrecording}
  <div class="actions">
    <a href="{$language}/recordings/upload?channelid={$channel.id}">{#channels__addrecording#}</a> |
    <a href="{$language}/channels/orderrecordings/{$channel.id}?forward={$FULL_URI|escape:url}">{#channels__orderrecordings#}</a>
  </div>
  {/if}
</div>
<div class="channelgradient"></div>
{capture assign=url}{$language}/{$module}/details/{$channel.id},{$channel.title|filenameize}?order=%order%{/capture}
{include file=Visitor/_sort.tpl url=$url}
<br/>

<div class="events">
  <div class="treeview">
    
    {if !empty( $channeltree[0].children ) or $channel|@userHasAccess}
      
      {foreach from=$channeltree item=item}
        <div class="channeltree">
          <div class="children">
            {foreach from=$item.children item=child}
              {include file="Visitor/Channels/Paging/DetailsChildren.tpl" child=$child}
            {/foreach}
          </div>
          <div class="clear"></div><br/>
        </div>
      {/foreach}
      
    {/if}
    
  </div>
  
  <div class="channelrecordings">