
<div id="headerlogin" class="rightbox">
  {if $member}
    {*}<a href="{$language}/users/welcome" title="{l key=sitewide_myprofile}">{l key=sitewide_welcome} {$member.nickname|escape:html}!</a>{/*}
    <div id="currentuser">
      <div id="currentusercontent">
        <a id="currentusername" href="#">{$member.nickname|escape:html}<span></span></a>
        <div id="currentusermenu">
          <span class="title">{l key=usermenu_users_title}</span>
          <ul>
            <li><a href="{$language}/users/welcome">{l key=usermenu_users_welcome}</a></li>
            <li><a href="{$language}/users/modify">{l key=usermenu_users_modify}</a></li>
            <li><a href="{$language}/users/admin">{l key=usermenu_users_listing}</a></li>
          </ul>
          <div class="hr"></div>
          <span class="title">{l key=usermenu_recordings_title}</span>
          <ul>
            <li><a href="{$language}/recordings/myrecordings">{l key=usermenu_recordings_myrecordings}</a></li>
            <li><a href="{$language}/recordings/upload">{l key=usermenu_recordings_upload}</a></li>
          </ul>
          <div class="hr"></div>
          <ul>
            <li><a href="{$language}/users/logout">{l key=usermenu_users_logout}</a></li>
          </ul>
        </div>
      </div>
    </div>
  {else}
    <div id="headerloginactions">
      {l key=headerloginactions assign=headerloginactions}
      {$headerloginactions|sprintf:"`$language`/users/login":"`$language`/users/signup"}
    </div>
    <div id="headerloginform" class="hidden">
      {l key=youremail assign=youremail}
      <form action="{$language}/users/login" method="post">
        <input type="hidden" name="action" value="submitlogin"/>
        <input type="hidden" name="forward" value="{$FULL_URI|escape:html}"/>
        <input class="inputtext inputbackground clearonclick" type="text" name="email" data-origval="{$youremail|escape:html}" value="{$youremail|escape:html}"/>
        <input class="inputtext inputbackground clearonclick" type="password" name="password" data-origval="******" value="******"/>
        <input class="submitbutton" type="submit" value="login"/>
      </form>
    </div>
  {/if}
</div>
