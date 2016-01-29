{if $recording.mediatype == 'audio' and isset( $flashdata.subtitle_files )}
{assign var=flashheight value=140}
{elseif $recording.mediatype == 'audio'}
{assign var=flashheight value=60}
{else}
{assign var=flashheight value=550}
{/if}
{include file="Visitor/_header.tpl" title=$recording.title}

<div id="player"{if !$browser.mobile} style="height: {$flashheight}px;"{/if}>
  
  {if $browser.mobile}
    {if $browser.mobiledevice == 'iphone'}
      <div id="mobileplayercontainer">
        <video x-webkit-airplay="allow" controls="controls" alt="{$recording.title|escape:html}" width="280" height="165" poster="{$recording|@indexphoto}" src="{$mobilehttpurl}">
          <a href="{$mobilehttpurl}"><img src="{$recording|@indexphoto}" width="280" height="190"/></a>
        </video>
      </div>
    {else}
      {if $recording.mediatype == 'audio'}
        {assign var=mobileurl value=$audiofileurl}
      {elseif $bootstrap->config.ondemandandroidhls}
        {assign var=mobileurl value=$mobilehttpurl}
      {else}
        {assign var=mobileurl value=$mobilertspurl}
      {/if}
      <div id="mobileplayercontainer">
        <a href="{$mobileurl}"><img src="{$recording|@indexphoto}" width="280" height="165"/></a>
      </div>
    {/if}
    {if count( $mobileversions ) > 1}
      <div id="qualitychooser">
        <ul>
          {foreach from=$mobileversions item=version}
            <li{if $activemobileversion.qualitytag == $version} class="active"{/if}><a href="{$language}/recordings/details/{$recording.id},{$recording.title|filenameize}?quality={$version|escape:url}">{$version|escape:html}</a></li>
          {/foreach}
        </ul>
      </div>
    {/if}
    <br/>
  {else}
    <div id="playercontainer{if $recording.mediatype == 'audio'}audio{/if}">{#recordings__noflash#}</div>
    <script type="text/javascript">
      swfobject.embedSWF('flash/VSQPlayer.swf?v={$VERSION}', 'playercontainer{if $recording.mediatype == 'audio'}audio{if isset( $flashdata.subtitle_files )}subtitle{/if}{/if}', '980', '{$flashheight}', '11.1.0', 'flash/swfobject/expressInstall.swf', {$flashdata|@jsonescape:true}, flashdefaults.params, null, handleFlashLoad );
    </script>
  {/if}
</div>

<div class="title recording">
  <h1>{$recording.title|escape:html|mb_wordwrap:25}</h1>
  <h2><span class="timestamp">{$recording.timestamp|date_format:#smarty_dateformat_long#}</span> {$recording.subtitle|escape:html|mb_wordwrap:25}</h2>

  {if $recording.approvalstatus != 'approved'}
    <center><a href="{$language}/recordings/modifysharing/{$recording.id}">{#recordings__notpublished_warning#}</a></center>
    <br/>
  {/if}
</div>

<div id="infobar">
  <ul class="left">
    <li id="recordinguploader">
      <div class="avatar">
        <img src="{$author|@avatarphoto}" width="36" height="36"/>
      </div>
      <div class="content">
        <div class="uploader">{$author|@nickformat|mb_wordwrap:20|escape:html}</div>
        <div class="timestamp" title="{#recordings__details_recordedtimestamp#}">{$recording.recordedtimestamp|date_format:#smarty_dateformat_long#}</div>
      </div>
    </li>
    <li id="recordingviews">
      <div class="views">{$recording.numberofviews|numberformat}</div>
      <div class="label">{#recordings__numberofviews#}</div>
    </li>
    <li id="rating">
      {assign var=numberofratings value=$recording.numberofratings|numberformat}
      <div class="ratewidget" data-canrate="{$canrate}">
        <span class="spinner"></span>
        <ul>
          <li{if $recording.rating > 0} class="full"{/if}><a href="{$language}/recordings/rate/{$recording.id}?rating=1"><span></span>1</a></li>
          <li{if $recording.rating > 1.5} class="full"{/if}><a href="{$language}/recordings/rate/{$recording.id}?rating=2"><span></span>2</a></li>
          <li{if $recording.rating > 2.5} class="full"{/if}><a href="{$language}/recordings/rate/{$recording.id}?rating=3"><span></span>3</a></li>
          <li{if $recording.rating > 3.5} class="full"{/if}><a href="{$language}/recordings/rate/{$recording.id}?rating=4"><span></span>4</a></li>
          <li{if $recording.rating > 4.5} class="full"{/if}><a href="{$language}/recordings/rate/{$recording.id}?rating=5"><span></span>5</a></li>
        </ul>
      </div>
      <div class="label">{#recordings__ratewidgetheading#|sprintf:$numberofratings}</div>
    </li>
  </ul>
  <ul class="right">
    <li id="infolink" class="active"><a href="#"><span></span>{#recordings__info#}</a></li>
    {if $member.id}
      <li id="channelslink"><a href="#"><span></span>{#recordings__addtochannel#}</a></li>
    {/if}
    <li id="commentslink"><a href="#"><span id="commentcount" data-commentcount="{$commentcount|default:'0'}">{$commentcount|default:'0'}</span> <span id="commentslinktext">{#recordings__comments#}</span></a></li>
    {if $bootstrap->config.loadaddthis}
    <li id="sharelink"><a href="#" title="{#recordings__share#}"><span></span>{#recordings__share#}</a></li>
    {/if}
    <li id="embedlink"><a href="#" title="{#recordings__embed#}"><span></span>{#recordings__embed#}</a></li>
  </ul>
  <div class="clear"></div>
</div>

<div id="embed">
  {capture assign=embed}
    <iframe width="480" height="{$height}" src="{$BASE_URI}recordings/embed/{$recording.id}" frameborder="0" allowfullscreen="allowfullscreen"></iframe>
  {/capture}
  <label for="embedcode">{#recordings__embedcode#}:</label><br/>
  <textarea id="embedcode" data-fullscaleheight="{$flashheight}" data-normalheight="{$height}">{$embed|trim|escape:html}</textarea>
  <div class="settings">{#recordings__embedsettings#}:</div>
  <div class="settingrow">
    <label for="embedautoplay">{#recordings__embedautoplay#}:</label>
    <input type="radio" name="embedautoplay" id="embedautoplay_no" checked="checked" value="0"/>
    <label for="embedautoplay_no">{#no#}</label>
    <input type="radio" name="embedautoplay" id="embedautoplay_yes" value="1"/>
    <label for="embedautoplay_yes">{#yes#}</label>
  </div>
  <div class="settingrow">
    <label for="embedfullscale">{#recordings__embedfullscale#}:</label>
    <input type="radio" name="embedfullscale" id="embedfullscale_no" checked="checked" value="0"/>
    <label for="embedfullscale_no">{#no#}</label>
    <input type="radio" name="embedfullscale" id="embedfullscale_yes" value="1"/>
    <label for="embedfullscale_yes">{#yes#}</label>
  </div>
  <div class="settingrow">
    <label for="embedstart">{#recordings__embedstart#}:</label>
    <input type="text" value="00" maxlength="2" id="embedstart_h" class="inputtext"/> {#recordings__embedhour#}
    <input type="text" value="00" maxlength="2" id="embedstart_m" class="inputtext"/> {#recordings__embedmin#}
    <input type="text" value="00" maxlength="2" id="embedstart_s" class="inputtext"/> {#recordings__embedsec#}
  </div>
</div>

<div id="info">
  {if $recording|@userHasAccess}
    <a id="recordingmodify" class="submitbutton" target="_blank" href="{$language}/recordings/modifybasics/{$recording.id}?forward={$FULL_URI|escape:url}">{#recordings__editrecording#}</a>
  {/if}

  <div id="presenters">
    <h3>{#recordings__presenters#}</h3>
    {if !empty( $recording.presenters )}
      {include file=Visitor/presenters.tpl presenters=$recording.presenters}
    {/if}
  </div>

  {if !empty( $attachments )}
    <div id="attachments">
      <h3>{#recordings__manageattachments_title#}</h3>
      <ul>
        {foreach from=$attachments item=attachment}
          <li><a href="{$attachment|@attachmenturl:$recording:$STATIC_URI}">{$attachment.title|escape:html}</a></li>
        {/foreach}
      </ul>
    </div>
  {/if}

  {if $recording.description|stringempty}
    <p id="recordingdescription">{$recording.description|escape:html|autolink|nl2br}</p>
  {/if}

  <div id="copyright">
    <p>{$recording.copyright|escape:html|default:#recordings__nocopyright#}</p>
  </div>

  <table id="metadatatable">
    {if $recording.keywords|stringempty}
      <tr>
        <td class="labelcolumn">{#recordings__keywords#}:</td>
        <td>{$recording.keywords|escape:html}</td>
      </tr>
    {/if}
    <tr>
      <td class="labelcolumn">{#recordings__recordlength#}:</td>
      <td>{$recording|@recordinglength|timeformat}</td>
    </tr>
    <tr>
      <td class="labelcolumn">{#recordings__details_recordedtimestamp#}:</td>
      <td>{$recording.recordedtimestamp|date_format:#smarty_dateformat_long#}</td>
    </tr>
    <tr>
      <td class="labelcolumn">{#recordings__details_uploadtimestamp#}:</td>
      <td>{$recording.timestamp|date_format:#smarty_dateformat_long#}</td>
    </tr>
  </table>

  <a id="detaillink" href="#" data-show="{#recordings__showdetails#|escape:html}" data-hide="{#recordings__hidedetails#|escape:html}">{#recordings__showdetails#}</a>
</div>

{if $member.id}
<div id="channels">
  <h3>{#recordings__addtochannel_title#}</h3>
  <ul id="channelslist">
    {include file=Visitor/Recordings/Details_channels.tpl level=1}
  </ul>
</div>
{/if}

{if $bootstrap->config.loadaddthis}
<div id="share">
  <br/>
  <div class="addthis_toolbox addthis_default_style addthis_32x32_style">
    <a class="addthis_button_preferred_1"></a>
    <a class="addthis_button_preferred_2"></a>
    <a class="addthis_button_preferred_3"></a>
    <a class="addthis_button_preferred_4"></a>
    <a class="addthis_button_compact"></a>
    <a class="addthis_counter addthis_bubble_style"></a>
  </div>
  <script type="text/javascript" src="//s7.addthis.com/js/250/addthis_widget.js#pubid=xa-5045da4260dfe0a6"></script>
</div>
{/if}

{if !empty( $recordingdownloads )}
<div id="recordingdownloads"{if $recording|@userHasAccess} class="closer"{/if}>
  <a href="#" class="submitbutton">{#recordings__recordingdownloads#}</a>
  <ul>
    {foreach from=$recordingdownloads key=key item=item}
      {assign var=localekey value="recordingdownloads_$key"}
      {assign var=itemlocale value=$l->get('recordings', $localekey, $language)}
      <li><a href="{$item.url}">{$itemlocale|sprintf:$item.qualitytag}</a></li>
    {/foreach}
  </ul>
</div>
{/if}

<div id="comments">
  <div class="loading"></div>
  <div class="title"><h3>{#recordings__comments#}</h3></div>
  <div id="commentwrap">
    {$commentoutput.html}
  </div>

  {if $member.id or $recording.isanonymouscommentsenabled}
    {if !$anonuser.id and $bootstrap->config.recaptchaenabled}
    <script src="https://www.google.com/recaptcha/api.js?render=explicit" async defer></script>
    {/if}
    <div id="commentform" data-needrecaptcha="{if !$anonuser.id and !$member.id and $bootstrap->config.recaptchaenabled}1{else}0{/if}">
      <div class="loading"></div>
      {$commentform}
    </div>
  {/if}
</div>
<div class="clear"></div>

{if !empty( $relatedvideos )}
<div class="accordion active" id="recommendatory">
  <h2>{#recordings__relatedvideos#}</h2>
  <ul>
    {foreach from=$relatedvideos item=item}
      {include file="Visitor/minirecordinglistitem.tpl"}
    {/foreach}
  </ul>
</div>
{/if}

{include file="Visitor/_footer.tpl"}