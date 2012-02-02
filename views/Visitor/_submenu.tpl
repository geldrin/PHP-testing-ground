
  <div id="submenu">
    <div class="wrap">
      {if $member}
      <div id="welcome">
        <a href="{$language}/users/welcome" title="{l key=sitewide_myprofile}">{l key=sitewide_welcome} {$member->nickname|escape:html}!</a>
      </div>
      {/if}
      <ul class="right">
        {if $member}
          <li><a href="{$language}/recordings/upload">{l key=sitewide_upload}</a></li>
        {/if}
        {if !$member}
          {if $smarty.get.forward}
            {assign var=forwardurl value=$smarty.get.forward}
          {else}
            {assign var=forwardurl value=$FULL_URI}
          {/if}
        <li class="login"><a href="{$language}/users/login?forward={$forwardurl|escape:url}">{l key=sitewide_login}</a></li>
        <li class="last register"><a href="{$language}/users/signup">{l key=sitewide_signup}</a></li>
        {/if}
      </ul>
    </div>
  </div>
