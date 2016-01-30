{include file="Visitor/_header.tpl" module="index"}

{foreach from=$blocks key=block item=items}
  {if $block == "eloadas" and !empty( $items )}
    <div id="channelheader">
      <div class="channelimage">
        <img src="{$STATIC_URI|escape:html}files/{$items.channelindexphotofilename|escape:html}"/>
      </div>
      <div class="channelinfowrap">
        <h1>{$items.title|escape:html|mb_wordwrap:50}</h1>
        {if $items.subtitle}
          <h2>{$items.subtitle|escape:html|mb_wordwrap:50}</h2>
        {/if}
        <div class="channeltype">{$items.channeltype|escape:html}</div>
        {if $items.starttimestamp or $items.location}
        <div class="channelinfo">
          {if $items.starttimestamp}
            {"%Y. %B %e"|shortdate:$items.starttimestamp:$items.endtimestamp}
          {/if}
          {if $items.location}
            {if $items.starttimestamp},{/if}
            {$items.location|escape:html}
          {/if}
        </div>
        {/if}
      </div>
    </div>
  {/if}

  {if $block == "kiemelt" and !empty( $items )}
    <div id="indexcontainer">
      <ul>
        {foreach from=$items item=item name=recordings}
          {include file="Visitor/minirecordinglistitem.tpl" isfirst=$smarty.foreach.recordings.first}
        {/foreach}
      </ul>
    </div>
    <div class="clear"></div>
  {/if}

  {if $block == "legujabb" and !empty( $items )}
    <div class="accordion active">
      <h2><a href="#">{#index__newest#}</a></h2>
      <ul>
        {foreach from=$items item=item}
          {include file="Visitor/minirecordinglistitem.tpl"}
        {/foreach}
      </ul>
    </div>
    <div class="clear"></div>
  {/if}

  {if $block == "legnezettebb" and !empty( $items )}
    <div class="accordion active">
      <h2><a href="#">{#index__mostviewed#}</a></h2>
      <ul>
        {foreach from=$items item=item}
          {include file="Visitor/minirecordinglistitem.tpl"}
        {/foreach}
      </ul>
    </div>
    <div class="clear"></div>
  {/if}

{/foreach}

{include file="Visitor/_footer.tpl"}
