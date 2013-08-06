{include file="Visitor/_header.tpl"}
<div id="contentsbody">
<h1>{#users__welcomepage_welcome#} {$member|nickformat|escape:html}!</h1>

<p>{#users__welcomepage_intro#}</p>

<h2>{#users__welcomepage_myoptions#}</h2>
<p>{#users__welcomepage_myoptions_intro#}</p>
<ul>
  <li><a href="{$language}/users/modify">{#users__welcomepage_mydata#}</a></li>
  <li><a href="{$language}/recordings/featured">{#users__welcomepage_recommended#}</a></li>
  <li><a href="{$language}/users/logout">{#users__welcomepage_logout#}</a></li>
</ul>

{if $member.isuploader}
  <h2>{#users__welcomepage_manage#}</h2>
  <p>{#users__welcomepage_manage_intro#}</p>
  <ul>
	<li><a href="{$language}/recordings/upload">{#users__welcomepage_upload_recordings#}</a></li>
	<li><a href="{$language}/recordings/myrecordings">{#users__welcomepage_myrecordings#}</a></li>
	<li><a href="{$language}/channels/mychannels">{#users__welcomepage_mychannels#}</a></li>
  </ul>
{/if}

{if $member.isnewseditor or $member.isclientadmin}
  <h2>{#users__welcomepage_admin_features#}</h2>
  <p>{#users__welcomepage_admin_intro#}</p>
  <ul>
    {if $member.isnewseditor or $member.isclientadmin}
      <li><a href="{$language}/organizations/createnews">{#users__welcomepage_create_news#}</a></li>
      <li><a href="{$language}/organizations/listnews">{#users__welcomepage_list_news#}</a></li>
    {/if}
    {if $member.isclientadmin}
      <li><a href="{$language}/organizations/modifyintroduction">{#users__welcomepage_org_intro#}</a></li>
      <li><a href="{$language}/users/admin">{#users__welcomepage_user_admin#}</a></li>
      <li><a href="{$language}/departments/admin">{#users__welcomepage_departments_admin#}</a></li>
	  <li><a href="{$language}/groups">{#users__welcomepage_mygroups#}</a></li>
      <li><a href="{$language}/genres/admin">{#users__welcomepage_genres_admin#}</a></li>
      <li><a href="{$language}/categories/admin">{#users__welcomepage_categories_admin#}</a></li>
    {/if}
  </ul>
{/if}

</div>
{include file="Visitor/_footer.tpl"}