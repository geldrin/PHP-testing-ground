    </div>
    <div id="footer">
      <div id="footerlogo">
        <a href="{$BASE_URI}"><span></span>{l key=sitename}</a>
      </div>
      <div class="footercontent leftbox">
        <ul class="footerlinks">
          <li><a href="#">Home</a></li>
          <li><a href="#">About</a></li>
          <li><a href="#">Clients</a></li>
          <li><a href="#">Contact Us</a></li>
        </ul>
        
        <h4 class="bottom">Lorem ipsum dolor sit amet</h4>
      </div>
      <div class="footercontent leftbox">
        <ul class="footerlinks">
          <li><a href="#">Home</a></li>
          <li><a href="#">About</a></li>
          <li><a href="#">Clients</a></li>
          <li><a href="#">Contact Us</a></li>
        </ul>
        
        <a href="#" class="bottom">support@example.com</a>
      </div>
    </div>
  </div>{*}wrap div{/*}
</div>{*}pagecontainer div{/*}

{if $bootstrap->debug and false}{debug}{/if}
{if $smarty.const.PRODUCTION}
{literal}
<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-16658651-1']);
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