{if $smarty.request.chromeless or $nolayout}
  {include file="Visitor/_header_nolayout.tpl" bodyclass=$bodyclass|default:liveiframe}
  {assign var=linksinnewwindow value=' target="_blank"'}
{else}
  {include file="Visitor/_header.tpl"}
{/if}

<div id="pagetitle">
  <h1>{#users__info_title#} - {$user|@nameformat|escape:html} <a class="back" href="{$forward|escape:html}">{#sitewide_back#|lower}</a></h1>
</div>
<div class="channelgradient"></div>
<br/>

{if !empty( $channels.recordings )}
  <ul class="recordinglist recordingprogress">
    {foreach from=$channels.recordings item=item}
      {assign var=views value=$item.numberofviews|numberformat}
      <li class="listitem">
        <div class="recordingpic">
          <a href="{$language}/recordings/details/{$item.id},{$item.title|filenameize}"><img src="{$item|@indexphoto}"/><span class="playprogress" title="{#recordings__progress#}: {$item.positionpercent}% ({$item.viewedminutes} {#recordings__embedmin#})">{$item.positionpercent}%</span></a>
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
            {if $item.currentlyfeatured and $item|@userHasAccess}
              <a class="featured right" href="{$language}/recordings/modifysharing/{$item.id}?forward={$FULL_URI|escape:url}">{#recordings__currentlyfeatured#}</a>
            {/if}
            <h3><a href="{$recordingurl}">{$item.title|escape:html|mb_wordwrap:25}</a></h3>
            {if $item.subtitle|stringempty}<h4>{$item.subtitle|escape:html|mb_wordwrap:25}</h4>{/if}
          </div>
          {if $item.approvalstatus != 'approved' and $item.status == 'onstorage' and $member.id}
            <span class="notpublished"><a href="{$language}/recordings/modifysharing/{$item.id}?forward={$FULL_URI|escape:url}">{#recordings__notpublished_warning#}</a></span>
          {/if}
          {if !empty( $item.presenters )}
            <div class="presenterswrap">
              <div class="label">{#recordings__presenters#}:</div>
              <div class="presenters">
                {include file=Visitor/presenters.tpl presenters=$item.presenters}
              </div>
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
    {/foreach}
  </ul>
{/if}

{if $channels.channelcount}
  <ul class="recordinglist recordingprogress">
    {section loop=$channels.channelcount name=channels}
      {assign var=item value=$channels[$smarty.section.channels.index]}
      <li class="listitem channel">
        <div class="recordingpic">
          <a href="{$language}/channels/details/{$item.id},{$item.title|filenameize}"><img src="{$item|@indexphoto}"/></a>
        </div>
        
        <div class="recordingcontent">
          <div class="title">
            <h3><a href="{$language}/channels/details/{$item.id},{$item.title|filenameize}">{$item.title|escape:html|mb_wordwrap:25}</a></h3>
            {if $item.subtitle|stringempty}<h4>{$item.subtitle|escape:html|mb_wordwrap:25}</h4>{/if}
            {if $item.starttimestamp}
              <div class="channeltimestamp">{#channels__timestamp#} {"%Y. %B %e"|shortdate:$item.starttimestamp:$item.endtimestamp}</div>
            {/if}
          </div>
          <div class="clear"></div>
        </div>
        <div class="clear"></div>
        <div class="smallrecordings">
          <ul class="recordinglist">
            {foreach from=$item.recordings item=recording}
              {assign var=views value=$recording.numberofviews|numberformat}
              <li class="listitem">
                <div class="recordingpic">
                  <a href="{$language}/recordings/details/{$recording.id},{$recording.title|filenameize}"><img src="{$recording|@indexphoto}"/><span class="playprogress" title="{#recordings__progress#}: {$recording.positionpercent}% ({$recording.viewedminutes} {#recordings__embedmin#})">{$recording.positionpercent}%</span></a>
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
                    {if $item.currentlyfeatured and $item|@userHasAccess}
                      <a class="featured right" href="{$language}/recordings/modifysharing/{$item.id}?forward={$FULL_URI|escape:url}">{#recordings__currentlyfeatured#}</a>
                    {/if}
                    <h3><a href="{$recordingurl}">{$item.title|escape:html|mb_wordwrap:25}</a></h3>
                    {if $item.subtitle|stringempty}<h4>{$item.subtitle|escape:html|mb_wordwrap:25}</h4>{/if}
                  </div>
                  {if $item.approvalstatus != 'approved' and $item.status == 'onstorage' and $member.id}
                    <span class="notpublished"><a href="{$language}/recordings/modifysharing/{$item.id}?forward={$FULL_URI|escape:url}">{#recordings__notpublished_warning#}</a></span>
                  {/if}
                </div>
              </li>
            {/foreach}
          </ul>
        </div>
        <div class="clear"></div>
      </li>
    {/section}
  </ul>
{/if}

{if $smarty.request.chromeless or $nolayout}
  {include file="Visitor/_footer_nolayout.tpl"}
{else}
  {include file="Visitor/_footer.tpl"}
{/if}