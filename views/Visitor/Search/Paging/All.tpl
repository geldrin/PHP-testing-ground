  <li class="listitem">
    <a name="rec{$item.id}"></a>
    <div class="recordingpic">
      <a href="{$language}/recordings/details/{$item.id},{$item.title|filenameize}"><span class="playpic"></span><img src="{$item|@indexphoto}"/></a>
    </div>
    
    <div class="recordingcontent">
      <div class="title">
        {if preg_match( '/^onstorage$|^failed.*$/', $item.status )}
          <a href="{$language}/recordings/delete/{$item.id}?forward={$FULL_URI|escape:url}" title="{#recordings__deleterecording#}" class="confirm right">{#delete#}</a>
        {/if}
        <h3><a href="{$language}/recordings/details/{$item.id},{$item.title|filenameize}">{$item.title|escape:html}</a></h3>
        {if $item.subtitle|stringempty}<h4>{$item.subtitle|escape:html}</h4>{/if}
      </div>
      {if !$item.ispublished and $item.status == 'onstorage'}
        <span class="notpublished"><a href="{$language}/recordings/modifysharing/{$item.id}?forward={$FULL_URI|escape:url}">{#recordings__notpublished_warning#}</a></span>
      {/if}
      <div class="recordinginfo">
        <ul>
          <li><span class="bold">{#recordings__recording_status#}:</span>
          {if !$item.ispublished and $item.status == 'onstorage'}
            {#recordings__waitingforpublish#}&nbsp;(<span class="status-{$item.status}">{l lov=recordingstatus key=$item.status}</span>)
          {elseif preg_match( '/^converting/', $item.status )}
            <span class="status-{$item.status}">{l lov=recordingstatus key=unavailable}</span>
          {else}
            <span class="status-{$item.status}">{l lov=recordingstatus key=$item.status}</span>
          {/if}
          </li>
          <li><span class="bold">{#recordings__recording_views#}:</span> <span>{$item.numberofviews}</span></li>
          <li>
            <div class="ratewidget" data-nojs="1">
              <div class="bold left">{#recordings__recording_rating#}:</div>
              <ul>
                <li{if $item.rating > 0} class="full"{/if}><a><span></span>1</a></li>
                <li{if $item.rating > 1.5} class="full"{/if}><a><span></span>2</a></li>
                <li{if $item.rating > 2.5} class="full"{/if}><a><span></span>3</a></li>
                <li{if $item.rating > 3.5} class="full"{/if}><a><span></span>4</a></li>
                <li{if $item.rating > 4.5} class="full"{/if}><a><span></span>5</a></li>
              </ul>
            </div>
          </li>
        </ul>
      </div>
      {if $item.status == 'onstorage'}
      <div class="recordingactions">
        <ul>
          <li><a href="{$language}/recordings/modifybasics/{$item.id}?forward={$FULL_URI|escape:url}">{#recordings__editrecording#}</a></li>
          <li><a href="{$language}/recordings/uploadsubtitle/{$item.id}?forward={$FULL_URI|escape:url}">{#recordings__uploadsubtitle#}</a></li>
          {*}
          <li><a href="{$language}/recordings/uploadattachment?recordingid={$item.id}&forward={$FULL_URI|escape:url}">{#recordings__manageattachments#}</a></li>
          {/*}
          {if $item.canuploadcontentvideo}
            <li><a href="{$language}/recordings/uploadcontent/{$item.id}?forward={$FULL_URI|escape:url}">{#recordings__uploadcontentvideo#}</a></li>
          {/if}
        </ul>
      </div>
      {/if}
      {if $item.contentstatus}
        <div class="recordinginfo recordingcontentinfo">
          <ul>
            <li><span class="bold">{#recordings__contentrecording_status#}:</span>
            {if preg_match( '/^converting/', $item.contentstatus )}
              {l lov=recordingstatus key=unavailable assign=contentstatus}
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
