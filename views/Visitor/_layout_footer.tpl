<div class="footerinfowrap">
  <div class="title">{#sitedescription#}</div>
  {assign var=year value=$smarty.now|date_format:'%Y'}
  <div class="copyright">{#footer_copyright#|sprintf:$year}</div>
</div>
<div class="footerlinkswrap">
  <table class="footerlinks">
    <tr>
      <td class="label">{#footer_otherinfo#}</td>
      <td class="links">
        <a href="{$BASE_URI}">{#footer_home#}</a>
        <a href="{$language}/contents/about">{#footer_about#}</a>
        <a href="{$language}/contents/contact">{#footer_contactus#}</a>
      </td>
    </tr>
    <tr>
      <td class="label">{#footer_modules#}</td>
      <td class="links">
        <a href="{$language}/categories">{#footer_categories#}</a>
        {if $organization.islivestreamingenabled}
          <a href="{$language}/live">{#footer_live#}</a>
        {/if}
        <a href="{$language}/channels">{#footer_channels#}</a>
        <a href="{$language}/recordings/featured">{#footer_featured#}</a>
      </td>
    </tr>
    <tr>
      <td class="label">{#footer_aboutus#}</td>
      <td class="links">
        <a href="mailto:{$supportemail|escape:html}">{$supportemail|escape:html}</a>
      </td>
    </tr>
  </table>
</div>
<div class="footersocialwrap">
  <ul class="footersocial">
    <li><a href="#TODO" class="twitter" title="{#footer_twitter#}"><span></span>{#footer_twitter#}</a></li>
    <li><a href="#TODO" class="facebook" title="{#footer_facebook#}"><span></span>{#footer_facebook#}</a></li>
    <li><a href="#TODO" class="gplus" title="{#footer_gplus#}"><span></span>{#footer_gplus#}</a></li>
  </ul>
</div>
