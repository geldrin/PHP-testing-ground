{foreach from=$blocks key=block item=items}
{capture assign="ajanlo_"|cat:$block}
  {if $block == "eloadas" and !empty( $items )}
    <div class="accordion active persist" id="accordion_{$block}">
      <h2><a href="#">{$labels[$block]|escape:html}</a></h2>
      <ul>
        <li class="listitem first">
          <a href="{$language}/live/details/{$items.id},{$items.title|filenameize}">
            <div class="recordingpic">
              <img src="{$items|@indexphoto}"/>
            </div>
            <div class="recordingcontent">
              <div class="presenter">{$items.channeltype|escape:html}</div>
              {if $items.starttimestamp}
                <div class="timestamp" title="{"%Y. %B %e"|shortdate:$items.starttimestamp:$items.endtimestamp}">
                  {"%Y. %B %e"|shortdate:$items.starttimestamp:$items.endtimestamp}
                </div>
              {/if}
              <div class="title" title="{$items.title|escape:html}">{$items.title|escape:html|mb_wordwrap:25}</div>
            </div>
          </a>
        </li>
      </ul>
      <div class="clear"></div>
    </div>
    <div class="clear"></div>
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
