{if $item.type == 'channel'}
TODO
{else}
  {assign var=views value=$item.numberofviews|numberformat}
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
          <li class="timestamp"><span></span>{$item.recordedtimestamp|date_format:#smarty_dateformat_long#}</li>
          <li class="views">{#recordings__recording_views#|sprintf:$views}</li>
          <li class="rating last">
            <div{if $item.rating > 0} class="full"{/if}><span></span>1</div>
            <div{if $item.rating > 1.5} class="full"{/if}><span></span>2</div>
            <div{if $item.rating > 2.5} class="full"{/if}><span></span>3</div>
            <div{if $item.rating > 3.5} class="full"{/if}><span></span>4</div>
            <div{if $item.rating > 4.5} class="full"{/if}><span></span>5</div>
          </li>
        </ul>
      </div>
      {if $item|@userHasAccess}
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
      
      <div class="clear"></div>
    </div>
  </li>
{/if}