<div class="heading">
  <h1>{$title|escape:html}</h1>
</div>

<ul class="featuredlist">
  <li{if $type == 'featured'} class="active"{/if}><a href="{$language}/recordings/featured">{#recordings__featured_featured#}</a></li>
  <li{if $type == 'newest'} class="active"{/if}><a href="{$language}/recordings/featured/newest">{#recordings__featured_newest#}</a></li>
  <li{if $type == 'mostviewed'} class="active"{/if}><a href="{$language}/recordings/featured/mostviewed">{#recordings__featured_mostviewed#}</a></li>
  <li{if $type == 'highestrated'} class="active"{/if}><a href="{$language}/recordings/featured/highestrated">{#recordings__featured_highestrated#}</a></li>
</ul>
{capture assign=url}{$language}/recordings/featured/_TYPE_?order=%s&start={$smarty.get.start|escape:uri}&perpage={$smarty.get.perpage|escape:uri}{/capture}

{if $type == 'mostviewed' or $type == 'highestrated'}
  <div class="sort">
    {if $type == 'mostviewed'}
      <div class="item">
        <a class="title" href="{$url|replace:_TYPE_:mostviewed|activesortlink:views:$order}">{#categories__views#|activesortarrow:views:$order}</a>
        <ul>
          <li><a href="{$url|replace:_TYPE_:mostviewed|replace:'%s':views}">{#categories__views#|sortarrows:null:views:$order}</a></li>
          <li><a href="{$url|replace:_TYPE_:mostviewed|replace:'%s':views_desc}">{#categories__views_desc#|sortarrows:null:views_desc:$order}</a></li>
          <li><a href="{$url|replace:_TYPE_:mostviewed|replace:'%s':viewsthisweek}">{#categories__viewsthisweek#|sortarrows:null:viewsthisweek:$order}</a></li>
          <li><a href="{$url|replace:_TYPE_:mostviewed|replace:'%s':viewsthisweek_desc}">{#categories__viewsthisweek_desc#|sortarrows:null:viewsthisweek_desc:$order}</a></li>
          <li><a href="{$url|replace:_TYPE_:mostviewed|replace:'%s':viewsthismonth}">{#categories__viewsthismonth#|sortarrows:null:viewsthismonth:$order}</a></li>
          <li><a href="{$url|replace:_TYPE_:mostviewed|replace:'%s':viewsthismonth_desc}">{#categories__viewsthismonth_desc#|sortarrows:null:viewsthismonth_desc:$order}</a></li>
        </ul>
      </div>
    {elseif $type == 'highestrated'}
      <div class="item">
        <a class="title" href="{$url|replace:_TYPE_:highestrated|activesortlink:rating:$order}">{#categories__rating#|activesortarrow:rating:$order}</a>
        <ul>
          <li><a href="{$url|replace:_TYPE_:highestrated|replace:'%s':rating}">{#categories__rating#|sortarrows:null:rating:$order}</a></li>
          <li><a href="{$url|replace:_TYPE_:highestrated|replace:'%s':rating_desc}">{#categories__rating_desc#|sortarrows:null:rating_desc:$order}</a></li>
          <li><a href="{$url|replace:_TYPE_:highestrated|replace:'%s':ratingthisweek}">{#categories__ratingthisweek#|sortarrows:null:ratingthisweek:$order}</a></li>
          <li><a href="{$url|replace:_TYPE_:highestrated|replace:'%s':ratingthisweek_desc}">{#categories__ratingthisweek_desc#|sortarrows:null:ratingthisweek_desc:$order}</a></li>
          <li><a href="{$url|replace:_TYPE_:highestrated|replace:'%s':ratingthismonth}">{#categories__ratingthismonth#|sortarrows:null:ratingthismonth:$order}</a></li>
          <li><a href="{$url|replace:_TYPE_:highestrated|replace:'%s':ratingthismonth_desc}">{#categories__ratingthismonth_desc#|sortarrows:null:ratingthismonth_desc:$order}</a></li>
        </ul>
      </div>
    {/if}
  </div>
{/if}

{*}
{if $type == 'featured' and ( $member.isclientadmin or $member.iseditor )}
  {capture assign="listitemhtml"}
    <div class="wrap contributor">
      <img src="__IMGSRC__"/>
      <span class="name">__NAME__</span>
      <div class="clear"></div>
    </div>
  {/capture}
  <div class="clear"></div></br>
  <div id="recordingssearch" class="form" data-listitemhtml="{$listitemhtml|trim|jsonescape:false:true}">
    {$form}
  </div>
{/if}
{/*}