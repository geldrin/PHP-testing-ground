{include file="Visitor/_header.tpl" module="contents"}

<div id="pagetitle">
  <h1>{#noprivilege_title#|sprintf:$privilege}</h1>
</div>
<div class="channelgradient"></div>
<br/>

<p>{#noprivilege_body#|sprintf:$privilege}</p>

{include file="Visitor/_footer.tpl"}