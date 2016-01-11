<div class="heading">
  <h1>{$title|escape:html}</h1>
</div>

<ul class="featuredlist">
  <li{if $type == 'featured'} class="active"{/if}><a href="{$language}/recordings/featured">{#recordings__featured_featured#}</a></li>
  <li{if $type == 'newest'} class="active"{/if}><a href="{$language}/recordings/featured/newest">{#recordings__featured_newest#}</a></li>
  <li{if $type == 'mostviewed'} class="active"{/if}><a href="{$language}/recordings/featured/mostviewed">{#recordings__featured_mostviewed#}</a></li>
  <li{if $type == 'highestrated'} class="active"{/if}><a href="{$language}/recordings/featured/highestrated">{#recordings__featured_highestrated#}</a></li>
</ul>
{capture assign=url}{$language}/recordings/featured/_TYPE_?order=%order%&amp;start={$smarty.get.start|escape:uri}&amp;perpage={$smarty.get.perpage|escape:uri}{/capture}

{if $type == 'mostviewed' or $type == 'highestrated'}
  {assign var=url value=$url|replace:_TYPE_:$type}
  {include file=Visitor/_sort.tpl url=$url}
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