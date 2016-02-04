{assign var=views value=$item.numberofviews|numberformat}
<li class="listitem">
  <a name="rec{$item.id}"></a>
  <div class="recordingpic">
    <a href="{$recordingurl}">
      <img src="{$item|@indexphoto}"/>
      <div class="length">{$item|@recordinglength|timeformat:minimal}</div>
    </a>
  </div>
  
  <div class="recordingcontent">
    <div class="recordinginfo">
      <div class="timestamp">{$item.recordedtimestamp|date_format:#smarty_dateformat_long#}</div>
      <div class="views">{#recordings__recording_views#|sprintf:$views}</div>
      <div class="rating">
        <div{if $item.rating > 0} class="full"{/if}><span></span>1</div>
        <div{if $item.rating > 1.5} class="full"{/if}><span></span>2</div>
        <div{if $item.rating > 2.5} class="full"{/if}><span></span>3</div>
        <div{if $item.rating > 3.5} class="full"{/if}><span></span>4</div>
        <div{if $item.rating > 4.5} class="full"{/if}><span></span>5</div>
      </div>
    </div>

    <div class="title">
      {if preg_match( '/^onstorage$|^failed.*$/', $item.status )}
        <a href="{$language}/recordings/delete/{$item.id}?forward={$FULL_URI|escape:url}" title="{#recordings__deleterecording#}" class="confirm right"{if $item.isintrooutro} data-confirm="{#recordings__deleterecording_introoutro#}"{/if}>{#delete#}</a>
      {/if}
      {if $item.currentlyfeatured and $item|@userHasAccess}
        <a class="featured right" href="{$language}/recordings/modifysharing/{$item.id}?forward={$FULL_URI|escape:url}">{#recordings__currentlyfeatured#}</a>
      {/if}
      <h3><a href="{$language}/recordings/details/{$item.id},{$item.title|filenameize}">{$item.title|escape:html|mb_wordwrap:25}</a></h3>
      {if $item.subtitle|stringempty}<h4>{$item.subtitle|escape:html|mb_wordwrap:25}</h4>{/if}
    </div>
    {if $item.approvalstatus != 'approved' and $item.status == 'onstorage'}
      <span class="notpublished"><a href="{$language}/recordings/modifysharing/{$item.id}?forward={$FULL_URI|escape:url}">{#recordings__notpublished_warning#}</a></span>
    {/if}

    <div class="status"><span class="bold">{#recordings__recording_status#}:</span>
    {if $item.approvalstatus != 'approved' and $item.status == 'onstorage'}
      {#recordings__waitingforpublish#}&nbsp;(<span class="status-{$item.status}">{l lov=recordingstatus key=$item.status}</span>)
    {elseif preg_match( '/^converting/', $item.status )}
      <span class="status-{$item.status}">{l lov=recordingstatus key=unavailable}</span>
    {elseif preg_match( '/^failed.*$/', $item.status )}
      <span class="status-failed">{l lov=recordingstatus key=failed}</span>
    {else}
      <span class="status-{$item.status}">{l lov=recordingstatus key=$item.status}</span>
    {/if}
    </div>
    <div class="recordingactions">
      <ul>
        {if $item.isintrooutro}
          <li>{#recordings__introoutrorecording#}</li>
        {/if}

        {if $item.status == 'onstorage'}
          <li><a href="{$language}/recordings/modifybasics/{$item.id}?forward={$FULL_URI|escape:url}">{#recordings__editrecording#}</a></li>
          {if !$item.isintrooutro}
            <li><a href="{$language}/recordings/uploadsubtitle/{$item.id}?forward={$FULL_URI|escape:url}">{#recordings__uploadsubtitle#}</a></li>
            <li><a href="{$language}/recordings/uploadattachment/{$item.id}?forward={$FULL_URI|escape:url}">{#recordings__manageattachments#}</a></li>
            {if $item.canuploadcontentvideo}
              <li><a href="{$language}/recordings/uploadcontent/{$item.id}?forward={$FULL_URI|escape:url}">{#recordings__uploadcontentvideo#}</a></li>
            {/if}
          {/if}
        {/if}
      </ul>
    </div>

    {if $item.contentstatus}
      <div class="recordinginfo recordingcontentinfo">
        <ul>
          <li class="last"><span class="bold">{#recordings__contentrecording_status#}:</span>
          {if preg_match( '/^converting/', $item.contentstatus )}
            {l lov=recordingstatus key=unavailable assign=contentstatus}
          {elseif preg_match( '/^failed.*$/', $item.contentstatus )}
            {l lov=recordingstatus key=failed assign=contentstatus}
          {else}
            {l lov=recordingstatus key=$item.contentstatus assign=contentstatus}
          {/if}
          <span class="status-{$item.status}">{$contentstatus}</span>
          {if $item.contentstatus == 'onstorage' or preg_match( '/^onstorage$|^failed.*$/', $item.contentstatus )}
            <a href="{$language}/recordings/deletecontent/{$item.id}?forward={$FULL_URI|escape:url}" class="confirm delete">{#recordings__deletecontent#}</a>
          {/if}
          </li>
        </ul>
      </div>
    {/if}

    {if !empty( $item.subtitlefiles )}
      <div class="subtitles">
        <h3>{#recordings__subtitles#}</h3>
        <ul>
          {foreach from=$item.subtitlefiles item=subtitle}
            <li>{$subtitle.language} - <a href="{$language}/recordings/deletesubtitle/{$subtitle.id}?forward={$FULL_URI|escape:url}" class="confirm delete">{#recordings__deletesubtitle#}</a></li>
          {/foreach}
        </ul>
      </div>
    {/if}

    <div class="clear"></div>
  </div>
</li>
