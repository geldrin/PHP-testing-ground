<div id="channelheader">
  <div class="channelimage">
    <img src="{$channelroot|@indexphoto:player}"/>
  </div>
  <div class="channelinfowrap">
    <h1>{$channelroot.title|escape:html|mb_wordwrap:50}</h1>
    {if $channelroot.subtitle}
      <h2>{$channelroot.subtitle|escape:html|mb_wordwrap:50}</h2>
    {/if}
    <div class="channeltype">{$channelroot.channeltype|escape:html}</div>
    {if $channelroot.starttimestamp or $channelroot.location}
    <div class="channelinfo">
      {if $channelroot.starttimestamp}
        {"%Y. %B %e"|shortdate:$channelroot.starttimestamp:$channelroot.endtimestamp}
      {/if}
      {if $channelroot.location}
        {if $channelroot.starttimestamp},{/if}
        {$channelroot.location|escape:html}
      {/if}
    </div>
    {/if}
  </div>
</div>

{if $channelroot.description or ( $member.id and ($member.isuploader or $member.ismoderateduploader) and $canaddrecording )}
  <div id="channeldescription">
    {if $member.id and ($member.isuploader or $member.ismoderateduploader) and $canaddrecording}
    <div class="actions">
      <a class="submitbutton" href="{$language}/recordings/upload?channelid={$channel.id}">{#channels__addrecording#}</a>
      <a class="submitbutton" href="{$language}/channels/orderrecordings/{$channel.id}?forward={$FULL_URI|escape:url}">{#channels__orderrecordings#}</a>
      <a class="subscribe submitbutton" href="{$language}/users/togglesubscription?channelid={$channel.id}&amp;state={if $subscribed}del{else}add{/if}&amp;forward={$FULL_URI|escape:url}">{if $subscribed}{#users__unsubscribe#}{else}{#users__subscribe#}{/if}</a>
    </div>
    <div class="clear"></div>
    {/if}

    <p>{$channelroot.description|escape:html|nl2br}</p>
  </div>
{/if}

<div class="heading categories title"></div>
<div class="channelgradient"></div>

<div class="events">
  {if !empty( $channelchildren )}
    <ul id="channellist">
      {if $channel.id != $channelroot.id}
        <li class="back">
          <a class="back" href="{$language}/channels/details/{$channelparent.id},{$channelparent.title|filenameize}">{#sitewide_back#|lower}</a>
          <div class="clear"></div>
        </li>
      {/if}

      {foreach from=$channelchildren item=item}
        <li>
          <a href="{$language}/channels/details/{$item.id},{$item.title|filenameize}">{$item.title|escape:html|mb_wordwrap:25}</a>
          {assign var=count value=$item.numberofrecordings|default:0|numberformat}
          <span class="numberofrecordings">{#categories__numberofrecordings#|sprintf:$count}</span>
        </li>
      {/foreach}
    </ul>
  {/if}
  <div class="channelrecordings{if !empty( $channelchildren )} halfwidth{/if}">
    {if !empty( $channelroot.children )}
      {if empty( $channelchildren )}
        <a class="back" href="{$language}/channels/details/{$channelparent.id},{$channelparent.title|filenameize}">{#sitewide_back#|lower}</a>
      {/if}
      <div class="channeltitle">{$channel.title|escape:html}</div>
      {*}
        vannak a csatorna faban felvetelek, nem egy szingli csatorna,
        viszont a csatornanak nincsenek gyerekei
      {/*}
      
    {/if}

    {capture assign=url}{$language}/{$module}/details/{$channel.id},{$channel.title|filenameize}?order=%order%{/capture}
    {include file=Visitor/_sort.tpl url=$url}