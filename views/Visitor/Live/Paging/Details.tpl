{if ( $item.livefeedid && $root.isactive && $feeds[$item.livefeedid].external && $item.liveexternal ) || ( $item.livefeedid and $feeds[$item.livefeedid].currentrecordingid == $item.id && $feeds[$item.livefeedid].status == 'live' )}
  {assign var="streamingactive" value=1}
{else}
  {assign var="streamingactive" value=0}
  {assign var="feedtesting" value=0}
{/if}
  <li>
    <div class="recordingcontent">
      <h1>
        {if $streamingactive}
          <a href="{$language}/live/view/{$item.livefeedid},{$feeds[$item.livefeedid].name|filenameize}" class="livefeed" title="{if $feedtesting}{#live__feedistesting#}{else}{#live__feedislive#}{/if}">{if $feedtesting}{#live__feedistesting#}{else}{#live__feedislive#}{/if}</a>
          <a href="{$language}/live/view/{$item.livefeedid},{$feeds[$item.livefeedid].name|filenameize}">
        {/if}
        {$item.title|escape:html}
        
      </h1>
      {if $item.subtitle}<h2>{$item.subtitle|escape:html}</h2>{/if}
      <p>{#recordings__starttimestamp#}: {$item.recordedtimestamp}</p>
      {if $item.description}<p>{$item.description|mb_truncate:100|escape:html}</p>{/if}
      
      <div class="clear"></div>
    </div>
  </li>
