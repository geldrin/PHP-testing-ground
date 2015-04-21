{assign var=views value=$item.numberofviews|numberformat}
{capture assign=recordingurl}{$language}/recordings/details/{$item.id},{$item.title|filenameize}{/capture}
<li class="listitem">
  {if $order == 'channels' and $havemultiplechannels and $currentchannelid != $item.channelid}
    {php}
    // mert file szintuek a fileban {assign}-olt valtozok, nekunk meg globalis kell
    $item = $this->get_template_vars('item');
    
    if (
         !isset( $GLOBALS['currentchannelid'] ) or
         $GLOBALS['currentchannelid'] != $item['channelid']
       ) {
      
      $this->assign('printchanneltitle', true );
      $GLOBALS['currentchannelid'] = $item['channelid'];
      
    }
    {/php}
    
    {if $printchanneltitle}
      <div class="channeltitle">
        <a href="{$language}/channels/details/{$item.channelid},{$item.channeltitle|filenameize}">{$item.channeltitle|escape:html}</a>
      </div>
    {/if}
  {/if}
  <a name="rec{$item.id}"></a>
  <div class="recordingpic">
    <a href="{$recordingurl}"><span class="playpic"></span><img src="{$item|@indexphoto}"/></a>
  </div>
  
  <div class="recordingcontent">
    <div class="title">
      <h3><a href="{$recordingurl}">{$item.title|escape:html|mb_wordwrap:25}</a></h3>
      {if $item.subtitle|stringempty}<h4>{$item.subtitle|escape:html|mb_wordwrap:25}</h4>{/if}
    </div>
    {if $item.approvalstatus != 'approved' and $item.status == 'onstorage' and $member.id}
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
    
    {if !empty( $item.presenters )}
      <div class="presenterswrap">
        <div class="label">{#recordings__presenters#}:</div>
        <div class="presenters">
          {include file=Visitor/presenters.tpl presenters=$item.presenters}
        </div>
      </div>
    {/if}

    {if !empty( $item.slides )}
    <div class="recordingslides">
      <div class="label">{#recordings__slidesearchhits#}:</div>
      {foreach from=$item.slides item=slide name=slide}
      {if $smarty.foreach.slide.iteration <= 12}
      <div class="slide">
          <a href="{$recordingurl}?start={$slide.positionsec}">
            <img src="{$slide|@slidephoto:$STATIC_URI}" width="100"/>
          </a>
          <span>{$slide.positionsec|timeformat}</span>
        </a>
      </div>
      {/if}
      {/foreach}
    </div>
    {/if}

    {if $item|@userHasAccess}
    <div class="recordingactions">
      <ul>
        <li><a href="{$language}/recordings/modifybasics/{$item.id}?forward={$FULL_URI|escape:url}">{#recordings__editrecording#}</a></li>
        {if !$item.isintrooutro and preg_match( '/^onstorage$|^failed.*$/', $item.status )}
          <li><a href="{$language}/recordings/delete/{$item.id}?forward={$FULL_URI|escape:url}" title="{#recordings__deleterecording#}" class="confirm right">{#delete#|ucfirst}</a></li>
        {/if}
      </ul>
    </div>
    {/if}
    
    <div class="clear"></div>
  </div>
</li>