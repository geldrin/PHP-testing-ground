
<div id="headerlogin" class="rightbox">
  {if $member}
    {*}<a href="{$language}/users/welcome" title="{l key=sitewide_myprofile}">{l key=sitewide_welcome} {$member->nickname|escape:html}!</a>{/*}
    <div id="currentuser" class="active">
      <div id="currentusername">{$member->nickname|escape:html}</div>
      <div id="currentusermenu">
        <span class="title">Settings</span>
        <ul>
          <li><a href="#">Profile</a></li>
          <li><a href="#">Welcome</a></li>
          <li><a href="#">Lorem Ipsum</a></li>
        </ul>
        <ul>
          <li><a href="#">Logout</a></li>
        </ul>
      </div>
    </div>
  {else}
    <form action="{$language}/users/login" method="post">
      <input type="hidden" name="action" value="submitlogin">
      <input class="inputtext inputbackground" type="text" name="email"/>
      <input class="inputtext inputbackground" type="password" name="password"/>
      <input class="submitbutton" type="submit" value="login"/>
    </form>
  {/if}
</div>
