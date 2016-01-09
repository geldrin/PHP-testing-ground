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
{capture assign=url}{$language}/{$module}/details/{$channel.id},{$channel.title|filenameize}?order=%s{/capture}

<div class="sort">
  <div class="item">
    <a class="title" href="{$url|activesortlink:channels:$order}">{#channels__channels#|activesortarrow:channels:$order}</a>
    <ul>
      <li><a href="{$url|replace:'%s':channels}">{#channels__channels#|sortarrows:null:channels:$order}</a></li>
  </div>
  <div class="item">
    <a class="title" href="{$url|activesortlink:timestamp:$order}">{#categories__timestamp#|activesortarrow:timestamp:$order}</a>
    <ul>
      <li><a href="{$url|replace:'%s':timestamp}">{#categories__timestamp#|sortarrows:null:timestamp:$order}</a></li>
      <li><a href="{$url|replace:'%s':timestamp_desc}">{#categories__timestamp_desc#|sortarrows:null:timestamp_desc:$order}</a></li>
    </ul>
  </div>
  <div class="item">
    <a class="title" href="{$url|activesortlink:views:$order}">{#categories__views#|activesortarrow:views:$order}</a>
    <ul>
      <li><a href="{$url|replace:'%s':views}">{#categories__views#|sortarrows:null:views:$order}</a></li>
      <li><a href="{$url|replace:'%s':views_desc}">{#categories__views_desc#|sortarrows:null:views_desc:$order}</a></li>
      <li><a href="{$url|replace:'%s':viewsthisweek}">{#categories__viewsthisweek#|sortarrows:null:viewsthisweek:$order}</a></li>
      <li><a href="{$url|replace:'%s':viewsthisweek_desc}">{#categories__viewsthisweek_desc#|sortarrows:null:viewsthisweek_desc:$order}</a></li>
      <li><a href="{$url|replace:'%s':viewsthismonth}">{#categories__viewsthismonth#|sortarrows:null:viewsthismonth:$order}</a></li>
      <li><a href="{$url|replace:'%s':viewsthismonth_desc}">{#categories__viewsthismonth_desc#|sortarrows:null:viewsthismonth_desc:$order}</a></li>
    </ul>
  </div>
  <div class="item">
    <a class="title" href="{$url|activesortlink:comments:$order}">{#categories__comments#|activesortarrow:comments:$order}</a>
    <ul>
      <li><a href="{$url|replace:'%s':comments}">{#categories__comments#|sortarrows:null:comments:$order}</a></li>
      <li><a href="{$url|replace:'%s':comments_desc}">{#categories__comments_desc#|sortarrows:null:comments_desc:$order}</a></li>
    </ul>
  </div>
  <div class="item">
    <a class="title" href="{$url|activesortlink:rating:$order}">{#categories__rating#|activesortarrow:rating:$order}</a>
    <ul>
      <li><a href="{$url|replace:'%s':rating}">{#categories__rating#|sortarrows:null:rating:$order}</a></li>
      <li><a href="{$url|replace:'%s':rating_desc}">{#categories__rating_desc#|sortarrows:null:rating_desc:$order}</a></li>
      <li><a href="{$url|replace:'%s':ratingthisweek}">{#categories__ratingthisweek#|sortarrows:null:ratingthisweek:$order}</a></li>
      <li><a href="{$url|replace:'%s':ratingthisweek_desc}">{#categories__ratingthisweek_desc#|sortarrows:null:ratingthisweek_desc:$order}</a></li>
      <li><a href="{$url|replace:'%s':ratingthismonth}">{#categories__ratingthismonth#|sortarrows:null:ratingthismonth:$order}</a></li>
      <li><a href="{$url|replace:'%s':ratingthismonth_desc}">{#categories__ratingthismonth_desc#|sortarrows:null:ratingthismonth_desc:$order}</a></li>
    </ul>
  </div>
</div>

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