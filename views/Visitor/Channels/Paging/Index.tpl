
<li class="listitem">
  <div class="recordingpic">
    <a href="{$language}/channels/details/{$item.id},{$item.title|filenameize}"><img src="{$item|@indexphoto}"/></a>
  </div>
  
  <div class="recordingcontent">
    <div class="title">
      <h3><a href="{$language}/channels/details/{$item.id},{$item.title|filenameize}">{$item.title|escape:html}</a></h3>
      {if $item.subtitle|stringempty}<h4>{$item.subtitle|escape:html}</h4>{/if}
    </div>
    {if $item.description}
      <p>{$item.description|escape:html|nl2br}</p>
    {/if}
    <div class="clear"></div>
  </div>
</li>