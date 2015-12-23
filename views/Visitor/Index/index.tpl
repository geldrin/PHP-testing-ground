{include file="Visitor/_header.tpl" module="index"}

{if !empty( $recordings )}
  <div id="indexcontainer">
    <div class="indexleft">
      {assign var=item value=$recordings[0]}
      <a class="imageinfo wlarge" href="{$language}/recordings/details/{$item.id},{$item.title|filenameize}" title="{$item.title|escape:html}">
        <img src="{$item|@indexphoto:player}"/>
        <div class="imageinfowrap">
          <div class="content">
            <div class="presenter">{$item.presenters|@contributorformat:false|mb_truncate:60|escape:html}</div>
            <div class="timestamp">{$item.recordedtimestamp|default:$item.timestamp|date_format:#smarty_dateformat_long#}</div>
            <h1>{$item.title|mb_truncate:60|escape:html}</h1>
          </div>
          <div class="length">{$item|@recordinglength|timeformat:minimal}</div>
        </div>
      </a>
      
    </div>
    
    <div class="indexright">
      <ul>
      {*} skip the first recording as we have already printed it above {/*}
      {section name=rightbox start=1 loop=$recordings}
        {assign var=item value=$recordings[rightbox]}
        {capture assign=recordingurl}{$language}/recordings/details/{$item.id},{$item.title|filenameize}{/capture}
        <li>
          <a class="imageinfo wwide" href="{$recordingurl}" title="{$item.title|escape:html}">
            <img src="{$item|@indexphoto:wide}"/>
            <div class="imageinfowrap">
              <div class="length">{$item|@recordinglength|timeformat:minimal}</div>
            </div>
          </a>
          <div class="recordingcontent">
            <div class="presenter">{$item.presenters|@contributorformat:false|mb_truncate:60|escape:html}</div>
            <div class="timestamp">{$item.recordedtimestamp|default:$item.timestamp|date_format:#smarty_dateformat_long#}</div>
            <div class="title">
              <a href="{$recordingurl}">{$item.title|escape:html|mb_wordwrap:25}</a>
            </div>
          </div>
        </li>
      {/section}
      </ul>
    </div>
    <div class="clear"></div>
  </div>
{/if}

<div class="clear"></div>
<div class="accordion active">
  <h2><a href="#">{#index__mostviewed#}</a></h2>
  <ul>
    {foreach from=$mostviewed item=item}
      {include file="Visitor/recordinglistitem.tpl"}
    {/foreach}
  </ul>
</div>

<div class="accordion">
  <h2><a href="#">{#index__newest#}</a></h2>
  <ul>
    {foreach from=$newest item=item}
      {include file="Visitor/recordinglistitem.tpl"}
    {/foreach}
  </ul>
</div>

<div class="accordion">
  <h2><a href="#">{#index__featured#}</a></h2>
  <ul>
    {foreach from=$featured item=item}
      {include file="Visitor/recordinglistitem.tpl"}
    {/foreach}
  </ul>
</div>

{include file="Visitor/_footer.tpl"}
