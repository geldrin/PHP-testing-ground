  <li class="listitem">
    <div class="recordingpic">
      <a href="{$language}/recordings/details/{$item.id},{$item.title|filenameize}"><span class="playpic"></span><img src="{$item|@indexphoto}"/></a>
    </div>
    <div class="recordingcontent">
      <div class="title">
        <h3><a href="{$language}/recordings/details/{$item.id},{$item.title|filenameize}">{$item.title|escape:html}</a></h3>
        {if $item.subtitle}<h4>{$item.subtitle|escape:html}</h4>{/if}
      </div>
      <div class="recordinginfo">
        <ul>
          <li><span class="bold">{#categories__recordedtimestamp#}:</span> <span>{$item.recordedtimestamp|date_format:#smarty_dateformat_long#}</span></li>
          <li><span class="bold">{#categories__recording_views#}:</span> <span>{$item.numberofviews}</span></li>
          <li>
            <div class="ratewidget" nojs="1">
              <div class="bold left">{#categories__recording_rating#}:</div>
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
      
      {if $item|@userHasAccess}
      <div class="recordingactions">
        <ul>
          <li><a href="{$language}/recordings/modifybasics/{$item.id}">{#recordings__editrecording#}</a></li>
        </ul>
      </div>
      {/if}
      
      <div class="clear"></div>
    </div>
  </li>
