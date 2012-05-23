{include file="Visitor/_header.tpl" title=$recording.title}
<div class="title recording">
  {if !$recording.ispublished}
    <center><a href="{$language}/recordings/modifysharing/{$recording.id}">{#recordings__notpublished_warning#}</a></center>
    <br/>
  {/if}
  
  <h1>{$recording.title|escape:html}</h1>
  
  {if $recording.subtitle|stringempty}
    <h2>{$recording.subtitle|escape:html}</h2>
  {/if}
  
</div>

<div class="player">
<script type="text/javascript">
swfobject.embedSWF('flash/TCPlayer{$VERSION}.swf', 'playercontainer{if $recording.mediatype == 'audio'}audio{if isset( $flashdata.subtitle_files )}subtitle{/if}{/if}', '950', '{if $recording.mediatype == 'audio' and isset( $flashdata.subtitle_files )}140{elseif $recording.mediatype == 'audio'}60{else}530{/if}', '11.1.0', 'flash/swfobject/expressInstall.swf', {$flashdata|@jsonescape:true}, flashdefaults.params );
</script>
  <div id="playercontainer{if $recording.mediatype == 'audio'}audio{/if}">{#recordings__noflash#}</div>
</div>

<div id="recommendatory" class="rightbox">
  <div class="title">
    <h2>{#recordings__relatedvideos#}</h2>
  </div>
</div>

<div id="metadata" class="leftdoublebox">
  {assign var=numberofratings value=$recording.numberofratings|numberformat}
  <div class="ratewidget right"{if $canrate} nojs="1"{/if} title="{#recordings__ratewidgetheading#|sprintf:$numberofratings}">
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
  
  <div class="recordinguploader">
    <div class="avatar">
      <img src="{$STATIC_URI}images/avatar_placeholder.png" width="36" height="36"/>
    </div>
    <div class="content">
      <h3>{#recordings__uploader#}:</h3>
      <div class="uploader">{$author.nickname|mb_truncate:50|escape:html}</div>
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
    <tr>
      <td class="labelcolumn">{#recordings__recordlength#}:</td>
      <td>{$recording.masterlength|timeformat}</td>
    </tr>
    <tr>
      <td class="labelcolumn">{#recordings__details_recordedtimestamp#}:</td>
      <td>{$recording.recordedtimestamp|date_format:#smarty_dateformat_long#}</td>
    </tr>
    <tr>
      <td class="labelcolumn">{#recordings__details_uploadtimestamp#}:</td>
      <td>{$recording.timestamp|date_format:#smarty_dateformat_long#}</td>
    </tr>
    {if $recording.keywords|stringempty}
      <tr>
        <td class="labelcolumn">{#recordings__keywords#}:</td>
        <td>{$recording.keywords|escape:html}</td>
      </tr>
    {/if}
  </table>
  <a class="hidedetails" href="#">{#recordings__hidedetails#}</a>
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
          <a href="#">
            <div class="length">{$item.masterlength|timeformat}</div>
            <img src="{$item|@indexphoto}" width="150" height="94"/>
          </a>
        </div>
        <div class="content">
          <h3><a href="{$language}/recordings/details/{$item.id},{$item|@title|filenameize}">{$item|@title|mb_wordwrap:30|escape:html}</a></h3>
          {assign var=views value=$item.numberofviews|numberformat}
          <div class="views">{#recordings__recording_views#|sprintf:$views}</div>
        </div>
      </li>
    {/foreach}
  </ul>
</div>
{/if}

{*}
<div id="comments">
  <div class="wrap">
    <div id="hider"><a href="#"><span></span>Hide/Show</a></div>
    <div id="comments-wrap">
      <h1><a href="#">{#recordings__comments#} <span>({$commentcount})</span></a></h1>
      <div class="wrap">
        <ul>
          {foreach from=$comments item=comment name=comment}
          <li{if $smarty.foreach.comment.last} class="last"{/if}>
            <h2><a href="#">{$comment.nickname|escape:html}</a> ({$comment.timestamp})</h2>
            <p>{$comment.text}</p>
          </li>
          {foreachelse}
          <li id="comments-none" class="last">{#recordings__nocomments#}</li>
          {assign var=nocomments value=true}
          {/foreach}
        </ul>
        <div id="comments-spinner">&nbsp;</div>
        <div id="comments-more"{if $nocomments} class="hidden"{/if}>
          <a href="{$language}/recordings/getcomments/{$recording.id}">{#recordings__morecomments#}</a>
        </div>
      </div>
    </div>
  </div>
{if $member}
  <div id="comment-form">
    <form enctype="multipart/form-data" target="_self" id="commentform" name="commentform" action="{$language}/recordings/newcomment/{$recording.id}" method="post">
      <input type="hidden" id="actopm" name="target" value="submitnewcomment"/>
      <input type="hidden" id="id" name="id" value="{$recording.id}"/>
      <h1>{#recordings__yourcomment#}</h1>
        <div class="wrap">
          <textarea name="text" id="text" class="textarea"></textarea>
        </div>
      <input type="submit" value="{#recordings__sendcomment#}" class="left button"/>
    </form>
  </div>
{else}
  <div id="comments-needlogin">
    <center><a class="ajaxlogin" href="{$language}/users/login?forward={$FULL_URI|escape:url}">{#recordings__logintocomment#}</a></center>
  </div>
{/if}
</div>
{/*}
{include file="Visitor/_footer.tpl"}