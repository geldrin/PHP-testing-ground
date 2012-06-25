<object id="quicktime" width="470" height="{if $aspectratio == '16:9'}267{elseif $aspectratio == '5:4'}376{else}354{/if}"
  classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B"
  codebase="http://www.apple.com/qtactivex/qtplugin.cab">
  <param name="src" value="{$url|escape:html}">
  <param name="autoplay" value="true">
  <param name="controller" value="false">
  {*} 16pixel kell a controllnak, annyival kisebb lesz a video{/*}
  <embed src="{$url|escape:html}"
    width="470"
    height="{if $aspectratio == '16:9'}267{elseif $aspectratio == '5:4'}376{else}354{/if}"
    autoplay="true"
    controller="true"
    scale="tofit"
    volume="50"
    pluginspage="http://www.apple.com/quicktime/download/">
  </embed>
</object>