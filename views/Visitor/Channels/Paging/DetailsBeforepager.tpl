<div class="heading categories">
  <h1>{$channeltree[0].title|escape:html}</h1>
  {if $channeltree[0].subtitle}<h2>{$channeltree[0].subtitle|escape:html}</h2>{/if}
</div>
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
        <div id="channelinfo">
          {$item.channeltype}
        </div>
        
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