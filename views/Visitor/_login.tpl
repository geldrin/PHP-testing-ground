
<div id="headerlogin" class="rightbox">
  {if $member.id}
    {if $browser.mobile and !$browser.tablet}
      <div id="headerloginactions">
        <a href="{$language}/users/logout">{#usermenu_users_logout#}</a>
      </div>
    {/if}
    {assign var=columncount value=2}
    {if $member.isnewseditor or $member.isclientadmin}
      {assign var=columncount value=$columncount+1}
    {/if}
    
    {if ( $member.isuploader or $member.isclientadmin ) or ( $organization.islivestreamingenabled and $member.isliveadmin )}
      {assign var=columncount value=$columncount+1}
    {/if}
    
    <div id="currentuser">
      <div class="avatar">{if $member.avatarstatus == 'onstorage'}<img src="{$member|@avatarphoto}" width="36" height="36"/>{/if}</div>
      <div id="currentusercontent">
        <a id="currentusername" href="#">{$member.namefirst|escape:html}<span></span></a>
        <div id="currentusermenu" style="width: {$columncount*216-216+241}px">
          {if $member.isnewseditor or $member.isclientadmin or $member.isuploader or ( $organization.islivestreamingenabled and $member.isliveadmin )}
            <div class="column first">
              {if $member.isnewseditor or $member.isclientadmin}
                <div class="title">{#usermenu_organizations_title#}</div>
                <ul>
                  <li><a href="{$language}/organizations/listnews">{#usermenu_organizations_news#}</a></li>
                  <li><a href="{$language}/organizations/modifyintroduction">{#usermenu_organizations_introduction#}</a></li>
                  <li><a href="{$language}/users/admin">{#usermenu_organizations_admin#}</a></li>
                  <li><a href="{$language}/users/invite">{#usermenu_organizations_invite#}</a></li>
                  <li><a href="{$language}/users/massinvite">{#usermenu_organizations_massinvite#}</a></li>
                </ul>
                <div class="hr"></div>
              {/if}
            </div>
          {/if}
          
          <div class="column{if $columncount != 4} first{/if}">
            {if $member.isclientadmin}
              <div class="title">{#usermenu_classification_title#}</div>
              <ul>
                <li><a href="{$language}/genres/admin">{#usermenu_genres_admin#}</a></li>
                <li><a href="{$language}/categories/admin">{#usermenu_categories_admin#}</a></li>
                <li><a href="{$language}/departments/admin">{#usermenu_departments_admin#}</a></li>
              </ul>
              <div class="hr"></div>
            {/if}
            <div class="title">{#usermenu_channels_title#}</div>
            <ul>
              <li><a href="{$language}/channels/mychannels">{#usermenu_channels_mychannels#}</a></li>
              {if $member.isuploader or $member.isclientadmin}<li><a href="{$language}/channels/create">{#usermenu_channels_create#}</a></li>{/if}
            </ul>
          </div>
          {if ( $member.isuploader or $member.isclientadmin ) or ( $organization.islivestreamingenabled and $member.isliveadmin )}
          <div class="column">
            {*}
            <div class="title">{#usermenu_events_title#}</div>
            <ul>
              <li><a href="{$language}/events/myevents">{#usermenu_events_myevents#}</a></li>
              <li><a href="{$language}/events/create">{#usermenu_events_create#}</a></li>
            </ul>
            <div class="hr"></div>
            <div class="title">{#usermenu_groups_title#}</div>
            <ul>
              <li><a href="{$language}/groups/mygroups">{#usermenu_groups_mygroups#}</a></li>
              <li><a href="{$language}/groups/create">{#usermenu_groups_create#}</a></li>
            </ul>
            {/*}
            
            {if $member.isuploader or $member.isclientadmin}
              <div class="title">{#usermenu_recordings_title#}</div>
              <ul>
                <li><a href="{$language}/recordings/myrecordings">{#usermenu_recordings_myrecordings#}</a></li>
                {if $member.isuploader}<li><a href="{$language}/recordings/upload">{#usermenu_recordings_upload#}</a></li>{/if}
              </ul>
              <div class="hr"></div>
            {/if}
            
            {if $organization.islivestreamingenabled and ( $member.isliveadmin or $member.isclientadmin)}
              <div class="title">{#usermenu_live_title#}</div>
              <ul>
                <li><a href="{$language}/live">{#usermenu_live_list#}</a></li>
                <li><a href="{$language}/live/create">{#usermenu_live_create#}</a></li>
              </ul>
            {/if}
          </div>
          {/if}
          <div class="column last">
            <div class="placeholder"></div>
            <div class="title">{#usermenu_users_title#}</div>
            <ul>
              <li><a href="{$language}/users/welcome">{#usermenu_users_welcome#}</a></li>
              <li><a href="{$language}/users/modify">{#usermenu_users_modify#}</a></li>
              <li><a href="{$language}/users/logout">{#usermenu_users_logout#}</a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  {else}
    <div id="headerloginactions">
      {#headerloginactions#|sprintf:"`$language`/users/login":"`$language`/users/signup"}
    </div>
    <div id="headerloginform" class="hidden">
      <form action="{$language}/users/login" method="post">
        <input type="hidden" name="action" value="submitlogin"/>
        <input type="hidden" name="welcome" value="{if $welcome}1{else}0{/if}"/>
        <input type="hidden" name="forward" value="{$FULL_URI|escape:html}"/>
        <input class="inputtext inputbackground clearonclick" type="text" name="email" data-origval="{#youremail#|escape:html}" value="{#youremail#|escape:html}"/>
        <input class="inputtext inputbackground clearonclick" type="password" name="password" data-origval="******" value="******"/>
        <input class="submitbutton" type="submit" value="login"/>
      </form>
    </div>
  {/if}
</div>
