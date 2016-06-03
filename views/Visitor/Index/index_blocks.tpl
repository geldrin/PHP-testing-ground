{foreach from=$blocks key=block item=items}
{capture assign="ajanlo_"|cat:$block}
  {if $block == "eloadas" and !empty( $items )}
    <div class="featuredcontainer live">
      <h2>{$labels[$block]|escape:html}</h2>
      <ul>
        {foreach from=$items item=item name=live}
        <li class="listitem{if $smarty.foreach.live.first} first{/if}">
          <a href="{$language}/live/details/{$item.id},{$item.title|filenameize}">
            <div class="recordingpic">
              <img src="{$item|@indexphoto:player}"/>
            </div>
            <div class="recordingcontent">
              <div class="wrap">
                {if $smarty.foreach.live.first}
                <div class="presenter" title="{$item.channeltype|escape:html}">{$item.channeltype|escape:html}</div>
                {/if}
                {if $item.starttimestamp and $smarty.foreach.live.first}
                  <div class="timestamp" title="{"%Y. %B %e"|shortdate:$item.starttimestamp:$item.endtimestamp}">
                    {"%Y. %B %e"|shortdate:$item.starttimestamp:$item.endtimestamp}
                  </div>
                {elseif $item.starttimestamp and !$smarty.foreach.live.first}
                  <div class="timestamp" title="{"%Y-%m-%d "|shortdate:$item.starttimestamp:$item.endtimestamp:false}">
                    {"%Y-%m-%d "|shortdate:$item.starttimestamp:$item.endtimestamp:false}
                  </div>
                {/if}
              </div>
              <div class="title" title="{$item.title|escape:html}">{$item.title|escape:html|mb_wordwrap:25}</div>
            </div>
          </a>
        </li>
        {/foreach}
      </ul>
      <div class="clear"></div>
    </div>
    <div class="clear"></div>
  {elseif $block == "kiemelt" and !empty( $items )}
    <div class="featuredcontainer recording">
      {if !empty( $blocks.eloadas )}
        <h2>{$labels[$block]|escape:html}</h2>
      {/if}
      <ul>
        {foreach from=$items item=item name=recordings}
          {capture assign=recordingurl}{$language}/recordings/details/{$item.id},{$item.title|filenameize}{/capture}
          {assign var=isfirst value=$smarty.foreach.recordings.first}
          {if $isfirst}{assign var=type value="player"}{/if}
          <li class="listitem{if $isfirst} first{/if}">
            <a href="{$recordingurl}">
              <div class="recordingpic">
                <div class="length">{$item|@recordinglength|timeformat:minimal}</div>
                <img src="{$item|@indexphoto:$type}"/>
              </div>
              <div class="recordingcontent">
                <div class="wrap">
                  {assign var=contrib value=$item.presenters|@contributorformat:false}
                  {if $contrib}
                  <div class="presenter" title="{$contrib|escape:html}">{$contrib|mb_truncate:25|escape:html}</div>
                  {/if}
                  <div class="timestamp" title="{$item.recordedtimestamp|default:$item.timestamp|date_format:#smarty_dateformat_long#}">{$item.recordedtimestamp|default:$item.timestamp|date_format:#smarty_dateformat_long#}</div>
                </div>
                <div class="title" title="{$item.title|escape:html}">{$item.title|escape:html|mb_wordwrap:25}</div>
              </div>
            </a>
          </li>

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
