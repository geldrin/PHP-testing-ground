<li{if $smarty.foreach.paging.first} class="first"{/if}>
  <div class="channelcontent">
    <h1><a href="{$language}/live/details/{$item.id},{$item.title|filenameize}">{$item.title|escape:html}</a></h1>
    {if $item.subtitle}<h2>{$item.subtitle|escape:html}</h2>{/if}
    {if $item.description}<p>{$item.description|mb_truncate:400|escape:html}</p>{/if}
    <ul class="channelinfo">
      <li>{if $item.ordinalnumber}{$item.ordinalnumber|escape:html}, {/if}{$item.channeltype}</li>
      {if $item.url}<li><a href="{$item.url|escape:html}">{$item.url|mb_truncate:80|escape:html}</a></li>{/if}
      <li>{$item.starttimestamp}</li>
    </ul>
  </div>
</li>
