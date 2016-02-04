{capture assign=recordingurl}{$language}/recordings/details/{$item.id},{$item.title|filenameize}{/capture}
{if $isfirst}{assign var=type value="player"}{/if}
<li class="listitem{if $isfirst} first{/if}">
  <a href="{$recordingurl}">
    <div class="recordingpic">
      <div class="length">{$item|@recordinglength|timeformat:minimal}</div>
      <img src="{$item|@indexphoto:$type}"/>
    </div>
    <div class="recordingcontent">
      <div class="wrap">
        <div class="presenter" title="{$item.presenters|@contributorformat:false|escape:html}">{$item.presenters|@contributorformat:false|mb_truncate:25|escape:html}</div>
        <div class="timestamp" title="{$item.recordedtimestamp|default:$item.timestamp|date_format:#smarty_dateformat_long#}">{$item.recordedtimestamp|default:$item.timestamp|date_format:#smarty_dateformat_long#}</div>
      </div>
      <div class="title" title="{$item.title|escape:html}">{$item.title|escape:html|mb_wordwrap:25}</div>
    </div>
  </a>
</li>
