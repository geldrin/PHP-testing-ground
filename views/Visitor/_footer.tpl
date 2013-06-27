      <div class="clear"></div>
    </div>
    <div id="footer">
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
          <li><a href="{$language}/#">{#footer_categories#}</a></li>
          <li><a href="{$language}/#">{#footer_live#}</a></li>
          <li><a href="{$language}/#">{#footer_channels#}</a></li>
          <li><a href="{$language}/#">{#footer_featured#}</a></li>
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
            {if $member.isuploader}
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
    </div>
  </div>{*}wrap div{/*}
</div>{*}pagecontainer div{/*}
<div id="footerbg"></div>

{if $bootstrap->debug and false}{debug}{/if}
{if $bootstrap->production and $bootstrap->config.loadgoogleanalytics}
{literal}
<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-34892054-1']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
{/literal}
{/if}
</body>
</html>