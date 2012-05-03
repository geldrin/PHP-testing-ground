{include file="Visitor/_header.tpl"}

<script type="text/javascript" src="{$organization|@uri:static}js/swfobject.full{$VERSION}.js"></script>
<script type="text/javascript" src="{$organization|@uri:static}js/swfupload220/swfupload{$VERSION}.js"></script>

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