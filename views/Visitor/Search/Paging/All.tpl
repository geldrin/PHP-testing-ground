{if $item.type == 'channel'}
  {include file=Visitor/channellistitem.tpl item=$item}
{else}
  {include file=Visitor/recordinglistitem.tpl item=$item}
{/if}