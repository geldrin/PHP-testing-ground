{if $recording.mediatype == 'audio' and isset( $flashdata.subtitle_files )}
{assign var=flashheight value=140}
{assign var=pagebgclass value=mediumheight}
{elseif $recording.mediatype == 'audio'}
{assign var=flashheight value=60}
{assign var=pagebgclass value=minheight}
{else}
{assign var=flashheight value=530}
{assign var=pagebgclass value=fullheight}
{/if}
{include file="Visitor/_header.tpl" title=$recording.title pagebgclass=$pagebgclass}
<div class="title recording">
  {if $recording.approvalstatus != 'approved'}
    <center><a href="{$language}/recordings/modifysharing/{$recording.id}">{#recordings__notpublished_warning#}</a></center>
    <br/>
  {/if}
  
  <h1>{$recording.title|escape:html}</h1>
  
  {if $recording.subtitle|stringempty}
    <h2>{$recording.subtitle|escape:html}</h2>
  {/if}
  
  {if !empty( $recording.presenters )}
    {include file=Visitor/presenters.tpl presenters=$recording.presenters}
  {/if}
  
</div>

<div id="player"{if !$browser.mobile} style="height: {$flashheight}px;"{/if}>
  
  {if $browser.mobile}
    {if $browser.mobiledevice == 'iphone'}
      <div id="mobileplayercontainer">
        <video x-webkit-airplay="allow" controls="controls" alt="{$recording.title|escape:html}" width="280" height="165" poster="{$recording|@indexphoto}" src="{$mobilehttpurl}">
          <a href="{$mobilehttpurl}"><img src="{$recording|@indexphoto}" width="280" height="190"/></a>
        </video>
      </div>
    {else}
      <div id="mobileplayercontainer">
        <a href="{if $recording.mediatype == 'audio'}{$audiofileurl}{else}{$mobilertspurl}{/if}"><img src="{$recording|@indexphoto}" width="280" height="165"/></a>
      </div>
    {/if}
    {if count( $mobileversions ) > 1}
      <div id="qualitychooser">
        <ul>
          {foreach from=$mobileversions item=version}
            <li><a href="{$language}/recordings/details/{$recording.id},{$recording.title|filenameize}?quality={$version|escape:url}">{$version|escape:html}</a></li>
          {/foreach}
        </ul>
      </div>
    {/if}
    <br/>
  {else}
    <div id="playercontainer{if $recording.mediatype == 'audio'}audio{/if}">{#recordings__noflash#}</div>
    <script type="text/javascript">
      swfobject.embedSWF('flash/VSQPlayer.swf?v={$VERSION}', 'playercontainer{if $recording.mediatype == 'audio'}audio{if isset( $flashdata.subtitle_files )}subtitle{/if}{/if}', '950', '{$flashheight}', '11.1.0', 'flash/swfobject/expressInstall.swf', {$flashdata|@jsonescape:true}, flashdefaults.params, null, handleFlashLoad );
    </script>
  {/if}
</div>

{if false and !empty( $relatedvideos )}
<div class="recommendatory">
  <div class="title">
    <h2>{#recordings__relatedvideos#}</h2>
  </div>
  
  <ul>
    {foreach from=$relatedvideos item=item}
      <li>
        <div class="recordingpic">
          <a href="{$language}/recordings/details/{$item.id},{$item.title|filenameize}">
            <div class="length">{$item|@recordinglength|timeformat:minimal}</div>
            <img src="{$item|@indexphoto}" width="150" height="94"/>
          </a>
        </div>
        <div class="content">
          <h3><a href="{$language}/recordings/details/{$item.id},{$item.title|filenameize}">{$item.title|mb_wordwrap:13|escape:html}</a></h3>
          {if $item.subtitle|stringempty}
            <h4>{$item.subtitle|mb_wordwrap:20|escape:html}</h4>
          {/if}
          <div class="author">{$item|@nickformat|mb_wordwrap:20|escape:html}</div>
          {assign var=views value=$item.numberofviews|numberformat}
          <div class="views">{#recordings__recording_views#|sprintf:$views}</div>
        </div>
      </li>
    {/foreach}
  </ul>
</div>
{/if}

<div id="metadata">
  {assign var=numberofratings value=$recording.numberofratings|numberformat}
  <div class="ratewidget right" data-canrate="{$canrate}" title="{#recordings__ratewidgetheading#|sprintf:$numberofratings}">
    <span class="spinner"></span>
    <h3>{#recordings__recording_rating#}:</h3>
    <ul>
      <li{if $recording.rating > 0} class="full"{/if}><a href="{$language}/recordings/rate/{$recording.id}?rating=1"><span></span>1</a></li>
      <li{if $recording.rating > 1.5} class="full"{/if}><a href="{$language}/recordings/rate/{$recording.id}?rating=2"><span></span>2</a></li>
      <li{if $recording.rating > 2.5} class="full"{/if}><a href="{$language}/recordings/rate/{$recording.id}?rating=3"><span></span>3</a></li>
      <li{if $recording.rating > 3.5} class="full"{/if}><a href="{$language}/recordings/rate/{$recording.id}?rating=4"><span></span>4</a></li>
      <li{if $recording.rating > 4.5} class="full"{/if}><a href="{$language}/recordings/rate/{$recording.id}?rating=5"><span></span>5</a></li>
    </ul>
  </div>
  <div id="recordingviews" title="{#recordings__metadata_views#}">{$recording.numberofviews|numberformat}</div>
  <div class="recordinguploader">
    {if $author.avatarstatus == 'onstorage'}
      <div class="avatar">
        <img src="{$author|@avatarphoto}" width="36" height="36"/>
      </div>
    {/if}
    <div class="content">
      <h3>{#recordings__uploader#}:</h3>
      <div class="uploader">{$author|@nickformat|mb_wordwrap:20|escape:html}</div>
    </div>
  </div>
  
  {if $recording.description|stringempty}
    <div class="recordingdescription">
      <p>{$recording.description|escape:html}</p>
    </div>
  {/if}
  
  <div class="copyright">
    <p>{$recording.copyright|escape:html|default:#recordings__nocopyright#}</p>
  </div>
  
  <table id="metadatatable">
    {if !empty( $recording.presenters )}
      <tr>
        <td class="labelcolumn">{#recordings__presenters#}:</td>
        <td>
          {include file=Visitor/presenters.tpl presenters=$recording.presenters}
        </td>
      </tr>
    {/if}
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
  
  <div id="infotoggle">
    <ul>
      {if $member.id}
        <li id="channellink"><a href="#" title="{#recordings__addtochannel#}"><span></span>{#recordings__addtochannel#}</a></li>
      {/if}
      <li id="embedlink"><a href="#" title="{#recordings__embed#}"><span></span>{#recordings__embed#}</a></li>
    </ul>
    <div id="gradient"></div>
    <a id="detaillink" href="#" data-show="{#recordings__showdetails#|escape:html}" data-hide="{#recordings__hidedetails#|escape:html}">{#recordings__showdetails#}</a>
  </div>
  
  <div id="embed">
    {capture assign=embed}
      <iframe width="480" height="{$height}" src="{$BASE_URI}{$language}/recordings/embed/{$recording.id}" frameborder="0" allowfullscreen="allowfullscreen"></iframe>
    {/capture}
    <label for="embedcode">{#recordings__embedcode#}:</label>
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
  
  {if !empty( $attachments )}
    <div class="attachments">
      <h3>{#recordings__manageattachments_title#}</h3>
      <ul>
        {foreach from=$attachments item=attachment}
          <li><a href="{$attachment|@attachmenturl:$recording:$STATIC_URI}">{$attachment.title|escape:html}</a></li>
        {/foreach}
      </ul>
    </div>
  {/if}
  <br/>
  {if $member.id}
    <div id="channels" class="hidden">
      <h3>{#recordings__addtochannel_title#}</h3>
      <ul id="channelslist">
        {include file=Visitor/Recordings/Details_channels.tpl level=1}
      </ul>
    </div>
  {/if}
  {if $bootstrap->config.loadaddthis}
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
  {/if}
</div>

{if !empty( $recordingdownloads )}
  <div id="recordingdownloads">
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
  <div class="title"><h2>{#recordings__comments#}</h2></div>
  <div id="commentwrap">
    {$commentoutput.html}
  </div>
  {if $member.id}
    <div id="commentform">
      <div class="loading"></div>
      {$commentform}
    </div>
  {/if}
</div>
<div class="clear"></div>

{if !empty( $relatedvideos )}
<div class="recommendatory">
  <div class="title">
    <h2>{#recordings__relatedvideos#}</h2>
  </div>
  
  <ul>
    {foreach from=$relatedvideos item=item}
      <li>
        <div class="recordingpic">
          <a href="{$language}/recordings/details/{$item.id},{$item.title|filenameize}">
            <div class="length">{$item|@recordinglength|timeformat:minimal}</div>
            <img src="{$item|@indexphoto}" width="159" height="94"/>
          </a>
        </div>
        <div class="content">
          <h3><a href="{$language}/recordings/details/{$item.id},{$item.title|filenameize}">{$item.title|mb_wordwrap:22|escape:html}</a></h3>
          {if $item.subtitle|stringempty}
            <h4>{$item.subtitle|mb_wordwrap:27|escape:html}</h4>
          {/if}
          <div class="author">{$item|@nickformat|mb_wordwrap:20|escape:html}</div>
          {assign var=views value=$item.numberofviews|numberformat}
          <div class="views">{#recordings__recording_views#|sprintf:$views}</div>
        </div>
      </li>
    {/foreach}
  </ul>
</div>
{/if}

{include file="Visitor/_footer.tpl"}