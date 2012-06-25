<object id="MediaPlayer" width="470" height="{if $aspectratio == '16:9'}267{elseif $aspectratio == '5:4'}376{else}354{/if}">
 <param name="controller" value="TRUE">
 <param name="type" value="video/x-ms-wmv">
 <param name="autoplay" value="true">
 <param name="target" value="myself">
 <param name="src" value="{$url|escape:html}">
 <param name="ShowControls" value="false">
 <param name="autoSize" value="false">
 <param name="displaySize" value="0">
 <embed 
     controller="TRUE"
     width="470"
     height="{if $aspectratio == '16:9'}267{elseif $aspectratio == '5:4'}376{else}354{/if}"
     target="myself"
     src="{$url|escape:html}"
     type="video/x-ms-wmv" 
     bgcolor="#000000"
     border="0"
     transparentatstart="0"
     animationatstart="0"
     showcontrols="0"
     autosize="0"
     displaysize="0">
 </embed>
</object>