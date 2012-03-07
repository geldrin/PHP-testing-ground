{include file="Visitor/_header.tpl" title=$recording.title}
<div class="title recording">
  {if !$recording.ispublished}
    <center><a href="{$language}/recordings/modifysharing/{$recording.id}">{l module=recordings key=notpublished_warning}</a></center>
    <br/>
  {/if}
  
  <div class="ratewidget right"{if $canrate} nojs="1"{/if}>
    <span class="spinner"></span>
    <h2><span class="count">{$recording.numberofratings}</span> {l module=recordings key=ratewidgetheading}</h2>
    <ul>
      <li{if $recording.rating > 0} class="full"{/if}><a href="{$language}/recordings/rate/{$recording.id}?rating=1"><span></span>1</a></li>
      <li{if $recording.rating > 1.5} class="full"{/if}><a href="{$language}/recordings/rate/{$recording.id}?rating=2"><span></span>2</a></li>
      <li{if $recording.rating > 2.5} class="full"{/if}><a href="{$language}/recordings/rate/{$recording.id}?rating=3"><span></span>3</a></li>
      <li{if $recording.rating > 3.5} class="full"{/if}><a href="{$language}/recordings/rate/{$recording.id}?rating=4"><span></span>4</a></li>
      <li{if $recording.rating > 4.5} class="full"{/if}><a href="{$language}/recordings/rate/{$recording.id}?rating=5"><span></span>5</a></li>
    </ul>
  </div>
  
  <h1>{$recording.title|escape:html}</h1>
  
  {if $recording.subtitle|stringempty}
    <h2>{$recording.subtitle|escape:html}</h2>
  {/if}
  
</div>

<div class="player">
<script type="text/javascript">
swfobject.embedSWF('flash/TCPlayer{$VERSION}.swf', 'playercontainer{if $recording.mediatype == 'audio'}audio{if isset( $flashdata.subtitle_files )}subtitle{/if}{/if}', '950', '{if $recording.mediatype == 'audio' and isset( $flashdata.subtitle_files )}140{elseif $recording.mediatype == 'audio'}60{else}530{/if}', '11.1.0', 'flash/swfobject/expressInstall.swf', {$flashdata|@jsonescape:true}, flashdefaults.params );
</script>
  <div id="playercontainer{if $recording.mediatype == 'audio'}audio{/if}">{l module=recordings key=noflash}</div>
</div>

<div id="description">
  
  {if $recording.description|stringempty}
  <div class="recordingdescription">
    <h3>{l module=recordings key=description}:</h3>
    <p>{$recording.description|escape:html}</p>
  </div>
  {/if}
  
  <div class="recordinguploader">
    <h3>{l module=recordings key=uploader}:</h3>
    <p>{$author.nickname|escape:html}</p>
  </div>
  <div class="">
    <h3>{l module=recordings key=details_recordedtimestamp}:</h3>
    {l key=smarty_dateformat_long assign=dateformat_long}
    <p>{$recording.recordedtimestamp|date_format:$dateformat_long}</p>
  </div>
  
  {if $recording.masterlength}
  <div class="">
    <h3>{l module=recordings key=recordlength}:</h3>
    <p>{$recording.masterlength|timeformat}</p>
  </div>
  {/if}
  
  <div class="recoredinguploadtimestamp">
    <h3>{l module=recordings key=details_uploadtimestamp}:</h3>
    <p>{$recording.timestamp|date_format:$dateformat_long}</p>
  </div>
  
  {if $recording.keywords|stringempty}
  <div class="recordingkeywords">
    <h3>{l module=recordings key=keywords}:</h3>
    <p>{$recording.keywords|escape:html}</p>
  </div>
  {/if}
  
  <div class="copyright">
    {l module=recordings key=nocopyright assign=nocopyright}
    <h3>{l module=recordings key=details_copyright}:</h3>
    <p>{$recording.copyright|escape:html|default:$nocopyright}</p>
  </div>
  
</div>
{*}
<div id="comments">
  <div class="wrap">
    <div id="hider"><a href="#"><span></span>Hide/Show</a></div>
    <div id="comments-wrap">
      <h1><a href="#">{l module=recordings key=comments} <span>({$commentcount})</span></a></h1>
      <div class="wrap">
        <ul>
          {foreach from=$comments item=comment name=comment}
          <li{if $smarty.foreach.comment.last} class="last"{/if}>
            <h2><a href="#">{$comment.nickname|escape:html}</a> ({$comment.timestamp})</h2>
            <p>{$comment.text}</p>
          </li>
          {foreachelse}
          <li id="comments-none" class="last">{l module=recordings key=nocomments}</li>
          {assign var=nocomments value=true}
          {/foreach}
        </ul>
        <div id="comments-spinner">&nbsp;</div>
        <div id="comments-more"{if $nocomments} class="hidden"{/if}>
          <a href="{$language}/recordings/getcomments/{$recording.id}">{l module=recordings key=morecomments}</a>
        </div>
      </div>
    </div>
  </div>
{if $member}
  <div id="comment-form">
    <form enctype="multipart/form-data" target="_self" id="commentform" name="commentform" action="{$language}/recordings/newcomment/{$recording.id}" method="post">
      <input type="hidden" id="actopm" name="target" value="submitnewcomment"/>
      <input type="hidden" id="id" name="id" value="{$recording.id}"/>
      <h1>{l module=recordings key=yourcomment}</h1>
        <div class="wrap">
          <textarea name="text" id="text" class="textarea"></textarea>
        </div>
      <input type="submit" value="{l module=recordings key=sendcomment}" class="left button"/>
    </form>
  </div>
{else}
  <div id="comments-needlogin">
    <center><a class="ajaxlogin" href="{$language}/users/login?forward={$FULL_URI|escape:url}">{l module=recordings key=logintocomment}</a></center>
  </div>
{/if}
</div>
{/*}
{include file="Visitor/_footer.tpl"}