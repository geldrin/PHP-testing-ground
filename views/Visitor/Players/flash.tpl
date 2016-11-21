<div id="{$playercontainerid}">{#recordings__noflash#}</div>
<script type="text/javascript">
  swfobject.embedSWF('flash/VSQPlayer.swf?v={$VERSION}', 'playercontainer{if $recording.mediatype == 'audio'}audio{if isset( $playerdata.subtitle_files )}subtitle{/if}{/if}', '980', '{$playerheight}', '11.1.0', 'flash/swfobject/expressInstall.swf', {$playerdata|@jsonescape:true}, flashdefaults.params, null, handleFlashLoad );
</script>