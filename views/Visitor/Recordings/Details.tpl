{include file="Visitor/_header.tpl" title=$recording.title}
<div class="heading recording">
  {if !$recording.ispublished}
    <center><a href="{$language}/recordings/modifysharing/{$recording.id}">{l module=recordings key=notpublished_warning}</a></center>
    <br/>
  {/if}
  <h1>{$recording.title|escape:html}</h1>
  
  {if $recording.subtitle|stringempty}
    <h2>{$recording.subtitle|escape:html}</h2>
  {/if}
  
  <div class="ratewidget"{if $canrate} nojs="1"{/if}>
    <span class="spinner"></span>
    <h2><span class="count">{$recording.numberofratings}</span> {l module=recordings key=ratewidgetheading}</h2>
    <ul>
      <li{if $recording.rating > 0} class="full"{/if}><a href="{$language}/recordings/rate?recordid={$recording.id}&rating=1"><span></span>1</a></li>
      <li{if $recording.rating > 1.5} class="full"{/if}><a href="{$language}/recordings/rate?recordid={$recording.id}&rating=2"><span></span>2</a></li>
      <li{if $recording.rating > 2.5} class="full"{/if}><a href="{$language}/recordings/rate?recordid={$recording.id}&rating=3"><span></span>3</a></li>
      <li{if $recording.rating > 3.5} class="full"{/if}><a href="{$language}/recordings/rate?recordid={$recording.id}&rating=4"><span></span>4</a></li>
      <li{if $recording.rating > 4.5} class="full"{/if}><a href="{$language}/recordings/rate?recordid={$recording.id}&rating=5"><span></span>5</a></li>
    </ul>
  </div>
</div>

<div class="player">
<script type="text/javascript">
swfobject.embedSWF('flash/TCPlayer{$VERSION}.swf', 'playercontainer{if $recording.mediatype == 'audio'}audio{if isset( $flashdata.subtitle_files )}subtitle{/if}{/if}', '950', '{if $recording.mediatype == 'audio' and isset( $flashdata.subtitle_files )}140{elseif $recording.mediatype == 'audio'}60{else}530{/if}', '11.1.0', 'flash/swfobject/expressInstall.swf', {$flashdata|@jsonescape}, flashdefaults.params );
</script>
  <div id="playercontainer{if $recording.mediatype == 'audio'}audio{/if}">{l module=recordings key=noflash}</div>
</div>

<div id="description">
  {*}
    <ul id="detailmenu">
      <li class="active"><a href="#">{l module=recordings key=details_basics}</a></li>
      <li><a href="#">{l module=recordings key=details_contributors}</a></li>
      <li><a href="#">{l module=recordings key=details_attachments}</a></li>
      <li class="last"><a href="#">{l module=recordings key=details_copyright}</a></li>
    </ul>
    <div class="basics left">
      <div class="wrap left">
        <ul>
          <li>
            <h2>{l module=recordings key=uploader}:</h2>
            {$author.nickname|escape:html}
          </li>
          <li>
            <h2>{l module=recordings key=details_recordedtimestamp}:</h2>
            {l key=smarty_dateformat_long assign=dateformat_long}
            {$recording.recordedtimestamp|date_format:$dateformat_long}
            {if $recording.masterlength}<h2>{l module=recordings key=recordlength}:</h2>
            {$recording.masterlength|timeformat}
            {/if}
          </li>
          <li>
            <h2>{l module=recordings key=details_uploadtimestamp}:</h2>
            {$recording.timestamp|date_format:$dateformat_long}
          </li>
        </ul>
      </div>
      <div class="wrap right">
        <ul>
          {if $recording.keywords}
          <li>
            <h2>{l module=recordings key=keywords}:</h2>
            {$recording.keywords|escape:html}
          </li>
          {/if}
        </ul>
      </div>
    </div>
    <div class="attachments">
      <div class="wrap">
        <ul>
        {foreach from=$attachments item=attachment}
          <li{if $smarty.foreach.attachments.last} class="last"{/if}>
            <a href="{$attachmenturl}{$attachment.id}.{$attachment.masterextension|escape:url}/{$attachment.masterfilename|escape:url}">
              {$attachment|@title|escape:html} ({$attachment.masterextension|truncate:7|escape:html})
            </a>
          </li>
        {foreachelse}
          <li>{l module=recordings key=noattachments}</li>
        {/foreach}
        </ul>
      </div>
    </div>
    <div class="copyright">
      {l key=nocopyright assign=nocopyright}
      <p>{$recording|@title:copyright|escape:html|default:$nocopyright}</p>
    </div>
  {/*}
  <div class="recordingdescription">
    {if $recording.description|stringempty}
    <h1>{l module=recordings key=description}</h1>
    <p>{$recording.description|escape:html}</p>
    {/if}
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