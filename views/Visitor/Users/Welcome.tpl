{include file="Visitor/_header.tpl"}
<div id="contentsbody">
<h1>{#users__welcomepage_welcome#} {$member|nickformat|escape:html}!</h1>

<p>{#users__welcomepage_intro#}</p>

{if !empty( $channels )}
  <h2>{#users__welcomepage_courses#}</h2>
  <ul class="recordinglist recordingprogress">
    {foreach from=$channels item=item}
      <li class="listitem channel">
        <div class="recordingpic">
          <a href="{$language}/channels/details/{$item.id},{$item.title|filenameize}"><img src="{$item|@indexphoto}"/></a>
        </div>
        
        <div class="recordingcontent">
          <div class="title">
            <h3><a href="{$language}/channels/details/{$item.id},{$item.title|filenameize}">{$item.title|escape:html}</a></h3>
            {if $item.subtitle|stringempty}<h4>{$item.subtitle|escape:html}</h4>{/if}
            {if $item.starttimestamp}
              <div class="channeltimestamp">{#channels__timestamp#} {"%Y. %B %e"|shortdate:$item.starttimestamp:$item.endtimestamp}</div>
            {/if}
          </div>
          <div class="clear"></div>
          {if $item.recordingtowatch}
            <a class="continuerecording" href="{$language}/recordings/details/{$item.recordingtowatch.id},{$item.recordingtowatch.title|filenameize}?start={$item.recordingtowatch.lastposition}">{#users__continuerecording#}</a><br/>
          {/if}
          <a class="togglesmallrecordings" href="#">{#users__togglesmallrecordings#}</a>
        </div>
        <div class="clear"></div>
        <div class="smallrecordings hidden">
          <ul class="recordinglist">
            {foreach from=$item.recordings item=recording}
              {assign var=views value=$recording.numberofviews|numberformat}
              <li class="listitem">
                <div class="recordingpic">
                  <a href="{$language}/recordings/details/{$recording.id},{$recording.title|filenameize}"><span class="playpic"></span><img src="{$recording|@indexphoto}"/><span class="playprogress" title="{#recordings__progress#}: {$recording.positionpercent}% ({$recording.viewedminutes} {#recordings__embedmin#})">{$recording.positionpercent}%</span></a>
                </div>
                <div class="recordingcontent">
                  <div class="title">
                    <h3><a href="{$language}/recordings/details/{$recording.id},{$recording.title|filenameize}">{$recording.title|escape:html}</a></h3>
                    {if $recording.subtitle|stringempty}<h4>{$recording.subtitle|escape:html}</h4>{/if}
                  </div>
                  {if $recording.approvalstatus == 'approved' and $recording.status == 'onstorage'}
                    <span class="notpublished"><a href="{$language}/recordings/modifysharing/{$recording.id}?forward={$FULL_URI|escape:url}">{#recordings__notpublished_warning#}</a></span>
                  {/if}
                  <div class="recordinginfo">
                    <ul>
                      <li class="timestamp"><span></span>{$recording.recordedtimestamp|date_format:#smarty_dateformat_long#}</li>
                      <li class="views">{#recordings__recording_views#|sprintf:$views}</li>
                      <li class="rating last">
                        <div{if $recording.rating > 0} class="full"{/if}><span></span>1</div>
                        <div{if $recording.rating > 1.5} class="full"{/if}><span></span>2</div>
                        <div{if $recording.rating > 2.5} class="full"{/if}><span></span>3</div>
                        <div{if $recording.rating > 3.5} class="full"{/if}><span></span>4</div>
                        <div{if $recording.rating > 4.5} class="full"{/if}><span></span>5</div>
                      </li>
                    </ul>
                  </div>
                </div>
              </li>
            {/foreach}
          </ul>
        </div>
        <div class="clear"></div>
      </li>
    {/foreach}
  </ul>
{/if}

{if !empty( $accreditedrecordings )}
  <h2>{#users__welcomepage_accreditedrecordings#}</h2>
  <ul class="recordinglist recordingprogress">
    {foreach from=$accreditedrecordings item=recording}
      {assign var=views value=$recording.numberofviews|numberformat}
      <li class="listitem">
        <div class="recordingpic">
          <a href="{$language}/recordings/details/{$recording.id},{$recording.title|filenameize}"><span class="playpic"></span><img src="{$recording|@indexphoto}"/><span class="playprogress" title="{#recordings__progress#}: {$recording.positionpercent}% ({$recording.viewedminutes} {#recordings__embedmin#})">{$recording.positionpercent}%</span></a>
        </div>
        <div class="recordingcontent">
          <div class="title">
            <h3><a href="{$language}/recordings/details/{$recording.id},{$recording.title|filenameize}">{$recording.title|escape:html}</a></h3>
            {if $recording.subtitle|stringempty}<h4>{$recording.subtitle|escape:html}</h4>{/if}
          </div>
          {if $recording.approvalstatus == 'draft' and $recording.status == 'onstorage'}
            <span class="notpublished"><a href="{$language}/recordings/modifysharing/{$recording.id}?forward={$FULL_URI|escape:url}">{#recordings__notpublished_warning#}</a></span>
          {/if}
          <div class="recordinginfo">
            <ul>
              <li class="timestamp"><span></span>{$recording.recordedtimestamp|date_format:#smarty_dateformat_long#}</li>
              <li class="views">{#recordings__recording_views#|sprintf:$views}</li>
              <li class="rating last">
                <div{if $recording.rating > 0} class="full"{/if}><span></span>1</div>
                <div{if $recording.rating > 1.5} class="full"{/if}><span></span>2</div>
                <div{if $recording.rating > 2.5} class="full"{/if}><span></span>3</div>
                <div{if $recording.rating > 3.5} class="full"{/if}><span></span>4</div>
                <div{if $recording.rating > 4.5} class="full"{/if}><span></span>5</div>
              </li>
            </ul>
          </div>
        </div>
      </li>
    {/foreach}
  </ul>
{/if}

<h2>{#users__welcomepage_myoptions#}</h2>
<p>{#users__welcomepage_myoptions_intro#}</p>
<ul>
  <li><a href="{$language}/users/modify">{#users__welcomepage_mydata#}</a></li>
  <li><a href="{$language}/recordings/featured">{#users__welcomepage_recommended#}</a></li>
  <li><a href="{$language}/users/logout">{#users__welcomepage_logout#}</a></li>
</ul>

{if $member.isuploader}
  <h2>{#users__welcomepage_manage#}</h2>
  <p>{#users__welcomepage_manage_intro#}</p>
  <ul>
    <li><a href="{$language}/recordings/upload">{#users__welcomepage_upload_recordings#}</a></li>
    <li><a href="{$language}/recordings/myrecordings">{#users__welcomepage_myrecordings#}</a></li>
    <li><a href="{$language}/channels/mychannels">{#users__welcomepage_mychannels#}</a></li>
  </ul>
{/if}

{if $member.isnewseditor or $member.isclientadmin}
  <h2>{#users__welcomepage_admin_features#}</h2>
  <p>{#users__welcomepage_admin_intro#}</p>
  <ul>
    {if $member.isnewseditor or $member.isclientadmin}
      <li><a href="{$language}/organizations/createnews">{#users__welcomepage_create_news#}</a></li>
      <li><a href="{$language}/organizations/listnews">{#users__welcomepage_list_news#}</a></li>
    {/if}
    {if $member.isclientadmin}
      <li><a href="{$language}/organizations/modifyintroduction">{#users__welcomepage_org_intro#}</a></li>
      <li><a href="{$language}/users/admin">{#users__welcomepage_user_admin#}</a></li>
      <li><a href="{$language}/departments/admin">{#users__welcomepage_departments_admin#}</a></li>
      <li><a href="{$language}/groups">{#users__welcomepage_mygroups#}</a></li>
      <li><a href="{$language}/genres/admin">{#users__welcomepage_genres_admin#}</a></li>
      <li><a href="{$language}/categories/admin">{#users__welcomepage_categories_admin#}</a></li>
    {/if}
  </ul>
{/if}

</div>
{include file="Visitor/_footer.tpl"}