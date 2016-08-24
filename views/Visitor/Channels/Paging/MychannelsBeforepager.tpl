<div id="pagetitle">
  <h1>{#channels__mychannels_title#}</h1>
</div>
<div class="channelgradient"></div>
<br/>

{if $member|@userHasPrivilege:'recordings_upload':'or':'isuploader':'ismoderateduploader'}
  <a href="{$language}/channels/create?forward={$FULL_URI|escape:url}">{#channels__create#}</a>
  <br/>
{/if}
