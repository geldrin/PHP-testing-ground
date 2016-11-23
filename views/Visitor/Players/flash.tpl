{if !$playercontainerid}
  {capture assign=playercontainerid}playercontainer{if $recording.mediatype == 'audio'}audio{if isset( $playerdata.subtitle_files )}subtitle{/if}{/if}{/capture}
{/if}
{if !$skipcontainer}
  <div id="{$playercontainerid}">{#recordings__noflash#}</div>
{/if}
<script type="text/javascript">
  swfobject.embedSWF('flash/VSQ{$flashplayertype}Player.swf?v={$VERSION}', '{$playercontainerid}', '980', '{$playerheight}', '11.1.0', 'flash/swfobject/expressInstall.swf', {$playerdata|@jsonescape:true}, {$flashplayerparams|default:"flashdefaults.params"}, null, handleFlashLoad );
</script>