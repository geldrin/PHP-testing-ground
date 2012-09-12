      <div class="clear"></div>
    </div>
    <div id="footer">
      <div id="footerlogo">
        <a href="{$BASE_URI}"><span></span>{#sitename#}</a>
      </div>
      <div class="footercontent leftbox">
        <h2>{#footer_otherinfo#}</h2>
        <ul class="footerlinks">
          <li><a href="#">{#footer_home#}</a></li>
          <li><a href="#">{#footer_about#}</a></li>
          <li><a href="#">{#footer_contactus#}</a></li>
        </ul>
      </div>
      <div class="footercontent leftbox">
        <h2>&nbsp;</h2>
        <ul class="footerlinks">
          <li><a href="#">{#footer_categories#}</a></li>
          <li><a href="#">{#footer_live#}</a></li>
          <li><a href="#">{#footer_channels#}</a></li>
          <li><a href="#">{#footer_featured#}</a></li>
        </ul>
      </div>
      {if false and $member.id}
        <div class="footercontent leftbox">
          <h2>&nbsp;</h2>
          <ul class="footerlinks">
            <li><a href="#">{#footer_mysettings#}</a></li>
            <li><a href="#">{#footer_newseditor#}</a></li>
            <li><a href="#">{#footer_upload#}</a></li>
            <li><a href="#">{#footer_myrecordings#}</a></li>
          </ul>
        </div>
      {/if}
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
{if $bootstrap->production}
{literal}
<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', '']);
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