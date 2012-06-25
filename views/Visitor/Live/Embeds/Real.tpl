<object id="realmedia" width="470" height="{if $aspectratio == '16:9'}267{elseif $aspectratio == '5:4'}376{else}354{/if}"
  classid="clsid:CFCDAA03-8BE4-11cf-B84B-0020AFBBCCFA">
  <param name="src" value="{$url|escape:html}">
  <param name="controls" value="ImageWindow">
  <param name="console" value="one">
  <param name="autostart" value="true">
  <embed src="{$url|escape:html}"
    width="470"
    height="{if $aspectratio == '16:9'}267{elseif $aspectratio == '5:4'}376{else}354{/if}"
    autostart="true"
    controls="ImageWindow"
    console="one"
    nojava="true">
  </embed>
</object>