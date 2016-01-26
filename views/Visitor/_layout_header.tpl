<a href="#" id="mobilemenu"></a>
<div id="headerlogo">
  <a href="{$BASE_URI}" title="{#sitename#}" class="basic"><span></span>{#sitename#}</a>
</div>
<div id="headermenu"{if $pagebgclass} class="black"{/if}>
  <nav>
    <ul>
      <li{if $module == 'categories'} class="active"{/if}><a href="{$language}/categories">{#sitewide_categories#}</a></li>
      {if $organization.islivestreamingenabled}
        <li{if $module == 'live'} class="active"{/if}><a href="{$language}/live">{#sitewide_live#}</a></li>
      {/if}
      <li{if $module == 'channels'} class="active"{/if}><a href="{$language}/channels">{#sitewide_channels#}</a></li>
      {*}
      <li{if $module == 'featured'} class="active"{/if}><a href="{$language}/recordings/featured/newest">{#sitewide_featured#}</a></li>
      {/*}
      <li id="headersearchlink"><a href="{$language}/search/all"><span></span>{#sitewide_search#}</a></li>
      <li id="headeruserlink">
        <a href="#"{if $member.id} title="{$member.namefirst|escape:html}"{/if}>
          {if $member.id}
            <img alt="{$member.namefirst|escape:html}" src="{$member|@avatarphoto}" width="36" height="36"/>
          {else}
            <span></span>{#sitewide_login#}
          {/if}
        </a>

        <div id="headerlogin">
          {if $member.id}
            {assign var=columncount value=2}
            {if $member.isnewseditor or $member.isclientadmin or $member.iseditor}
              {assign var=columncount value=$columncount+1}
            {/if}

            {if ( $member.isuploader or $member.ismoderateduploader or $member.isclientadmin or $member.iseditor ) or ( $organization.islivestreamingenabled and $member.isliveadmin )}
              {assign var=columncount value=$columncount+1}
            {/if}

            <div id="currentusermenu" class="menulayer" style="width: {$columncount*216-216+241}px">
              {if $member.isnewseditor or $member.isclientadmin or $member.iseditor}
                <div class="column first">
                    <div class="title">{#usermenu_organizations_title#}</div>
                    <ul>
                      {if $member.isclientadmin}
                        <li><a href="{$language}/organizations/accountstatus">{#usermenu_organizations_accountstatus#}</a></li>
                      {/if}
                      <li><a href="{$language}/organizations/listnews">{#usermenu_organizations_news#}</a></li>
                      {if $member.isclientadmin}
                        <li><a href="{$language}/organizations/modifyintroduction">{#usermenu_organizations_introduction#}</a></li>
                      {/if}
                      {if $member.isnewseditor or $member.isclientadmin}
                        <li><a href="{$language}/users/admin">{#usermenu_organizations_admin#}</a></li>
                        <li><a href="{$language}/users/invitations">{#usermenu_invitations#}</a></li>
                      {/if}
                    </ul>
                    <div class="hr"></div>
                    {if $member.isclientadmin}
                      <div class="title">{#usermenu_analytics_title#}</div>
                      <ul>
                        {if $member.isclientadmin}
                          <li><a href="{$language}/analytics/accreditedrecordings">{#usermenu_analytics_accreditedrecordings#}</a></li>
                          <li><a href="{$language}/analytics/statistics">{#usermenu_analytics_statistics#}</a></li>
                        {/if}
                      </ul>
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
                  {if $member.isuploader or $member.ismoderateduploader or $member.isclientadmin or $member.iseditor}<li><a href="{$language}/channels/create">{#usermenu_channels_create#}</a></li>{/if}
                </ul>
              </div>
              {if ( $member.isuploader or $member.ismoderateduploader or $member.isclientadmin or $member.iseditor) or ( $organization.islivestreamingenabled and $member.isliveadmin )}
              <div class="column">
                {*}
                <div class="title">{#usermenu_events_title#}</div>
                <ul>
                  <li><a href="{$language}/events/myevents">{#usermenu_events_myevents#}</a></li>
                  <li><a href="{$language}/events/create">{#usermenu_events_create#}</a></li>
                </ul>
                <div class="hr"></div>
                {/*}
                <div class="title">{#usermenu_groups_title#}</div>
                <ul>
                  <li><a href="{$language}/groups">{#usermenu_groups_mygroups#}</a></li>
                  <li><a href="{$language}/groups/create">{#usermenu_groups_create#}</a></li>
                </ul>
                <div class="hr"></div>

                {if $member.isuploader or $member.ismoderateduploader or $member.isclientadmin or $member.iseditor}
                  <div class="title">{#usermenu_recordings_title#}</div>
                  <ul>
                    <li><a href="{$language}/recordings/myrecordings">{#usermenu_recordings_myrecordings#}</a></li>
                    {if $member.isuploader or $member.ismoderateduploader}<li><a href="{$language}/recordings/upload">{#usermenu_recordings_upload#}</a></li>{/if}
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
                  {if !$member.source or $member.source == 'local'}
                    <li><a href="{$language}/users/logout">{#usermenu_users_logout#}</a></li>
                  {/if}
                </ul>
              </div>
            </div>

          {else}
            <div id="headerloginactions">

              <div id="headerloginform">
                <form action="{$language}/users/login" method="post">
                  <input type="hidden" name="action" value="submitlogin"/>
                  <input type="hidden" name="welcome" value="{if $welcome}1{else}0{/if}"/>
                  <input type="hidden" name="forward" value="{$FULL_URI|escape:html}"/>
                  <input class="inputtext inputbackground clearonclick" type="text" name="email" data-origval="{#youremail#|escape:html}" value="{#youremail#|escape:html}" tabindex="1"/>
                  <input class="inputtext inputbackground clearonclick" type="password" name="password" data-origval="******" value="******" tabindex="2"/>
                  <div id="login_rememberme_wrap">
                    <input type="checkbox" tabindex="3" name="autologin" value="1" id="login_rememberme"/><label for="login_rememberme">{#login_rememberme#}</label>
                  </div>
                  <input class="submitbutton" type="submit" value="{#sitewide_login#}" tabindex="4"/><br/>

                  {if $organization.registrationtype != 'closed'}
                    <a class="signuplink" href="{$language}/users/signup">{#sitewide_signup#}</a>
                  {/if}
                </form>
              </div>

            </div>
          {/if}
        </div>

      </li>
      <li id="languageselectorlink">
        {foreach from=$organization.languages key=languageid item=item}
          {if $languageid == $language}
            <a href="{$FULL_URI|changelanguage:$language}" class="{$language} active">
              {if $languageid == 'hu'}
                {#sitewide_language_hu#}
              {elseif $languageid == 'en'}
                {#sitewide_language_en#}
              {/if}
              <span></span>
            </a>
          {/if}
        {/foreach}
      </li>
    </ul>
  </nav>
</div>

<div id="languages">
  
  {foreach from=$organization.languages key=languageid item=item}
    {if $languageid != $language}
      <a href="{$FULL_URI|changelanguage:$languageid}" class="{$languageid}">
        {if $languageid == 'hu'}
          {#sitewide_language_hu#}
        {elseif $languageid == 'en'}
          {#sitewide_language_en#}
        {/if}
        <span></span>
      </a>
    {/if}
  {/foreach}
</div>

<div id="headersearch">
  <form action="{$language}/search/all" method="get">
    <button id="headersearchsubmit" type="submit"></button>
    <input class="inputtext clearonclick" type="text" name="q" data-origval="{#sitewide_search_input#|escape:html}" value="{$smarty.request.q|default:#sitewide_search_input#|escape:html}"/>
    <a href="{$language}/search/advanced" id="headersearchadvanced" title="{#sitewide_search_advanced#}"></a>
  </form>
</div>
