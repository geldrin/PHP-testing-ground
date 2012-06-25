{if $smarty.request.chromeless}
  {include file="Visitor/_header_nolayout.tpl" title=$rootchannel.title islive=true bodyclass=liveiframe}
  {assign var=linksinnewwindow value=' target="_blank"'}
{else}
  {include file="Visitor/_header.tpl" title=$rootchannel.title islive=true}
{/if}

{if $currentstream.feedtype == 'flash'}
  {include file="Visitor/Live/Embeds/Flash.tpl" aspectratio=$currentstream.aspectratio url=$currentstream.streamurl keycode=$currentstream.keycode htmlid="stream" external=$feed.isexternal }
{/if}

{if $feed.numberofstreams == 2}
  {include file="Visitor/Live/Embeds/Flash.tpl" aspectratio=$currentstream.contentaspectratio url=$currentstream.contentstreamurl keycode=$currentstream.contentkeycode htmlid="contentstream" external=$feed.isexternal }
{/if}

<div class="clear"></div><br/>

{if $smarty.request.chromeless}
  {include file="Visitor/_footer_nolayout.tpl"}
{else}
  {include file="Visitor/_footer.tpl"}
{/if}