
<div id="headerlogin" class="rightbox">
  {if $member}
    <a href="{$language}/users/welcome" title="{l key=sitewide_myprofile}">{l key=sitewide_welcome} {$member->nickname|escape:html}!</a>
  {else}
    <form action="{$language}/users/login" method="post">
      <input type="hidden" name="action" value="submitlogin">
      <input class="inputtext" type="text" name="email"/>
      <input class="inputtext" type="password" name="password"/>
      <input class="submit" type="submit" value="login"/>
    </form>
  {/if}
</div>
