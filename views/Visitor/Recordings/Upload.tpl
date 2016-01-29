{include file="Visitor/_header.tpl"}
<script type="text/javascript" src="{$STATIC_URI}js/plupload/plupload{$VERSION}.js"></script>
<script type="text/javascript" src="{$STATIC_URI}js/plupload/plupload.html5{$VERSION}.js"></script>
<script type="text/javascript" src="{$STATIC_URI}js/plupload/plupload.flash{$VERSION}.js"></script>
<script type="text/javascript">
  var uploadurl      = "{$uploadurl}";
  var uploadchunkurl = "{$BASE_URI}{$language}/recordings/uploadchunk";
  var checkresumeurl = "{$BASE_URI}{$language}/recordings/checkfileresume";
</script>
<div id="pagetitle">
  <h1>{$title|escape:html}</h1>
</div>
<div class="channelgradient"></div>
<br/>

<div id="videoupload" class="leftdoublebox form">
  <noscript id="noscriptcontainer">
    <div class="formerrors">
      <br />
      <ul>
        <li>{#sitewide_jsrequired#}</li>
      </ul>
      <br />
    </div>
    <br />
  </noscript>
  <br />
  
  {$form}
  
</div>

{if !empty( $help )}
<div class="help rightbox small">
  <h1 class="title">{#help#}</h1>
  {$help.body}
</div>
{/if}
{include file="Visitor/_footer.tpl"}