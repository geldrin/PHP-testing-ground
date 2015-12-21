{include file="Visitor/_header.tpl" module="index"}

{if !empty( $recordings )}
  <div id="indexcontainer">
    <div class="leftdoublebox">
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
    
    <div class="rightbox">
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



{include file="Visitor/_footer.tpl"}
