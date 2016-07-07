{capture assign=url}{$language}/live/view/{$item.id},{$item.name|filenameize}{/capture}
<li class="listitem">
  <div class="recordingpic">
    <a href="{$url}"><img src="{$item|@indexphoto:live}"/></a>
  </div>

  <div class="recordingcontent">
    <div class="title">
      {if $streamingactive}
        <a href="{$url}" class="livefeed" title="{#live__feedislive#}">{#live__feedislive#}</a>
      {/if}
      {if $organization.islivepinenabled and $item|@userHasAccess}
        <span class="pin">{#live__pin#|sprintf:$item.pin}</span>
      {/if}
      <h3><a href="{$url}">{$item.name|mb_wordwrap:30|escape:html}</a></h3>
    </div>
    <div class="clear"></div>
  </div>
</li>
