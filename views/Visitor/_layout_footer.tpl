<div class="footercontent leftbox">
  <h2>{#footer_otherinfo#}</h2>
  <ul class="footerlinks">
    <li><a href="{$BASE_URI}">{#footer_home#}</a></li>
    <li><a href="{$language}/contents/about">{#footer_about#}</a></li>
    <li><a href="{$language}/contents/contact">{#footer_contactus#}</a></li>
  </ul>
</div>
<div class="footercontent leftbox">
  <h2>{#footer_modules#}</h2>
  <ul class="footerlinks">
    <li><a href="{$language}/categories">{#footer_categories#}</a></li>
    {if $organization.islivestreamingenabled}
      <li><a href="{$language}/live">{#footer_live#}</a></li>
    {/if}
    <li><a href="{$language}/channels">{#footer_channels#}</a></li>
    <li><a href="{$language}/recordings/featured">{#footer_featured#}</a></li>
  </ul>
</div>
{if $member.id}
  <div class="footercontent leftbox">
    <h2>{#footer_membermodules#}</h2>
    <ul class="footerlinks">
      <li><a href="{$language}/users/modify">{#footer_mysettings#}</a></li>
      {if $member.isnewseditor or $member.isclientadmin}
        <li><a href="{$language}/organizations/listnews">{#footer_newseditor#}</a></li>
      {/if}
      {if $member.isuploader or $member.ismoderateduploader}
        <li><a href="{$language}/recordings/upload">{#footer_upload#}</a></li>
        <li><a href="{$language}/recordings/myrecordings">{#footer_myrecordings#}</a></li>
      {/if}
    </ul>
  </div>
{/if}
<div class="footercontent leftbox right">
  <h2>{#footer_aboutus#}</h2>
  <p>{#footer_aboutus_content#}</p>
</div>
<div class="hr"></div>
<div class="leftbox bottom">
  {assign var=year value=$smarty.now|date_format:'%Y'}
  {#footer_copyright#|sprintf:$year}
</div>
<div class="leftbox bottom">
  <a href="mailto:{$supportemail|escape:html}">{$supportemail|escape:html}</a>
</div>