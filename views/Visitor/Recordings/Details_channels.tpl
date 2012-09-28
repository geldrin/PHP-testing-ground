{foreach from=$channels item=channel}
  <li>
    {if $channel.active}
      <div class="added"></div>
    {/if}
    <div class="channelname level{$level}">
      <div class="actions">
        {if $channel.active}
          <a href="{$language}/recordings/removefromchannel/{$recording.id}?channel={$channel.id}" class="removefromchannel" title="{#recordings__removefromchannel#}"></a>
        {else}
          <a href="{$language}/recordings/addtochannel/{$recording.id}?channel={$channel.id}" class="addtochannel" title="{#recordings__addtochannel#}"></a>
        {/if}
      </div>
      {$channel.title|escape:html|mb_wordwrap:70}
    </div>
    {if !empty( $channel.children )}
      <ul class="child">
        {include file=Visitor/Recordings/Details_channels.tpl channels=$channel.children level=$level+1}
      </ul>
    {/if}
  </li>
{/foreach}
