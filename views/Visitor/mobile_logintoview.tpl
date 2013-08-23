{if $indexphoto}
  <img src="{$indexphoto}" width="280" height="190"/><br/>
  <br/>
{else}
  <br/>
  <br/>
  <br/>
  <br/>
{/if}
<center>
  {#recording_needpermission#}<br/>
  <a href="{$language}/users/login?nolayout=1&forward={$FULL_URI|escape:url}">{#sitewide_login#}</a>
</center>
