<li class="listitem">
  <div class="recordingcontent">
    <div class="title">
      {if $streamingactive}
        <a href="{$language}/live/view/{$item.livefeedid},{$feeds[$item.livefeedid].name|filenameize}" class="livefeed" title="{#live__feedislive#}">{#live__feedislive#}</a>
      {/if}
      <h3>{$item.title|escape:html}</h3>
      {if $item.subtitle|stringempty}<h4>{$item.subtitle|escape:html}</h4>{/if}
    </div>
    {if !$item.ispublished and $item.status == 'onstorage'}
      <span class="notpublished"><a href="{$language}/recordings/modifysharing/{$item.id}?forward={$FULL_URI|escape:url}">{#recordings__notpublished_warning#}</a></span>
    {/if}
    <div class="recordinginfo">
      <ul>
        <li class="timestamp last"><span></span>{$item.recordedtimestamp|date_format:#smarty_dateformat_long#}</li>
      </ul>
    </div>
    {if $item|@userHasAccess}
    <div class="recordingactions">
      <ul>
        <li><a href="{$language}/recordings/modifybasics/{$item.id}?forward={$FULL_URI|escape:url}">{#recordings__editrecording#}</a></li>
      </ul>
    </div>
    {/if}
    
    <div class="clear"></div>
  </div>
</li>