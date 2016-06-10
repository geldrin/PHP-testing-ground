<li class="listitem channel">
  <div class="recordingpic">
    <a href="{$language}/live/details/{$item.id},{$item.title|filenameize}"><img src="{$item|@indexphoto:'livethumb'}"/></a>
  </div>
  
  <div class="recordingcontent">
    <div class="title">
      <h3><a href="{$language}/live/details/{$item.id},{$item.title|filenameize}">{$item.title|escape:html|mb_wordwrap:25}</a></h3>
      {if $item.subtitle|stringempty}<h4>{$item.subtitle|escape:html|mb_wordwrap:25}</h4>{/if}
      {if $item.relatedchannelid}<a class="relatedchannel" href="{$language}/channels/details/{$item.relatedchannelid},{$item.title|filenameize}">{#live__view_archive#}</a>{/if}
      {if $item.starttimestamp}
        <div class="channeltimestamp">{#channels__timestamp#} {"%Y. %B %e"|shortdate:$item.starttimestamp:$item.endtimestamp}</div>
      {/if}
    </div>
    {if $item.description}<p>{$item.description|mb_truncate:400|escape:html}</p>{/if}
    <div class="clear"></div>
  </div>
</li>
