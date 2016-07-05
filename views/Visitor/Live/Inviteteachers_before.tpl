{*}
<div class="title">
  <h1>{$title|escape:html}</h1>
  <h2>{$feed.title|escape:html}</h2>
  <br/>
</div>
{/*}
<div id="pagetitle"{if $titleclass} class="{$titleclass|escape:html}{/if}">
  <h1>{$title|escape:html}</h1>
</div>
<div class="channelgradient"></div>
<br/>

<a href="{$language}/live/teacherinvites/{$feed.id},{$feed.title|filenameize}?forward={$FULL_URI|escape:url}">{#live__teacher_invites#}</a>
<br/>
