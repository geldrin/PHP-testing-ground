{foreach from=$blocks key=block item=items}
{capture assign="ajanlo_"|cat:$block}
  {if $block == "eloadas" and !empty( $items )}
    <a id="channelheader" href="{$language}/live/details/{$items.id},{$items.title|filenameize}">
      <div class="channelimage">
        <img src="{$items|@indexphoto:player}"/>
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
    </a>
  {elseif $block == "kiemelt" and !empty( $items )}
    <div id="indexcontainer">
      <ul>
        {foreach from=$items item=item name=recordings}
          {include file="Visitor/minirecordinglistitem.tpl" isfirst=$smarty.foreach.recordings.first}
        {/foreach}
      </ul>
      <div class="clear"></div>
    </div>
    <div class="clear"></div>
  {elseif $block == "ujranezes" and !empty( $items )}
    <div class="accordion active persist" id="accordion_{$block}">
      <h2><a href="#">{$labels[$block]|escape:html}</a></h2>
      <ul>
        {foreach from=$items item=item}
          {include file="Visitor/minirecordinglistitem.tpl"}
        {/foreach}
      </ul>
      <div class="clear"></div>
    </div>
    <div class="clear"></div>
  {elseif !empty( $items )}
    <div class="accordion active persist" id="accordion_{$block}">
      <h2><a href="#">{$labels[$block]|escape:html}</a></h2>
      <ul>
        {foreach from=$items item=item}
          {include file="Visitor/minirecordinglistitem.tpl"}
        {/foreach}
      </ul>
      <div class="clear"></div>
      <a href="{$language}/recordings/featured/{$blocksToTypes[$block]}" class="more">{#sitewide_go#}</a>
    </div>
    <div class="clear"></div>
  {/if}
{/capture}
{/foreach}
