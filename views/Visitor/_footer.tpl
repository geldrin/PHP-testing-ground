      <div class="clear"></div>
    </div>
    <div id="footer">
      {eval var=$layoutfooter}
    </div>
  </div>{*}wrap div{/*}
</div>{*}pagecontainer div{/*}
<div id="footerbg"></div>

{if $bootstrap->debug and false}{debug}{/if}
{if $bootstrap->production and $bootstrap->config.loadgoogleanalytics}
<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', '{$bootstrap->config.googleanalytics_trackingcode}']);
  _gaq.push(['_trackPageview']);
{literal}
  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();
{/literal}
</script>
{/if}
</body>
</html>